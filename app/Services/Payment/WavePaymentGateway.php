<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Support\Str;

class WavePaymentGateway implements PaymentGatewayInterface
{
    public function initiate(Payment $payment, Order $order, array $context = []): array
    {
        $frontendUrl = rtrim((string) ($context['frontend_url'] ?? env('FRONTEND_URL', 'http://localhost:3000')), '/');
        $reference = 'WAVE-'.now()->format('YmdHis').'-'.strtoupper(Str::random(6));
        $paymentUrl = $frontendUrl.'/orders/'.$order->id.'/confirmation?payment_ref='.$reference.'&gateway=wave';

        return [
            'provider' => 'wave',
            'provider_reference' => $reference,
            'payment_url' => $paymentUrl,
            'status' => 'pending',
        ];
    }
}
