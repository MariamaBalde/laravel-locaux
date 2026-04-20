<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    public function create(User $user): bool
    {
        return $user->isAdmin() || $user->isVendeur();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin() || $product->vendeur_id === $user->vendeur?->id;
    }

    public function delete(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function toggle(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }

    public function updateStock(User $user, Product $product): bool
    {
        return $this->update($user, $product);
    }
}
