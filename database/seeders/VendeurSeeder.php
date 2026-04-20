<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Vendeur;
use Illuminate\Database\Seeder;

class VendeurSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $vendeurs = User::where('role', 'vendeur')->get();

        $shops = [
            [
                'shop_name' => 'Terranga Foods',
                'description' => 'Spécialiste des produits alimentaires sénégalais authentiques. Arachides, céréales, épices et plus encore.',
                'verified' => true,
                'rating' => 4.8,
                'total_sales' => 125000,
            ],
            [
                'shop_name' => 'Délices du Sénégal',  // ← CHANGÉ
                'description' => 'Confiseries et douceurs traditionnelles sénégalaises. Croquants, bonbons et friandises artisanales.',
                'verified' => true,
                'rating' => 4.6,
                'total_sales' => 85000,
            ],
            [
                'shop_name' => 'Pâtisserie Dakaroise',  // ← CHANGÉ
                'description' => 'Biscuits et pâtisseries sénégalaises faits maison. Recettes traditionnelles et authentiques.',
                'verified' => true,
                'rating' => 4.9,
                'total_sales' => 95000,
            ],
        ];

        foreach ($vendeurs as $index => $vendeur) {
            Vendeur::create([
                'user_id' => $vendeur->id,
                'shop_name' => $shops[$index]['shop_name'],
                'description' => $shops[$index]['description'],
                'verified' => $shops[$index]['verified'],
                'rating' => $shops[$index]['rating'],
                'total_sales' => $shops[$index]['total_sales'],
            ]);
        }
    }
}
