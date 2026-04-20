<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_payment_denies_non_owner_non_admin(): void
    {
        $owner = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $otherUser = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'CMD-2026-000001',
            'total' => 15000,
            'status' => 'pending',
            'shipping_method' => 'standard',
            'shipping_address' => 'Dakar',
            'shipping_cost' => 3000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 15000,
            'method' => 'wave',
            'status' => 'pending',
        ]);

        Passport::actingAs($otherUser);

        $response = $this->postJson("/api/orders/{$order->id}/confirm-payment", [
            'transaction_id' => 'TXN-TEST-1',
        ]);

        $response
            ->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonStructure(['errors' => ['permission']]);
    }

    public function test_confirm_payment_allows_owner_and_updates_status(): void
    {
        $owner = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $order = Order::create([
            'user_id' => $owner->id,
            'order_number' => 'CMD-2026-000002',
            'total' => 18000,
            'status' => 'pending',
            'shipping_method' => 'standard',
            'shipping_address' => 'Dakar',
            'shipping_cost' => 3000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 18000,
            'method' => 'wave',
            'status' => 'pending',
        ]);

        Passport::actingAs($owner);

        $response = $this->postJson("/api/orders/{$order->id}/confirm-payment", [
            'transaction_id' => 'TXN-TEST-2',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.payment.status', 'completed');
    }
}
