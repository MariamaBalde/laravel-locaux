<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $clientMetrics = $this->role === 'client'
            ? $this->buildClientMetrics()
            : null;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'statut' => $this->statut,
            'country' => $this->country,
            'phone' => $this->phone,
            'email_verified_at' => $this->email_verified_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'client_metrics' => $clientMetrics,
            'vendeur' => $this->whenLoaded('vendeur', function () {
                return [
                    'id' => $this->vendeur?->id,
                    'shop_name' => $this->vendeur?->shop_name,
                    'verified' => (bool) ($this->vendeur?->verified ?? false),
                    'rating' => $this->vendeur?->rating,
                    'total_sales' => $this->vendeur?->total_sales,
                ];
            }),
        ];
    }

    private function buildClientMetrics(): array
    {
        $ordersCount = (int) ($this->orders_count ?? 0);
        $totalSpent = (float) ($this->total_spent ?? 0);
        $averageOrderValue = (float) ($this->average_order_value ?? 0);
        $lastOrderAt = $this->last_order_at;
        $rawCountry = strtoupper((string) ($this->country ?: 'SN'));
        $isDiaspora = $rawCountry !== 'SN';

        $createdAt = $this->created_at;
        $accountAgeDays = $createdAt ? now()->diffInDays($createdAt) : null;
        $daysSinceOrder = $lastOrderAt ? now()->diffInDays($lastOrderAt) : null;

        $isInactive = $daysSinceOrder !== null
            ? $daysSinceOrder > 90
            : (($accountAgeDays ?? 9999) > 45);

        $isNew = (($accountAgeDays ?? 9999) <= 30) || $ordersCount <= 2;
        $isVip = $ordersCount >= 10 || $totalSpent >= 120000;

        $segment = 'Local actif';
        if ($isInactive) {
            $segment = 'Inactif';
        } elseif ($isNew) {
            $segment = 'Nouveau';
        } elseif ($isDiaspora) {
            $segment = 'Diaspora';
        }

        if ($isVip && ! $isInactive) {
            $segment = 'VIP';
        }

        $fidelityPoints = max(1, min(5, (int) round($totalSpent / 45000) ?: 1));
        $fidelityProgress = max(0, min(100, ($totalSpent / 220000) * 100));

        return [
            'orders_count' => $ordersCount,
            'total_spent' => $totalSpent,
            'average_order_value' => $averageOrderValue,
            'last_order_at' => $lastOrderAt,
            'last_payment_method' => $this->last_payment_method,
            'segment' => $segment,
            'country_code' => $rawCountry,
            'is_diaspora' => $isDiaspora,
            'is_inactive' => $isInactive,
            'is_new' => $isNew,
            'is_vip' => $isVip,
            'fidelity_points' => $fidelityPoints,
            'fidelity_progress' => round($fidelityProgress, 2),
            'source' => 'full_history',
        ];
    }
}
