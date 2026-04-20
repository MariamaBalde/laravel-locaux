<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ConfirmOrderPaymentRequest;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderCollection;
use App\Http\Resources\OrderResource;
use App\Http\Resources\PaymentResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    protected $orderService;

    public function __construct(OrderService $orderService)
    {
        $this->orderService = $orderService;
    }

    private function isStockConflict(array $errors): bool
    {
        foreach ($errors as $messages) {
            foreach ((array) $messages as $message) {
                $normalized = mb_strtolower((string) $message);
                if (str_contains($normalized, 'stock insuffisant')
                    || str_contains($normalized, 'n\'est plus disponible')
                    || str_contains($normalized, 'produit non disponible')
                ) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Crée une commande depuis le panier
     * POST /api/orders
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        try {
            $this->authorize('create', Order::class);

            $result = $this->orderService->createOrderFromCart(
                $request->user(),
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Commande créée avec succès',
                'data' => [
                    'order' => new OrderResource($result['order']),
                    'payment' => new PaymentResource($result['payment']),
                ],
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            $errors = $e->errors();
            $status = $this->isStockConflict($errors) ? 409 : 422;

            return response()->json([
                'success' => false,
                'message' => $status === 409 ? 'Conflit de stock' : 'Erreur de validation',
                'errors' => $errors,
            ], $status);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => [
                    'permission' => ['Action non autorisée.'],
                ],
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la commande',
            ], 500);
        }
    }

    /**
     * Liste les commandes de l'utilisateur
     * GET /api/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('viewAny', Order::class);
            $filters = $request->all();
            $orders = $this->orderService->getUserOrders($request->user(), $filters);

            return response()->json([
                'success' => true,
                'data' => new OrderCollection($orders),
            ], 200);

        } catch (\Exception $e) {
            report($e);
            if ($e instanceof AuthorizationException) {
                return response()->json([
                    'success' => false,
                    'message' => 'Erreur',
                    'errors' => [
                        'permission' => ['Action non autorisée.'],
                    ],
                ], 403);
            }

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes',
            ], 500);
        }
    }

    /**
     * Affiche une commande spécifique
     * GET /api/orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->getOrderById($request->user(), $id);
            $this->authorize('view', $order);

            return response()->json([
                'success' => true,
                'data' => new OrderResource($order),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
                'errors' => $e->errors(),
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => [
                    'permission' => ['Action non autorisée.'],
                ],
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande',
            ], 500);
        }
    }

    /**
     * Annule une commande
     * PATCH /api/orders/{id}/cancel
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $orderModel = Order::find($id);
            if ($orderModel) {
                $this->authorize('cancel', $orderModel);
            }

            $result = $this->orderService->cancelOrder($request->user(), $id);

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new OrderResource($result['order']),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => [
                    'permission' => ['Action non autorisée.'],
                ],
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation',
            ], 500);
        }
    }

    /**
     * Confirme le paiement d'une commande
     * POST /api/orders/{id}/confirm-payment
     */
    public function confirmPayment(ConfirmOrderPaymentRequest $request, int $id): JsonResponse
    {
        try {
            $orderModel = Order::find($id);
            if ($orderModel) {
                $this->authorize('confirmPayment', $orderModel);
            }

            $result = $this->orderService->confirmPayment($request->user(), $id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'data' => new OrderResource($result['order']),
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur',
                'errors' => [
                    'permission' => ['Action non autorisée.'],
                ],
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la confirmation du paiement',
            ], 500);
        }
    }
}
