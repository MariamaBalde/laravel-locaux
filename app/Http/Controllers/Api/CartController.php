<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\AddToCartRequest;
use App\Http\Requests\UpdateCartItemRequest;
use App\Services\Cart\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    protected $cartService;

    public function __construct(CartService $cartService)
    {
        $this->cartService = $cartService;
    }

    private function isStockConflict(array $errors): bool
    {
        foreach ($errors as $messages) {
            foreach ((array) $messages as $message) {
                $normalized = mb_strtolower((string) $message);
                if (str_contains($normalized, 'stock insuffisant')
                    || str_contains($normalized, 'n\'est pas disponible')
                    || str_contains($normalized, 'non disponible')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Récupère le panier de l'utilisateur
     * GET /api/cart
     */
    private function formatCartItem($item)
    {
        return [
            'id' => $item->id,
            'product_id' => $item->product_id,
            'quantity' => $item->quantity,
            'product' => [
                'id' => $item->product->id,
                'name' => $item->product->name,
                'price' => number_format($item->product->price, 2, '.', ''),
            ],
            'subtotal' => $item->product->price * $item->quantity,
        ];
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $cart = $this->cartService->getCart($request->user());

            $items = collect($cart['items'])->map(function ($item) {
                return $this->formatCartItem($item);
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'items' => $items,
                    'subtotal' => $cart['subtotal'],
                    'items_count' => $cart['items_count'],
                ],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du panier',
            ], 500);
        }
    }

    /**
     * Ajoute un produit au panier
     * POST /api/cart
     */
    public function store(AddToCartRequest $request): JsonResponse
    {
        try {
            $result = $this->cartService->addToCart($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $this->formatCartItem($result['cart_item']),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $status = $this->isStockConflict($errors) ? 409 : 422;

            return response()->json([
                'success' => false,
                'message' => $status === 409 ? 'Conflit de stock' : 'Erreur de validation',
                'errors' => $errors,
            ], $status);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'ajout au panier',
            ], 500);
        }
    }

    /**
     * Met à jour la quantité d'un article
     * PATCH /api/cart/{id}
     */
    public function update(UpdateCartItemRequest $request, int $id): JsonResponse
    {
        try {
            $validated = $request->validated();

            $result = $this->cartService->updateCartItem(
                $request->user(),
                $id,
                (int) $validated['quantity']
            );

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => $this->formatCartItem($result['cart_item']),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $status = $this->isStockConflict($errors) ? 409 : 422;

            return response()->json([
                'success' => false,
                'message' => $status === 409 ? 'Conflit de stock' : 'Erreur de validation',
                'errors' => $errors,
            ], $status);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour',
            ], 500);
        }
    }

    /**
     * Supprime un article du panier
     * DELETE /api/cart/{id}
     */
    public function destroy(Request $request, int $id): JsonResponse
    {
        try {
            $result = $this->cartService->removeFromCart($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
            ], 500);
        }
    }

    /**
     * Vide le panier
     * DELETE /api/cart
     */
    public function clear(Request $request): JsonResponse
    {
        try {
            $result = $this->cartService->clearCart($request->user());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du vidage du panier',
            ], 500);
        }
    }

    /**
     * Vérifie la disponibilité du panier
     * GET /api/cart/check
     */
    public function check(Request $request): JsonResponse
    {
        try {
            $result = $this->cartService->checkCartAvailability($request->user());

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);

        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la vérification',
            ], 500);
        }
    }
}
