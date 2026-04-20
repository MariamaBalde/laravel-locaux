<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\InitiatePaymentRequest;
use App\Http\Requests\PaymentCallbackRequest;
use App\Http\Resources\DataResource;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function __construct(private PaymentService $paymentService) {}

    /**
     * POST /api/payments/initiate
     */
    public function initiate(InitiatePaymentRequest $request): JsonResponse
    {
        try {
            $result = $this->paymentService->initiate($request->user(), $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Paiement initié',
                'data' => new DataResource($result),
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
                'message' => 'Erreur lors de l\'initialisation du paiement',
            ], 500);
        }
    }

    /**
     * POST /api/payments/callback
     */
    public function callback(PaymentCallbackRequest $request): JsonResponse
    {
        try {
            $result = $this->paymentService->handleCallback(
                $request->validated(),
                $request->header('X-Payment-Signature', $request->input('signature')),
                $request->header('X-Payment-Timestamp', $request->input('timestamp'))
            );

            return response()->json([
                'success' => true,
                'message' => 'Callback paiement traité',
                'data' => new DataResource($result),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Callback invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du traitement du callback',
            ], 500);
        }
    }

    /**
     * GET /api/payments/status/{orderId}
     */
    public function status(Request $request, int $orderId): JsonResponse
    {
        try {
            $status = $this->paymentService->getOrderPaymentStatus($request->user(), $orderId);

            return response()->json([
                'success' => true,
                'data' => new DataResource($status),
            ], 200);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Requête invalide',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération du statut de paiement',
            ], 500);
        }
    }
}
