<?php

namespace App\Services\Payment;

use App\Models\Order;
use App\Models\Payment;

interface PaymentGatewayInterface
{
    /**
     * Initialise une session de paiement et renvoie l'URL de redirection.
     *
     * @return array{
     *   provider:string,
     *   provider_reference:string,
     *   payment_url:string,
     *   status:string
     * }
     */
    public function initiate(Payment $payment, Order $order, array $context = []): array;
}
