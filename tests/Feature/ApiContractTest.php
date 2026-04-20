<?php

namespace Tests\Feature;

use App\Models\Category;
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
}
