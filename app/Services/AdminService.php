<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;

class AdminService
{
    /**
     * ═══════════════════════════════════════════════════════
     * STATISTIQUES GÉNÉRALES (DASHBOARD)
     * ═══════════════════════════════════════════════════════
     */
    public function getDashboardStats()
    {
        // Statistiques globales
        $stats = [
            'total_users' => User::count(),
            'total_clients' => User::where('role', 'client')->count(),
            'total_vendeurs' => User::where('role', 'vendeur')->count(),
            'total_admins' => User::where('role', 'admin')->count(),

            'total_products' => Product::count(),
            'active_products' => Product::where('is_active', true)->count(),
            'out_of_stock' => Product::where('stock', 0)->count(),

            'total_categories' => Category::count(),

            'total_orders' => Order::count(),
            'pending_orders' => Order::where('status', 'pending')->count(),
            'processing_orders' => Order::where('status', 'processing')->count(),
            'completed_orders' => Order::where('status', 'delivered')->count(),
            'cancelled_orders' => Order::where('status', 'cancelled')->count(),

            'total_revenue' => Order::whereIn('status', ['processing', 'shipped', 'delivered'])
                ->sum('total'),
            'revenue_this_month' => Order::whereIn('status', ['processing', 'shipped', 'delivered'])
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->sum('total'),

            'pending_vendeurs' => Vendeur::where('verified', false)->count(),
        ];

        return $stats;
    }

    /**
     * ═══════════════════════════════════════════════════════
     * VENTES PAR MOIS (pour graphique)
     * ═══════════════════════════════════════════════════════
     */
    public function getSalesPerMonth(?int $year = null)
    {
        $year = $year ?? now()->year;

        $sales = Order::selectRaw('MONTH(created_at) as month, SUM(total) as total, COUNT(*) as count')
            ->whereYear('created_at', $year)
            ->whereIn('status', ['processing', 'shipped', 'delivered'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        // Remplir les mois manquants avec 0
        $monthlySales = [];
        for ($i = 1; $i <= 12; $i++) {
            $monthData = $sales->firstWhere('month', $i);
            $monthlySales[] = [
                'month' => $i,
                'month_name' => date('F', mktime(0, 0, 0, $i, 1)),
                'total' => $monthData ? (float) $monthData->total : 0,
                'orders_count' => $monthData ? $monthData->count : 0,
            ];
        }

        return $monthlySales;
    }

    /**
     * ═══════════════════════════════════════════════════════
     * PRODUITS PAR CATÉGORIE (pour graphique)
     * ═══════════════════════════════════════════════════════
     */
    public function getProductsByCategory()
    {
        return Category::withCount('products')
            ->orderBy('products_count', 'desc')
            ->get()
            ->map(function ($category) {
                return [
                    'category' => $category->name,
                    'count' => $category->products_count,
                ];
            });
    }

    /**
     * ═══════════════════════════════════════════════════════
     * TOP VENDEURS
     * ═══════════════════════════════════════════════════════
     */
    public function getTopVendors(array $filters = [])
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return Vendeur::with('user')
            ->orderBy('total_sales', 'desc')
            ->paginate($perPage)
            ->through(function ($vendeur) {
                return [
                    'id' => $vendeur->id,
                    'shop_name' => $vendeur->shop_name,
                    'user_name' => $vendeur->user->name,
                    'total_sales' => $vendeur->total_sales,
                    'rating' => $vendeur->rating,
                    'verified' => $vendeur->verified,
                ];
            });
    }

    /**
     * ═══════════════════════════════════════════════════════
     * DERNIÈRES COMMANDES
     * ═══════════════════════════════════════════════════════
     */
    public function getRecentOrders(array $filters = [])
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return Order::with(['user', 'payment'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * DERNIERS UTILISATEURS INSCRITS
     * ═══════════════════════════════════════════════════════
     */
    public function getRecentUsers(array $filters = [])
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return User::with('vendeur')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * VENDEURS EN ATTENTE DE VÉRIFICATION
     * ═══════════════════════════════════════════════════════
     */
    public function getPendingVendors(array $filters = [])
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return Vendeur::with('user')
            ->where('verified', false)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * APPROUVER UN VENDEUR
     * ═══════════════════════════════════════════════════════
     */
    public function approveVendor(int $vendeurId, User $admin)
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent approuver des vendeurs.'],
            ]);
        }

