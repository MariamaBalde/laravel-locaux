<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Product;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CartService
{
    /**
     * ═══════════════════════════════════════════════════════
     * RÉCUPÉRER LE PANIER D'UN UTILISATEUR
     * ═══════════════════════════════════════════════════════
     */
    public function getCart(User $user)
    {
        $cartItems = Cart::with(['product.vendeur.user', 'product.category'])
            ->where('user_id', $user->id)
            ->get();

        // Calculer les totaux
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $itemsCount = $cartItems->sum('quantity');

        return [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'items_count' => $itemsCount,
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * AJOUTER UN PRODUIT AU PANIER
     * ═══════════════════════════════════════════════════════
     */
    public function addToCart(User $user, array $data)
    {
        $productId = (int) $data['product_id'];
        $quantity = (int) $data['quantity'];

        // Vérifier que le produit existe et est actif
        $product = Product::find($productId);

        if (! $product || ! $product->is_active) {
            throw ValidationException::withMessages([
                'product' => ['Ce produit n\'est pas disponible.'],
            ]);
        }

        // Vérifier le stock disponible
        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Stock insuffisant. Disponible : '.$product->stock],
            ]);
        }

        // Vérifier si le produit est déjà dans le panier
        $cartItem = Cart::where('user_id', $user->id)
            ->where('product_id', $productId)
            ->first();

        if ($cartItem) {
            // Mettre à jour la quantité
            $newQuantity = $cartItem->quantity + $quantity;

            // Vérifier le stock pour la nouvelle quantité
            if ($product->stock < $newQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ['Stock insuffisant. Disponible : '.$product->stock],
                ]);
            }

            $cartItem->update(['quantity' => $newQuantity]);
            $message = 'Quantité mise à jour dans le panier';
        } else {
            // Ajouter au panier
            $cartItem = Cart::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
            $message = 'Produit ajouté au panier';
        }

        return [
            'message' => $message,
            'cart_item' => $cartItem->load('product'),
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * METTRE À JOUR LA QUANTITÉ D'UN ARTICLE
     * ═══════════════════════════════════════════════════════
     */
    public function updateCartItem(User $user, int $cartItemId, int $quantity)
    {
        $cartItem = Cart::where('user_id', $user->id)
            ->where('id', $cartItemId)
            ->first();

        if (! $cartItem) {
            throw ValidationException::withMessages([
                'cart_item' => ['Article non trouvé dans le panier.'],
            ]);
        }

        // Valider la quantité
        if ($quantity < 1) {
            throw ValidationException::withMessages([
                'quantity' => ['La quantité doit être au moins 1.'],
            ]);
        }

        // Vérifier le stock
        $product = $cartItem->product;
        if ($product->stock < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ['Stock insuffisant. Disponible : '.$product->stock],
            ]);
        }

        // Mettre à jour
        $cartItem->update(['quantity' => $quantity]);

        return [
            'message' => 'Quantité mise à jour',
            'cart_item' => $cartItem->load('product'),
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * SUPPRIMER UN ARTICLE DU PANIER
     * ═══════════════════════════════════════════════════════
     */
    public function removeFromCart(User $user, int $cartItemId)
    {
        $cartItem = Cart::where('user_id', $user->id)
            ->where('id', $cartItemId)
            ->first();

        if (! $cartItem) {
            throw ValidationException::withMessages([
                'cart_item' => ['Article non trouvé dans le panier.'],
            ]);
        }

        $cartItem->delete();

        return [
            'message' => 'Article supprimé du panier',
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * VIDER LE PANIER
     * ═══════════════════════════════════════════════════════
     */
    public function clearCart(User $user)
    {
        Cart::where('user_id', $user->id)->delete();

        return [
            'message' => 'Panier vidé avec succès',
        ];
    }

    /**
     * ═══════════════════════════════════════════════════════
     * VÉRIFIER LA DISPONIBILITÉ DU PANIER
     * ═══════════════════════════════════════════════════════
     */
    public function checkCartAvailability(User $user)
    {
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();
        $unavailableItems = [];
        $stockIssues = [];

        foreach ($cartItems as $item) {
            $product = $item->product;

            // Produit inactif ou supprimé
            if (! $product || ! $product->is_active) {
                $unavailableItems[] = [
                    'cart_item_id' => $item->id,
                    'product_name' => $product->name ?? 'Produit supprimé',
                    'reason' => 'Produit non disponible',
                ];

                continue;
            }

            // Stock insuffisant
            if ($product->stock < $item->quantity) {
                $stockIssues[] = [
                    'cart_item_id' => $item->id,
                    'product_name' => $product->name,
                    'requested' => $item->quantity,
                    'available' => $product->stock,
                ];
            }
        }

        return [
            'is_available' => empty($unavailableItems) && empty($stockIssues),
            'unavailable_items' => $unavailableItems,
            'stock_issues' => $stockIssues,
        ];
    }
}
