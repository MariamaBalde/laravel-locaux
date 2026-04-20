<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ChecklistCoverageTest extends TestCase
{
    use RefreshDatabase;

    private function createVerifiedVendor(string $email = 'vendor@test.local'): array
    {
        $vendorUser = User::factory()->create([
            'name' => 'Vendor User',
            'email' => $email,
            'password' => bcrypt('Password123!'),
            'role' => 'vendeur',
            'country' => 'SN',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        $vendeur = Vendeur::create([
            'user_id' => $vendorUser->id,
            'shop_name' => 'Boutique Test',
            'verified' => true,
            'rating' => 4.7,
            'total_sales' => 0,
        ]);

        return [$vendorUser, $vendeur];
    }

    private function ensurePassportPersonalAccessClient(): void
    {
        $provider = config('auth.guards.api.provider', 'users');
        $exists = Client::query()
            ->where('revoked', false)
            ->get()
            ->contains(fn (Client $client) => $client->hasGrantType('personal_access'));

        if (! $exists) {
            app(ClientRepository::class)->createPersonalAccessGrantClient(
                'Test Personal Access Client',
                $provider
            );
        }
    }

    public function test_auth_checklist_endpoints_are_covered(): void
    {
        $register = $this->postJson('/api/register', [
            'name' => 'Client Register',
            'email' => 'client.register@test.local',
            'password' => 'Password123!',
            'password_confirmation' => 'Password123!',
            'role' => 'client',
            'country' => 'SN',
        ]);
        $register->assertStatus(201);

        $loginUser = User::factory()->create([
            'email' => 'client.login@test.local',
            'password' => bcrypt('Password123!'),
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        $this->ensurePassportPersonalAccessClient();

        $this->postJson('/api/login', [
            'email' => 'client.login@test.local',
            'password' => 'Password123!',
        ])->assertOk();

        Passport::actingAs($loginUser);

        $this->postJson('/api/logout')->assertOk();
        $this->getJson('/api/me')->assertOk();

        $this->postJson('/api/password/forgot', [
            'email' => 'client.login@test.local',
        ])->assertOk();

        $this->postJson('/api/password/reset', [
            'email' => 'client.login@test.local',
            'token' => 'invalid-token',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ])->assertStatus(422);

        $loginUser->update(['email_verified_at' => null]);
        $this->postJson('/api/email/verify')->assertOk();
    }

    public function test_products_checklist_endpoints_are_covered(): void
    {
        [$vendorUser, $vendeur] = $this->createVerifiedVendor('vendor.products@test.local');

        $category = Category::create([
            'name' => 'Arachides',
            'slug' => 'arachides',
            'description' => 'Catégorie test',
        ]);

        Passport::actingAs($vendorUser);

        $create = $this->postJson('/api/seller/products', [
            'category_id' => $category->id,
            'name' => 'Produit Checklist',
            'description' => 'Produit test',
            'price' => 5000,
            'stock' => 25,
            'weight' => 0.5,
            'image_urls' => ['https://example.com/image.jpg'],
            'is_active' => true,
        ])->assertStatus(201);

        $productId = $create->json('data.id');

        $this->getJson('/api/products')->assertOk();
        $this->getJson("/api/products/{$productId}")->assertOk();

        $this->putJson("/api/seller/products/{$productId}", [
            'name' => 'Produit Checklist MAJ',
            'price' => 6500,
        ])->assertOk();

        $this->postJson("/api/seller/products/{$productId}/images", [
            'image_urls' => ['https://example.com/image2.jpg'],
        ])->assertOk();

        $this->getJson('/api/categories')->assertOk();

        $this->deleteJson("/api/seller/products/{$productId}")->assertOk();

        // Éviter variable inutilisée en assertions implicites liées au vendeur
        $this->assertEquals($vendeur->id, $vendorUser->vendeur->id);
    }

    public function test_orders_checklist_endpoints_are_covered(): void
    {
        [$vendorUser, $vendeur] = $this->createVerifiedVendor('vendor.orders@test.local');

        $client = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        $category = Category::create([
            'name' => 'Snacks',
            'slug' => 'snacks',
        ]);

        $product = Product::create([
            'vendeur_id' => $vendeur->id,
            'category_id' => $category->id,
            'name' => 'Produit Commande',
            'description' => 'desc',
            'price' => 4000,
            'stock' => 30,
            'is_active' => true,
            'created_by' => $vendorUser->id,
        ]);

        Cart::create([
            'user_id' => $client->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ]);

        Passport::actingAs($client);

        $createOrder = $this->postJson('/api/orders', [
            'shipping_address' => 'Dakar, Sénégal',
            'shipping_method' => 'standard',
            'payment_method' => 'wave',
        ])->assertStatus(201);

        $orderId = $createOrder->json('data.order.id')
            ?? $createOrder->json('data.order.data.id');

        $this->getJson('/api/orders')->assertOk();
        $this->getJson("/api/orders/{$orderId}")->assertOk();
        $this->putJson("/api/orders/{$orderId}/cancel")->assertOk();

        $sellerOrder = Order::create([
            'user_id' => $client->id,
            'order_number' => 'CMD-2026-CHECK-LIST',
            'total' => 11000,
            'status' => 'pending',
            'shipping_method' => 'standard',
            'shipping_address' => 'Dakar, Sénégal',
            'shipping_cost' => 3000,
        ]);

        OrderItem::create([
            'order_id' => $sellerOrder->id,
            'product_id' => $product->id,
            'vendeur_id' => $vendeur->id,
            'quantity' => 2,
            'price' => 4000,
            'subtotal' => 8000,
        ]);

        Payment::create([
            'order_id' => $sellerOrder->id,
            'amount' => 11000,
            'method' => 'wave',
            'status' => 'pending',
        ]);

        Passport::actingAs($vendorUser);

        $this->getJson('/api/seller/orders')->assertOk();
        $this->putJson("/api/seller/orders/{$sellerOrder->id}/status", [
            'status' => 'confirme',
        ])->assertOk();
    }

    public function test_payment_shipping_vendor_admin_checklist_endpoints_are_covered(): void
    {
        [$vendorUser, $vendeur] = $this->createVerifiedVendor('vendor.misc@test.local');

        $admin = User::factory()->create([
            'role' => 'admin',
            'country' => 'SN',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        $client = User::factory()->create([
            'role' => 'client',
            'country' => 'SN',
            'statut' => 'actif',
            'email_verified_at' => now(),
        ]);

        $order = Order::create([
            'user_id' => $client->id,
            'order_number' => 'CMD-2026-PAY-001',
            'total' => 15000,
            'status' => 'pending',
            'shipping_method' => 'standard',
            'shipping_address' => 'Dakar, Sénégal',
            'shipping_cost' => 3000,
        ]);

        Payment::create([
            'order_id' => $order->id,
            'amount' => 15000,
            'method' => 'wave',
            'status' => 'pending',
            'transaction_id' => 'TXN-CHECK-001',
        ]);

        Passport::actingAs($client);

        $this->postJson('/api/payments/initiate', ['order_id' => $order->id])->assertOk();
        $this->getJson('/api/payments/status/'.$order->id)->assertOk();

        $this->putJson('/api/orders/'.$order->id.'/shipping', [
            'shipping_method' => 'express',
            'shipping_address' => 'Thiès, Sénégal',
            'destination_country' => 'SN',
            'weight_kg' => 1.2,
        ])->assertOk();

        $this->getJson('/api/shipping/methods')->assertOk();
        $this->postJson('/api/shipping/estimate', [
            'shipping_method' => 'standard',
            'destination_country' => 'SN',
            'subtotal' => 12000,
            'weight_kg' => 1,
        ])->assertOk();

        $this->getJson('/api/payments/callback')->assertStatus(422);

        Passport::actingAs($vendorUser);
        $this->getJson('/api/seller/profile')->assertOk();
        $this->putJson('/api/seller/profile', ['phone' => '+221770000000'])->assertOk();
        $this->getJson('/api/seller/dashboard')->assertOk();

        Passport::actingAs($admin, [], 'api');
        $this->assertTrue($admin->isAdmin());
        $this->getJson('/api/admin/users')->assertOk();
        $this->putJson('/api/admin/users/'.$client->id.'/status')->assertOk();
        $this->getJson('/api/admin/orders')->assertOk();
        $this->getJson('/api/admin/stats')->assertOk();

        $this->assertTrue($vendeur->fresh()->verified);
    }
}
