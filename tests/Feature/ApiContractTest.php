<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ApiContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_route_is_rate_limited(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $refreshRoute = $routes->first(function ($route) {
            return in_array('POST', $route->methods(), true)
                && $route->uri() === 'api/auth/refresh';
        });

        $this->assertNotNull($refreshRoute);
        $this->assertContains('throttle:10,1', $refreshRoute->gatherMiddleware());
    }

    public function test_put_aliases_exist_for_order_cancel_and_seller_status(): void
    {
        $routes = collect(app('router')->getRoutes()->getRoutes());

        $hasPutOrderCancel = $routes->contains(function ($route) {
            return in_array('PUT', $route->methods(), true)
                && $route->uri() === 'api/orders/{id}/cancel';
        });

        $hasPutSellerStatus = $routes->contains(function ($route) {
            return in_array('PUT', $route->methods(), true)
                && $route->uri() === 'api/seller/orders/{id}/status';
        });

        $this->assertTrue($hasPutOrderCancel);
        $this->assertTrue($hasPutSellerStatus);
    }

    public function test_categories_index_is_paginated(): void
    {
        Category::factory()->count(5)->create();

        $response = $this->getJson('/api/categories?per_page=2');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 2)
            ->assertJsonCount(2, 'data');
    }

    public function test_user_addresses_and_payment_methods_are_paginated(): void
    {
        $user = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        Passport::actingAs($user);

        $user->addresses()->createMany([
            ['name' => 'Maison', 'address' => 'Dakar', 'city' => 'Dakar', 'is_default' => true],
            ['name' => 'Bureau', 'address' => 'Thiès', 'city' => 'Thiès', 'is_default' => false],
        ]);

        $user->paymentMethods()->createMany([
            ['provider' => 'wave', 'account_number' => '770000001', 'is_default' => true],
            ['provider' => 'orange_money', 'account_number' => '770000002', 'is_default' => false],
        ]);

        $this->getJson('/api/user/addresses?per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');

        $this->getJson('/api/user/payment-methods?per_page=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('meta.per_page', 1)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_users_endpoint_returns_client_metrics_from_full_history(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'country' => 'FR',
            'statut' => 'actif',
            'created_at' => now()->subDays(120),
        ]);

        $olderOrder = Order::factory()->create([
            'user_id' => $client->id,
            'status' => 'delivered',
            'total' => 25000,
            'created_at' => now()->subDays(40),
        ]);
        $olderOrder->payment()->create([
            'amount' => 25000,
            'method' => 'wave',
            'status' => 'completed',
            'transaction_id' => 'txn-old',
        ]);

        $latestOrder = Order::factory()->create([
            'user_id' => $client->id,
            'status' => 'delivered',
            'total' => 45000,
            'created_at' => now()->subDays(5),
        ]);
        $latestOrder->payment()->create([
            'amount' => 45000,
            'method' => 'orange_money',
            'status' => 'completed',
            'transaction_id' => 'txn-latest',
        ]);

        Passport::actingAs($admin);

        $response = $this->getJson('/api/admin/users?role=client&per_page=10');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.0.id', $client->id)
            ->assertJsonPath('data.0.client_metrics.orders_count', 2)
            ->assertJsonPath('data.0.client_metrics.total_spent', 70000)
            ->assertJsonPath('data.0.client_metrics.average_order_value', 35000)
            ->assertJsonPath('data.0.client_metrics.last_payment_method', 'orange_money')
            ->assertJsonPath('data.0.client_metrics.segment', 'Nouveau')
            ->assertJsonPath('data.0.client_metrics.source', 'full_history');
    }
}
