<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class VendorOrderPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isVendeur() && $user->vendeur()->exists();
    }

    public function view(User $user, Order $order): bool
    {
        $vendor = $user->vendeur()->first();

        if (! $user->isVendeur() || ! $vendor) {
            return false;
        }

        if ($order->relationLoaded('items')) {
            return $order->items->contains(fn ($item) => (int) $item->vendeur_id === (int) $vendor->id);
        }

        return $order->items()->where('vendeur_id', $vendor->id)->exists();
    }

    public function updateStatus(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }

    public function updateTracking(User $user, Order $order): bool
    {
        return $this->view($user, $order);
    }
}
