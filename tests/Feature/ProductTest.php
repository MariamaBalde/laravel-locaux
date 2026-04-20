<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_products_index_returns_only_active_products(): void
    {
        $category = Category::create([
            'name' => 'Snacks',
            'slug' => 'snacks',
        ]);

        $user = User::factory()->create([
            'role' => 'vendeur',
            'country' => 'SN',
            'statut' => 'actif',
        ]);

        $vendeur = Vendeur::create([
            'user_id' => $user->id,
            'shop_name' => 'Boutique Test',
            'verified' => true,
            'rating' => 0,
            'total_sales' => 0,
        ]);

        Product::create([
            'vendeur_id' => $vendeur->id,
            'category_id' => $category->id,
            'name' => 'Produit Actif',
            'description' => 'Description',
            'price' => 1000,
            'stock' => 10,
            'is_active' => true,
            'created_by' => $user->id,
        ]);

        Product::create([
            'vendeur_id' => $vendeur->id,
            'category_id' => $category->id,
            'name' => 'Produit Inactif',
            'description' => 'Description',
            'price' => 2000,
            'stock' => 5,
            'is_active' => false,
            'created_by' => $user->id,
        ]);

        $response = $this->getJson('/api/products');

        $response
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.name', 'Produit Actif');
    }

    public function test_products_show_returns_404_for_unknown_product(): void
    {
        $response = $this->getJson('/api/products/999999');

        $response
            ->assertStatus(404)
            ->assertJsonPath('success', false);
    }
}
