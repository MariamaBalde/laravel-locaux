<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class VendorDashboardService
{
    private const VALID_STATUSES = ['all', 'pending', 'processing', 'shipped', 'delivered', 'cancelled', 'refunded'];

    private const VALID_PERIODS = ['all', '7d', '30d', 'month'];

    public function getOverview(User $user, array $filters = []): array
    {
        $vendeurId = $this->resolveVendeurId($user);
        $status = $this->normalizeStatus($filters['status'] ?? 'all');
        $period = $this->normalizePeriod($filters['period'] ?? 'month');
        $page = max((int) ($filters['page'] ?? 1), 1);
        $perPage = min(max((int) ($filters['per_page'] ?? 6), 1), 50);

        $stats = $this->getStats($user, $vendeurId, $status, $period);
        $weeklyRevenue = $this->getWeeklyRevenue($vendeurId, $status, $period);
        $destinations = $this->getDestinations($vendeurId, $status, $period);
        $recentOrders = $this->getRecentOrders($vendeurId, $status, $period, $page, $perPage);
        $topProducts = $this->getTopProducts($vendeurId, $status, $period, (int) ($filters['top_limit'] ?? 5));

        return [
            'stats' => $stats,
            'weeklyRevenue' => $weeklyRevenue,
            'destinations' => $destinations,
            'topProducts' => $topProducts,
            'recentOrders' => $recentOrders['data'],
            'pagination' => $recentOrders['pagination'],
            'notifications' => [
                'pendingOrders' => $this->getPendingOrdersCount($vendeurId, $period),
            ],
        ];
    }

    public function getStats(User $user, int $vendeurId, string $status = 'all', string $period = 'month'): array
    {
        $orders = $this->baseOrdersQuery($vendeurId, $status, $period)
            ->with(['items' => function ($query) use ($vendeurId) {
                $query->where('vendeur_id', $vendeurId);
            }])
            ->get(['id', 'status', 'created_at']);

        // Le revenu reflète la période filtrée (month/7d/30d/all).
        $monthlyRevenue = (float) $orders->sum(fn ($order) => $order->items->sum('subtotal'));

        $pendingCount = (int) $orders
            ->whereIn('status', ['pending', 'processing'])
            ->count();

        $products = Product::query()->where('vendeur_id', $vendeurId);
        $totalProducts = (int) (clone $products)->count();
        $inactiveProducts = (int) (clone $products)->where('is_active', false)->count();
        $activeProducts = (int) (clone $products)
            ->where(function ($query) {
                $query->where('is_active', true)
                    ->orWhereNull('is_active');
            })
            ->count();

        // Compatibilite donnees legacy: certains anciens enregistrements n'ont pas de statut explicite.
        if ($activeProducts === 0 && $totalProducts > 0 && $inactiveProducts === 0) {
            $activeProducts = $totalProducts;
        }

        $shopRating = (float) ($user->vendeur?->rating ?? 0);
        if ($shopRating <= 0) {
            $shopRating = 4.8;
        }

        return [
            'monthlyRevenue' => round($monthlyRevenue, 2),
            'ordersCount' => (int) $orders->count(),
            'pendingCount' => $pendingCount,
            'totalProducts' => $totalProducts,
            'activeProducts' => $activeProducts,
            'outOfStockProducts' => (int) (clone $products)->where('stock', '<=', 0)->count(),
            'shopRating' => round($shopRating, 1),
        ];
    }

    public function getWeeklyRevenue(int $vendeurId, string $status = 'all', string $period = 'month'): array
    {
        $rows = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->selectRaw('orders.id, orders.created_at, SUM(order_items.subtotal) as vendor_total')
            ->where('order_items.vendeur_id', $vendeurId)
            ->whereIn('orders.status', ['pending', 'processing', 'shipped', 'delivered']);

        if ($status !== 'all') {
            $rows->where('orders.status', $status);
        }

        $this->applyPeriodFilter($rows, $period, 'orders.created_at');

        $orders = $rows
            ->groupBy('orders.id', 'orders.created_at')
            ->get();

        $buckets = [0, 0, 0, 0, 0];
        foreach ($orders as $row) {
            $day = (int) Carbon::parse($row->created_at)->day;
            $weekIndex = min(4, (int) floor(($day - 1) / 7));
            $buckets[$weekIndex] += (float) $row->vendor_total;
        }

        return collect($buckets)
            ->map(fn ($value, $index) => [
                'label' => 'S'.($index + 1),
                'value' => round((float) $value, 2),
            ])
            ->values()
            ->all();
    }

    public function getDestinations(int $vendeurId, string $status = 'all', string $period = 'month'): array
    {
        $orders = $this->baseOrdersQuery($vendeurId, $status, $period)
            ->get(['id', 'shipping_address']);

        $counts = [
            'Sénégal' => 0,
            'France' => 0,
            'USA' => 0,
            'Autres' => 0,
        ];

        foreach ($orders as $order) {
            $country = $this->countryFromAddress($order->shipping_address);
            $counts[$country] = ($counts[$country] ?? 0) + 1;
        }

        $total = array_sum($counts);

        $items = [];
        foreach ($counts as $name => $count) {
            $items[] = [
                'name' => $name,
                'count' => $count,
                'percent' => $total > 0 ? (int) round(($count / $total) * 100) : 0,
            ];
        }

        return [
            'total' => $total,
            'items' => $items,
        ];
    }

    public function getRecentOrders(
        int $vendeurId,
        string $status = 'all',
        string $period = 'month',
        int $page = 1,
        int $perPage = 6
    ): array {
        $paginated = $this->baseOrdersQuery($vendeurId, $status, $period)
            ->with([
                'user:id,name',
                'items' => function ($query) use ($vendeurId) {
                    $query->where('vendeur_id', $vendeurId)
                        ->with('product:id,name');
                },
            ])
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $orders = $paginated->getCollection()
            ->map(function ($order) {
                $vendorAmount = (float) $order->items->sum('subtotal');
                $productNames = $order->items
                    ->pluck('product.name')
                    ->filter()
                    ->unique()
                    ->values();
                $firstProduct = $productNames->first() ?? 'Produit';
                $otherProductsCount = max($productNames->count() - 1, 0);
                $productSummary = $otherProductsCount > 0
                    ? sprintf('%s (+%d autre%s)', $firstProduct, $otherProductsCount, $otherProductsCount > 1 ? 's' : '')
                    : $firstProduct;

                return [
                    'id' => $order->id,
                    'clientName' => $order->user?->name ?? 'Client',
                    'productName' => $firstProduct,
                    'productSummary' => $productSummary,
                    'productNames' => $productNames->all(),
                    'amount' => round($vendorAmount, 2),
                    'status' => $order->status,
                    'statusLabel' => $this->statusLabel($order->status),
                    'createdAt' => $order->created_at?->toISOString(),
                    'destination' => $this->countryFromAddress($order->shipping_address),
                    'shippingAddress' => $order->shipping_address,
                ];
            })
            ->values()
            ->all();

        return [
            'data' => $orders,
            'pagination' => [
                'page' => $paginated->currentPage(),
                'perPage' => $paginated->perPage(),
                'totalOrders' => $paginated->total(),
                'totalPages' => $paginated->lastPage(),
            ],
        ];
    }

    public function getTopProducts(int $vendeurId, string $status = 'all', string $period = 'month', int $limit = 5): array
    {
        $limit = min(max($limit, 1), 20);

        $query = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->join('products', 'products.id', '=', 'order_items.product_id')
            ->where('order_items.vendeur_id', $vendeurId)
            ->whereIn('orders.status', ['pending', 'processing', 'shipped', 'delivered'])
            ->selectRaw('products.id, products.name, SUM(order_items.quantity) as sales, SUM(order_items.subtotal) as revenue')
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('revenue');

        if ($status !== 'all') {
            $query->where('orders.status', $status);
        }

        $this->applyPeriodFilter($query, $period, 'orders.created_at');

        $rows = $query->limit($limit)->get();
        $maxRevenue = (float) ($rows->max('revenue') ?? 0);

        return $rows->map(function ($row) use ($maxRevenue) {
            $revenue = (float) $row->revenue;

            return [
                'name' => $row->name,
                'sales' => (int) $row->sales,
                'revenue' => round($revenue, 2),
                'progress' => $maxRevenue > 0 ? (int) round(($revenue / $maxRevenue) * 100) : 0,
            ];
        })->values()->all();
    }

    private function baseOrdersQuery(int $vendeurId, string $status = 'all', string $period = 'month'): Builder
    {
        $query = Order::query()
            ->whereHas('items', function ($subQuery) use ($vendeurId) {
                $subQuery->where('vendeur_id', $vendeurId);
            });

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        $this->applyPeriodFilter($query, $period, 'orders.created_at');

        return $query;
    }

    private function getPendingOrdersCount(int $vendeurId, string $period = 'month'): int
    {
        return (int) $this->baseOrdersQuery($vendeurId, 'all', $period)
            ->whereIn('status', ['pending', 'processing'])
            ->count();
    }

    private function applyPeriodFilter($query, string $period, string $column = 'created_at'): void
    {
        if ($period === 'all') {
            return;
        }

        if ($period === '7d') {
            $query->where($column, '>=', now()->subDays(7)->startOfDay());

            return;
        }

        if ($period === '30d') {
            $query->where($column, '>=', now()->subDays(30)->startOfDay());

            return;
        }

        // month (default)
        $query->where($column, '>=', now()->startOfMonth());
    }

    private function normalizeStatus(string $status): string
    {
        return in_array($status, self::VALID_STATUSES, true) ? $status : 'all';
    }

    private function normalizePeriod(string $period): string
    {
        return in_array($period, self::VALID_PERIODS, true) ? $period : 'month';
    }

    private function resolveVendeurId(User $user): int
    {
        if (! $user->isVendeur() || ! $user->vendeur) {
            throw ValidationException::withMessages([
                'role' => ['Seuls les vendeurs peuvent accéder à ce dashboard.'],
            ]);
        }

        return (int) $user->vendeur->id;
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'pending' => 'En attente',
            'processing' => 'Préparation',
            'shipped' => 'Expédié',
            'delivered' => 'Livré',
            'cancelled' => 'Annulé',
            'refunded' => 'Remboursé',
            default => 'En attente',
        };
    }

    private function countryFromAddress(?string $address): string
    {
        if (! $address) {
            return 'Autres';
        }

        $parts = explode(',', mb_strtolower($address));
        $country = trim((string) end($parts));

        if (in_array($country, ['sn', 'sénégal', 'senegal'], true)) {
            return 'Sénégal';
        }

        if (in_array($country, ['fr', 'france'], true)) {
            return 'France';
        }

        if (in_array($country, ['us', 'usa', 'états-unis', 'etats-unis'], true)) {
            return 'USA';
        }

        return 'Autres';
    }
}
