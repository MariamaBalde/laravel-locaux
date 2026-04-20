<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use Tests\TestCase;

class VendorDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_vendor_dashboard_overview_returns_only_connected_vendor_data(): void
    {
        $category = Category::create([
            'name' => 'Snacks',
            'slug' => 'snacks',
        ]);

        $vendorUser = User::factory()->create([
            'role' => 'vendeur',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $otherVendorUser = User::factory()->create([
            'role' => 'vendeur',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $vendor = Vendeur::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Boutique V1',
            'verified' => true,
            'rating' => 4.7,
            'total_sales' => 0,
        ]);

        $otherVendor = Vendeur::create([
            'user_id' => $otherVendorUser->id,
            'shop_name' => 'Boutique V2',
            'verified' => true,
            'rating' => 4.2,
            'total_sales' => 0,
        ]);

        $vendorProduct = Product::create([
            'vendeur_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Arachides grillées',
            'description' => 'Produit test V1',
            'price' => 6000,
            'stock' => 10,
            'is_active' => true,
            'created_by' => $vendorUser->id,
        ]);

        $otherVendorProduct = Product::create([
            'vendeur_id' => $otherVendor->id,
            'category_id' => $category->id,
            'name' => 'Barres caramel',
            'description' => 'Produit test V2',
            'price' => 5000,
            'stock' => 8,
            'is_active' => true,
            'created_by' => $otherVendorUser->id,
        ]);

        $order = Order::create([
            'user_id' => $client->id,
            'order_number' => 'CMD-2026-100001',
            'total' => 17000,
            'status' => 'pending',
            'shipping_method' => 'standard',
            'shipping_address' => 'Dakar, Sénégal',
            'shipping_cost' => 3000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $vendorProduct->id,
            'vendeur_id' => $vendor->id,
            'quantity' => 2,
            'price' => 6000,
            'subtotal' => 12000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $otherVendorProduct->id,
            'vendeur_id' => $otherVendor->id,
            'quantity' => 1,
            'price' => 5000,
            'subtotal' => 5000,
        ]);

        Passport::actingAs($vendorUser);

        $response = $this->getJson('/api/vendor/dashboard/overview?period=all');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.stats.ordersCount', 1)
            ->assertJsonPath('data.stats.monthlyRevenue', 12000)
            ->assertJsonPath('data.recentOrders.0.amount', 12000)
            ->assertJsonPath('data.recentOrders.0.productName', 'Arachides grillées');
    }

    public function test_vendor_dashboard_endpoints_are_forbidden_for_non_vendor(): void
    {
        $client = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        Passport::actingAs($client);

        $this->getJson('/api/vendor/dashboard/overview')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    public function test_vendor_order_detail_returns_only_vendor_items(): void
    {
        $category = Category::create([
            'name' => 'Snacks 2',
            'slug' => 'snacks-2',
        ]);

        $vendorUser = User::factory()->create(['role' => 'vendeur', 'country' => 'SN', 'statut' => 'actif']);
        $otherVendorUser = User::factory()->create(['role' => 'vendeur', 'country' => 'SN', 'statut' => 'actif']);
        $client = User::factory()->create(['role' => 'client', 'country' => 'SN', 'statut' => 'actif']);

        $vendor = Vendeur::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Boutique A',
            'verified' => true,
            'rating' => 4.6,
            'total_sales' => 0,
        ]);

        $otherVendor = Vendeur::create([
            'user_id' => $otherVendorUser->id,
            'shop_name' => 'Boutique B',
            'verified' => true,
            'rating' => 4.3,
            'total_sales' => 0,
        ]);

        $productA = Product::create([
            'vendeur_id' => $vendor->id,
            'category_id' => $category->id,
            'name' => 'Produit A',
            'description' => 'A',
            'price' => 4000,
            'stock' => 10,
            'is_active' => true,
            'created_by' => $vendorUser->id,
        ]);

        $productB = Product::create([
            'vendeur_id' => $otherVendor->id,
            'category_id' => $category->id,
            'name' => 'Produit B',
            'description' => 'B',
            'price' => 7000,
            'stock' => 8,
            'is_active' => true,
            'created_by' => $otherVendorUser->id,
        ]);

        $order = Order::create([
            'user_id' => $client->id,
            'order_number' => 'CMD-2026-200001',
            'total' => 15000,
            'status' => 'processing',
            'shipping_method' => 'standard',
            'shipping_address' => 'Dakar, Sénégal',
            'shipping_cost' => 3000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productA->id,
            'vendeur_id' => $vendor->id,
            'quantity' => 2,
            'price' => 4000,
            'subtotal' => 8000,
        ]);

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $productB->id,
            'vendeur_id' => $otherVendor->id,
            'quantity' => 1,
            'price' => 7000,
            'subtotal' => 7000,
        ]);

        Passport::actingAs($vendorUser);

        $response = $this->getJson("/api/vendor/orders/{$order->id}");

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.summary.vendor_subtotal', 8000)
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.product_name', 'Produit A');
    }
}
