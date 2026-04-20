<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class PaymentService
{
    public function __construct(
        private WavePaymentGateway $waveGateway,
        private OrangeMoneyGateway $orangeMoneyGateway,
        private StripePaymentGateway $stripeGateway
    ) {}

    /**
     * Initialise un paiement pour une commande existante.
     */
    public function initiate(User $user, array $data): array
    {
        $order = Order::with('payment')->findOrFail((int) $data['order_id']);

        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à initier ce paiement.'],
            ]);
        }

        if (! $order->payment) {
            throw ValidationException::withMessages([
                'payment' => ['Aucun paiement associé à cette commande.'],
            ]);
        }

        $payment = $order->payment;

        if ($payment->status === 'completed') {
            return [
                'order_id' => $order->id,
                'payment_id' => $payment->id,
                'method' => $payment->method,
                'status' => 'completed',
                'payment_url' => null,
                'provider_reference' => $payment->transaction_id,
            ];
        }

        if (in_array($order->status, ['cancelled', 'refunded'], true)) {
            throw ValidationException::withMessages([
                'order' => ['Impossible de payer une commande annulée ou remboursée.'],
            ]);
        }

        $gatewayResult = $this->resolveGateway((string) $payment->method)->initiate($payment, $order, [
            'frontend_url' => env('FRONTEND_URL', 'http://localhost:3000'),
        ]);

        $payment->update([
            'transaction_id' => $gatewayResult['provider_reference'],
            'gateway_response' => json_encode($gatewayResult, JSON_UNESCAPED_UNICODE),
            'status' => $gatewayResult['status'] ?? 'pending',
        ]);

        return [
            'order_id' => $order->id,
            'payment_id' => $payment->id,
            'method' => $payment->method,
            'status' => $payment->status,
            'payment_url' => $gatewayResult['payment_url'],
            'provider_reference' => $gatewayResult['provider_reference'],
        ];
    }

    /**
     * Callback provider: met à jour le statut de paiement et de commande.
     */
    public function handleCallback(array $payload, ?string $signature = null, ?string $timestamp = null): array
    {
        $this->validateCallbackSignature($payload, $signature, $timestamp);

        $payment = Payment::with('order')
            ->where('transaction_id', $payload['transaction_id'])
            ->first();

        if (! $payment) {
            throw ValidationException::withMessages([
                'transaction_id' => ['Transaction introuvable.'],
            ]);
        }

        $mappedStatus = match ($payload['status']) {
            'succeeded' => 'completed',
            'failed', 'cancelled' => 'failed',
            default => 'pending',
        };

        $payment->update([
            'status' => $mappedStatus,
            'paid_at' => $mappedStatus === 'completed' ? now() : null,
            'gateway_response' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        if ($payment->order) {
            if ($mappedStatus === 'completed' && $payment->order->status === 'pending') {
                $payment->order->update(['status' => 'processing']);
            }

            if ($mappedStatus === 'failed' && $payment->order->status === 'processing') {
                $payment->order->update(['status' => 'pending']);
            }
        }

        return [
            'payment_id' => $payment->id,
            'order_id' => $payment->order_id,
            'status' => $payment->status,
        ];
    }

    public function getOrderPaymentStatus(User $user, int $orderId): array
    {
        $order = Order::with('payment')->findOrFail($orderId);

        if (! $user->isAdmin() && $order->user_id !== $user->id) {
            throw ValidationException::withMessages([
                'permission' => ['Vous n\'êtes pas autorisé à consulter ce paiement.'],
            ]);
        }

        return [
            'order_id' => $order->id,
            'order_status' => $order->status,
            'payment' => $order->payment ? [
                'id' => $order->payment->id,
                'method' => $order->payment->method,
                'status' => $order->payment->status,
                'transaction_id' => $order->payment->transaction_id,
                'paid_at' => $order->payment->paid_at,
            ] : null,
        ];
    }

    private function resolveGateway(string $method): PaymentGatewayInterface
    {
        return match ($method) {
            'wave' => $this->waveGateway,
            'orange_money' => $this->orangeMoneyGateway,
            'stripe', 'visa', 'paypal' => $this->stripeGateway,
            default => $this->waveGateway,
        };
    }

    /**
     * Vérifie la signature HMAC du callback provider.
     * Format signé: "{timestamp}.{transaction_id}.{status}".
     */
    private function validateCallbackSignature(array $validated, ?string $signature, ?string $timestamp): void
    {
        $secret = (string) env('PAYMENT_CALLBACK_SECRET', '');
        $isProduction = app()->environment('production');

        if ($secret === '') {
            if ($isProduction) {
                throw ValidationException::withMessages([
                    'signature' => ['PAYMENT_CALLBACK_SECRET manquant en production.'],
                ]);
            }

            // En local/dev: on autorise pour faciliter les tests manuels.
            return;
        }

        if (! $signature || ! $timestamp) {
            throw ValidationException::withMessages([
                'signature' => ['Signature callback manquante.'],
            ]);
        }

        if (! ctype_digit((string) $timestamp)) {
            throw ValidationException::withMessages([
                'signature' => ['Timestamp callback invalide.'],
            ]);
        }

        $toleranceSeconds = max((int) env('PAYMENT_CALLBACK_TOLERANCE_SECONDS', 300), 30);

        $now = Carbon::now()->timestamp;
        $ts = (int) $timestamp;
        if (abs($now - $ts) > $toleranceSeconds) {
            throw ValidationException::withMessages([
                'signature' => ['Callback expiré (fenêtre de validité dépassée).'],
            ]);
        }

        $signedPayload = $timestamp.'.'.$validated['transaction_id'].'.'.$validated['status'];
        $expected = hash_hmac('sha256', $signedPayload, $secret);

        if (! hash_equals($expected, (string) $signature)) {
            throw ValidationException::withMessages([
                'signature' => ['Signature callback invalide.'],
            ]);
        }
    }
}
