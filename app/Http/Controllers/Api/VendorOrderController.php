<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateVendorOrderStatusRequest;
use App\Http\Requests\UpdateVendorTrackingRequest;
use App\Http\Resources\VendorOrderResource;
use App\Models\Order;
use App\Services\Order\OrderService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VendorOrderController extends Controller
{
    public function __construct(private OrderService $orderService) {}

    /**
     * GET /api/seller/orders
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $this->authorize('vendor.orders.viewAny');
            $orders = $this->orderService->getVendorOrders($request->user(), $request->all());

            return response()->json([
                'success' => true,
                'data' => VendorOrderResource::collection($orders),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé',
                'errors' => $e->errors(),
            ], 403);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des commandes vendeur',
            ], 500);
        }
    }

    /**
     * GET /api/vendor/orders/{id}
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $orderModel = Order::query()
                ->with(['items:id,order_id,vendeur_id'])
                ->findOrFail($id);
            $this->authorize('vendor.orders.view', $orderModel);

            $order = $this->orderService->getVendorOrderById($request->user(), $id);

            return response()->json([
                'success' => true,
                'data' => new VendorOrderResource($order),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
                'errors' => $e->errors(),
            ], 404);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la commande vendeur',
            ], 500);
        }
    }

    /**
     * PATCH /api/vendor/orders/{id}/status
     */
    public function updateStatus(UpdateVendorOrderStatusRequest $request, int $id): JsonResponse
    {
        try {
            $orderModel = Order::query()
                ->with(['items:id,order_id,vendeur_id'])
                ->findOrFail($id);
            $this->authorize('vendor.orders.updateStatus', $orderModel);

            $validated = $request->validated();
            $statusMap = [
                'en_attente' => 'pending',
                'confirme' => 'processing',
                'expedie' => 'shipped',
                'livre' => 'delivered',
            ];
            $normalizedStatus = $statusMap[$validated['status']] ?? $validated['status'];

            $order = $this->orderService->updateVendorOrderStatus(
                $request->user(),
                $id,
                $normalizedStatus
            );

            return response()->json([
                'success' => true,
                'message' => 'Statut de la commande mis à jour.',
                'data' => new VendorOrderResource($order),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du statut de commande vendeur',
            ], 500);
        }
    }

    /**
     * PATCH /api/vendor/orders/{id}/tracking
     */
    public function updateTracking(UpdateVendorTrackingRequest $request, int $id): JsonResponse
    {
        try {
            $orderModel = Order::query()
                ->with(['items:id,order_id,vendeur_id'])
                ->findOrFail($id);
            $this->authorize('vendor.orders.updateTracking', $orderModel);

            $validated = $request->validated();

            $order = $this->orderService->updateVendorTracking(
                $request->user(),
                $id,
                $validated['tracking_number']
            );

            return response()->json([
                'success' => true,
                'message' => 'Numéro de suivi mis à jour.',
                'data' => new VendorOrderResource($order),
            ]);
        } catch (ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Commande non trouvée',
            ], 404);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Action non autorisée',
            ], 403);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour du suivi vendeur',
            ], 500);
        }
    }
}