        $vendeur = Vendeur::with('user')->find($vendeurId);

        if (! $vendeur) {
            throw ValidationException::withMessages([
                'vendeur' => ['Vendeur non trouvé.'],
            ]);
        }

        if ($vendeur->verified) {
            throw ValidationException::withMessages([
                'vendeur' => ['Ce vendeur est déjà vérifié.'],
            ]);
        }

        $vendeur->update(['verified' => true]);

        // TODO: Envoyer email de confirmation au vendeur
        // Mail::to($vendeur->user->email)->send(new VendorApproved($vendeur));

        return [
            'message' => 'Vendeur approuvé avec succès',
            'vendeur' => $vendeur,
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * REJETER UN VENDEUR
     * ═══════════════════════════════════════════════════════
     */
    public function rejectVendor(int $vendeurId, User $admin, ?string $reason = null)
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent rejeter des vendeurs.'],
            ]);
        }

        $vendeur = Vendeur::with('user')->find($vendeurId);

        if (! $vendeur) {
            throw ValidationException::withMessages([
                'vendeur' => ['Vendeur non trouvé.'],
            ]);
        }

        // TODO: Envoyer email de rejet au vendeur avec raison
        // Mail::to($vendeur->user->email)->send(new VendorRejected($vendeur, $reason));

        // Supprimer le vendeur et l'utilisateur
        $vendeur->user->delete(); // Cascade delete sur vendeur

        return [
            'message' => 'Vendeur rejeté et supprimé',
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * SUSPENDRE/ACTIVER UN UTILISATEUR
     * ═══════════════════════════════════════════════════════
     */
    public function toggleUserStatus(int $userId, User $admin)
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent modifier le statut des utilisateurs.'],
            ]);
        }

        $user = User::find($userId);

        if (! $user) {
            throw ValidationException::withMessages([
                'user' => ['Utilisateur non trouvé.'],
            ]);
        }

        // Ne peut pas suspendre un autre admin
        if ($user->isAdmin() && $user->id !== $admin->id) {
            throw ValidationException::withMessages([
                'user' => ['Impossible de modifier le statut d\'un autre administrateur.'],
            ]);
        }

        // Toggle entre actif et suspendu
        $newStatus = $user->statut === 'actif' ? 'suspendu' : 'actif';
        $user->update(['statut' => $newStatus]);

        return [
            'message' => 'Statut utilisateur modifié',
            'user' => $user,
            'new_status' => $newStatus,
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * STATISTIQUES AVANCÉES
     * ═══════════════════════════════════════════════════════
     */
    public function getAdvancedStats()
    {
        return [
            // Taux de conversion
            'conversion_rate' => $this->calculateConversionRate(),

            // Panier moyen
            'average_order_value' => Order::whereIn('status', ['processing', 'shipped', 'delivered'])
                ->avg('total'),

            // Produit le plus vendu
            'top_product' => $this->getTopProduct(),

            // Catégorie la plus vendue
            'top_category' => $this->getTopCategory(),

            // Évolution du CA (comparaison mois actuel vs mois précédent)
            'revenue_growth' => $this->calculateRevenueGrowth(),
        ];
    }

    protected function calculateConversionRate()
    {
        $totalUsers = User::where('role', 'client')->count();
        $usersWithOrders = User::where('role', 'client')
            ->whereHas('orders')
            ->count();

        return $totalUsers > 0 ? round(($usersWithOrders / $totalUsers) * 100, 2) : 0;
    }

    protected function getTopProduct()
    {
        $topProductId = DB::table('order_items')
            ->select('product_id', DB::raw('SUM(quantity) as total_sold'))
            ->groupBy('product_id')
            ->orderBy('total_sold', 'desc')
            ->first();

        if ($topProductId) {
            return Product::find($topProductId->product_id);
        }

        return null;
    }

    protected function getTopCategory()
    {
        $topCategoryId = DB::table('products')
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->select('products.category_id', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->groupBy('products.category_id')
            ->orderBy('total_sold', 'desc')
            ->first();

        if ($topCategoryId) {
            return Category::find($topCategoryId->category_id);
        }

        return null;
    }

    protected function calculateRevenueGrowth()
    {
        $currentMonth = Order::whereIn('status', ['processing', 'shipped', 'delivered'])
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        $lastMonth = Order::whereIn('status', ['processing', 'shipped', 'delivered'])
            ->whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');

        if ($lastMonth > 0) {
            $growth = (($currentMonth - $lastMonth) / $lastMonth) * 100;

            return round($growth, 2);
        }

        return 0;
    }

    /**
     * ═══════════════════════════════════════════════════════
     * LISTE DE TOUS LES UTILISATEURS (avec filtres)
     * ═══════════════════════════════════════════════════════
     */
    public function getAllUsers(array $filters = [])
    {
        $query = User::with('vendeur');

        // Filtre par rôle
        if (isset($filters['role'])) {
            $query->where('role', $filters['role']);
        }

        // Filtre par statut
        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        // Recherche
        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('name', 'like', '%'.$filters['search'].'%')
                    ->orWhere('email', 'like', '%'.$filters['search'].'%');
            });
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = $filters['sort_order'] ?? 'desc';
        $query->orderBy($sortBy, $sortOrder);

        // Pagination
        $perPage = $filters['per_page'] ?? 20;

        return $query->paginate($perPage);
    }

    /**
     * ═══════════════════════════════════════════════════════
     * GESTION PRODUITS (ADMIN)
     * ═══════════════════════════════════════════════════════
     */
    public function getAllProducts(array $filters = [])
    {
        $query = Product::with(['vendeur.user', 'category']);

        if (isset($filters['is_active'])) {
            $isActive = filter_var($filters['is_active'], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
            if ($isActive !== null) {
                $query->where('is_active', $isActive);
            }
        }

        if (! empty($filters['search'])) {
            $term = trim((string) $filters['search']);
            $query->where(function ($q) use ($term) {
                $q->where('name', 'like', '%'.$term.'%')
                    ->orWhere('description', 'like', '%'.$term.'%')
                    ->orWhereHas('vendeur', function ($vq) use ($term) {
                        $vq->where('shop_name', 'like', '%'.$term.'%');
                    });
            });
        }

        if (! empty($filters['category_id'])) {
            $query->where('category_id', (int) $filters['category_id']);
        }

        if (! empty($filters['vendeur_id'])) {
            $query->where('vendeur_id', (int) $filters['vendeur_id']);
        }

        if (! empty($filters['stock_status'])) {
            if ($filters['stock_status'] === 'in_stock') {
                $query->where('stock', '>', 5);
            } elseif ($filters['stock_status'] === 'low_stock') {
                $query->whereBetween('stock', [1, 5]);
            } elseif ($filters['stock_status'] === 'out_of_stock') {
                $query->where('stock', '<=', 0);
            }
        }

        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortOrder = strtolower((string) ($filters['sort_order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['created_at', 'updated_at', 'price', 'stock', 'name'];
        if (! in_array($sortBy, $allowedSorts, true)) {
            $sortBy = 'created_at';
        }
        $query->orderBy($sortBy, $sortOrder);

        $limit = max(1, min((int) ($filters['limit'] ?? 100), 200));

        return $query->limit($limit)->get()->map(function (Product $product) {
            return $this->enrichProductData($product);
        })->values();
    }

    public function updateProductStatus(int $productId, string $status, User $admin): array
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent modifier le statut des produits.'],
            ]);
        }

        if (! in_array($status, ['active', 'inactive'], true)) {
            throw ValidationException::withMessages([
                'status' => ['Le statut doit être active ou inactive.'],
            ]);
        }

        $product = Product::find($productId);
        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        $product->update([
            'is_active' => $status === 'active',
            'updated_by' => $admin->id,
        ]);

        return [
            'message' => $status === 'active' ? 'Produit activé' : 'Produit désactivé',
            'data' => $this->enrichProductData($product->fresh(['vendeur.user', 'category'])),
        ];
    }

    public function toggleFeaturedProduct(int $productId, User $admin): array
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent mettre un produit en avant.'],
            ]);
        }

        if (! Schema::hasColumn('products', 'featured')) {
            throw ValidationException::withMessages([
                'featured' => ['La colonne featured est absente. Lancez la migration dédiée.'],
            ]);
        }

        $product = Product::find($productId);
        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        $product->update([
            'featured' => ! ((bool) $product->featured),
            'updated_by' => $admin->id,
        ]);

        return [
            'message' => $product->featured ? 'Produit mis en avant' : 'Produit retiré de la mise en avant',
            'data' => $this->enrichProductData($product->fresh(['vendeur.user', 'category'])),
        ];
    }

    public function deleteProduct(int $productId, User $admin): array
    {
        if (! $admin->isAdmin()) {
            throw ValidationException::withMessages([
                'permission' => ['Seuls les administrateurs peuvent supprimer des produits.'],
            ]);
        }

        $product = Product::find($productId);
        if (! $product) {
            throw ValidationException::withMessages([
                'product' => ['Produit non trouvé.'],
            ]);
        }

        $hasPendingOrders = DB::table('order_items')
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->where('order_items.product_id', $product->id)
            ->whereIn('orders.status', ['pending'])
            ->exists();

        if ($hasPendingOrders) {
            throw ValidationException::withMessages([
                'product' => ['Impossible de supprimer ce produit: il est lié à des commandes en attente.'],
            ]);
        }

        $product->delete();

        return ['message' => 'Produit supprimé avec succès'];
    }

    private function enrichProductData(Product $product): array
    {
        $orderItems = $product->orderItems()
            ->whereHas('order', function ($q) {
                $q->whereIn('status', ['processing', 'shipped', 'delivered']);
            })
            ->with(['order.user:id,country'])
            ->get(['id', 'order_id', 'quantity', 'product_id']);

        $salesCount = (int) $orderItems->sum('quantity');
        $orderCount = (int) $orderItems->pluck('order_id')->filter()->unique()->count();
        $diasporaOrderCount = (int) $orderItems
            ->filter(function ($item) {
                return $this->isDiasporaOrder($item->order);
            })
            ->pluck('order_id')
            ->filter()
            ->unique()
            ->count();

        $diasporaPercent = $orderCount > 0
            ? round(($diasporaOrderCount / $orderCount) * 100, 2)
            : 0;

        $satisfaction = (float) ($product->vendeur?->rating ?? 0);

        $stockStatus = 'in_stock';
        $stockColor = 'green';
        if ((int) $product->stock <= 0) {
            $stockStatus = 'out_of_stock';
            $stockColor = 'red';
        } elseif ((int) $product->stock <= 5) {
            $stockStatus = 'low_stock';
            $stockColor = 'orange';
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => (float) $product->price,
            'stock' => (int) $product->stock,
            'stock_status' => $stockStatus,
            'stock_color' => $stockColor,
            'images' => $product->images ?? [],
            'category' => [
                'id' => $product->category?->id,
                'name' => $product->category?->name,
            ],
            'vendor' => [
                'id' => $product->vendeur?->id,
                'name' => $product->vendeur?->shop_name,
                'user_id' => $product->vendeur?->user_id,
                'rating' => (float) ($product->vendeur?->rating ?? 0),
                'verified' => (bool) ($product->vendeur?->verified ?? false),
                'pending_review' => ! ((bool) ($product->vendeur?->verified ?? false)),
            ],
            'is_active' => (bool) $product->is_active,
            'featured' => (bool) ($product->featured ?? false),
            'performance' => [
                'sales' => $salesCount,
                'satisfaction' => round($satisfaction, 1),
                'diaspora_percent' => $diasporaPercent,
            ],
            'created_at' => $product->created_at,
            'updated_at' => $product->updated_at,
        ];
    }

    private function isDiasporaOrder(?Order $order): bool
    {
        if (! $order) {
            return false;
        }

        $country = strtoupper((string) ($order->user?->country ?? ''));
        if ($country !== '') {
            return ! in_array($country, ['SN', 'SENEGAL', 'SÉNÉGAL'], true);
        }

        $address = strtolower((string) $order->shipping_address);
        if ($address === '') {
            return false;
        }

        return strpos($address, 'senegal') === false && strpos($address, 'sénégal') === false;
    }
}
