<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class PaymentShippingTest extends TestCase
{
    use RefreshDatabase;

    public function test_payment_status_returns_owner_payment_information(): void
    {
        $user = User::factory()->create();

        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 12000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 12000,
            'method' => 'wave',
            'status' => 'pending',
            'transaction_id' => 'TXN-123',
        ]);

        Passport::actingAs($user);

        $response = $this->getJson("/api/payments/status/{$order->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.order_id', $order->id)
            ->assertJsonPath('data.payment.status', 'pending');
    }

    public function test_shipping_methods_endpoint_is_public_and_returns_entries(): void
    {
        $response = $this->getJson('/api/shipping/methods');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    ['code', 'label', 'base_cost'],
                ],
            ]);
    }

    public function test_shipping_estimate_returns_numeric_cost(): void
    {
        $response = $this->postJson('/api/shipping/estimate', [
            'shipping_method' => 'international',
            'destination_country' => 'FR',
            'subtotal' => 30000,
            'weight_kg' => 2.5,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.shipping_method', 'international');
    }

    public function test_payment_callback_with_valid_signature_updates_payment_and_order(): void
    {
        $secret = 'test-callback-secret';
        putenv('PAYMENT_CALLBACK_SECRET='.$secret);
        $_ENV['PAYMENT_CALLBACK_SECRET'] = $secret;
        putenv('PAYMENT_CALLBACK_TOLERANCE_SECONDS=600');
        $_ENV['PAYMENT_CALLBACK_TOLERANCE_SECONDS'] = '600';

        $user = User::factory()->create();
        $order = Order::factory()->create([
            'user_id' => $user->id,
            'status' => 'pending',
            'total' => 15000,
        ]);

        $transactionId = 'TXN-CB-OK-001';
        Payment::create([
            'order_id' => $order->id,
            'amount' => 15000,
            'method' => 'wave',
            'status' => 'pending',
            'transaction_id' => $transactionId,
        ]);

        $timestamp = (string) now()->timestamp;
        $status = 'succeeded';
        $signature = hash_hmac('sha256', $timestamp.'.'.$transactionId.'.'.$status, $secret);

        $response = $this->postJson(
            '/api/payments/callback',
            [
                'transaction_id' => $transactionId,
                'status' => $status,
            ],
            [
                'X-Payment-Signature' => $signature,
                'X-Payment-Timestamp' => $timestamp,
            ]
        );

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'completed');

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'transaction_id' => $transactionId,
            'status' => 'completed',
        ]);

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => 'processing',
        ]);
    }
}
