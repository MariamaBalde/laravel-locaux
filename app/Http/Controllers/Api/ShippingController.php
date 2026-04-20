<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\ShippingEstimateRequest;
use App\Http\Requests\UpdateOrderShippingRequest;
use App\Http\Resources\DataCollection;
use App\Http\Resources\DataResource;
use App\Http\Resources\OrderResource;
use App\Services\Order\OrderService;
use App\Services\Shipping\ShippingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class ShippingController extends Controller
{
    public function __construct(
        private ShippingService $shippingService,
        private OrderService $orderService
    ) {}

    public function methods(): JsonResponse
    {
        try {
            return response()->json([
                'success' => true,
                'data' => new DataCollection(collect($this->shippingService->methods())),
            ], 200);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération des méthodes de livraison.',
            ], 500);
        }
    }

    public function estimate(ShippingEstimateRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();
            $cost = $this->shippingService->estimate(
                $validated['shipping_method'],
                $validated['destination_country'],
                (float) $validated['subtotal'],
                isset($validated['weight_kg']) ? (float) $validated['weight_kg'] : null
            );

            return response()->json([
                'success' => true,
                'data' => new DataResource([
                    'shipping_method' => $validated['shipping_method'],
                    'destination_country' => strtoupper($validated['destination_country']),
                    'estimated_cost' => $cost,
                    'currency' => 'XOF',
                ]),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'estimation des frais de livraison.',
            ], 500);
        }
    }

    public function updateOrderShipping(UpdateOrderShippingRequest $request, int $id): JsonResponse
    {
        try {
            $order = $this->orderService->updateOrderShipping($request->user(), $id, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Livraison mise à jour.',
                'data' => new OrderResource($order),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la mise à jour de la livraison.',
            ], 500);
        }
    }
}
