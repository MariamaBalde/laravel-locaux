<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\Vendeur;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;

class ProductSeeder extends Seeder
{
    private function cloudinaryConfig(): array
    {
        return [
            'cloud_name' => (string) (
                env('CLOUDINARY_CLOUD_NAME')
                ?: env('REACT_APP_CLOUDINARY_CLOUD_NAME')
            ),
            'upload_preset' => (string) (
                env('CLOUDINARY_UPLOAD_PRESET')
                ?: env('REACT_APP_CLOUDINARY_UPLOAD_PRESET')
            ),
            'folder' => (string) (
                env('CLOUDINARY_SEED_FOLDER') ?: 'app-produits-locaux/seed-products'
            ),
        ];
    }

    private function frontendAssetPath(string $filename): string
    {
        // Le backend est dans /backend, les assets frontend dans ../frontend/src/assets/home
        return base_path('../frontend/src/assets/home/'.ltrim($filename, '/'));
    }

    private function uploadAssetToCloudinary(string $filename, string $publicId): ?string
    {
        $config = $this->cloudinaryConfig();
        if ($config['cloud_name'] === '' || $config['upload_preset'] === '') {
            return null;
        }

        $path = $this->frontendAssetPath($filename);
        if (! is_file($path)) {
            return null;
        }

        $endpoint = sprintf(
            'https://api.cloudinary.com/v1_1/%s/image/upload',
            $config['cloud_name']
        );

        $handle = fopen($path, 'r');
        if (! $handle) {
            return null;
        }

        try {
            $response = Http::asMultipart()
                ->attach('file', $handle, basename($path))
                ->post($endpoint, [
                    'upload_preset' => $config['upload_preset'],
                    'folder' => $config['folder'],
                    'public_id' => $publicId,
                    'overwrite' => 'true',
                ]);
        } finally {
            fclose($handle);
        }

        if (! $response->successful()) {
            $this->command?->warn('Cloudinary upload échoué pour '.$filename.': '.$response->body());

            return null;
        }

        return (string) ($response->json('secure_url') ?: '');
    }

    private function resolveImageUrl(string $fallbackPath, string $frontendAsset, string $publicId): string
    {
        $cloudinaryUrl = $this->uploadAssetToCloudinary($frontendAsset, $publicId);
        if (! empty($cloudinaryUrl)) {
            return $cloudinaryUrl;
        }

        // Fallback local si Cloudinary non configuré / indisponible.
        return $fallbackPath;
    }

    public function run(): void
    {
        $vendeur1 = Vendeur::first();
        $vendeur2 = Vendeur::skip(1)->first();

        $arachidesNaturelles = Category::where('slug', 'arachides-naturelles')->first();
        $arachidesCaramel = Category::where('slug', 'arachides-caramelisees')->first();
        $confiseries = Category::where('slug', 'croquants')->first();
        $biscuits = Category::where('slug', 'biscuits')->first();

        $products = [

            [
                'vendeur_id' => $vendeur1->id,
                'category_id' => $arachidesNaturelles->id,
                'name' => 'Arachides Crues 500g',
                'description' => 'Arachides naturelles non grillées',
                'price' => 2000,
                'stock' => 100,
                'weight' => 0.5,
                'images' => [
                    $this->resolveImageUrl(
                        'products/arachides-crues.jpg',
                        'product-shell-peanuts.jpg',
                        'arachides-crues-500g'
                    ),
                ],
                'created_by' => $vendeur1->user_id,
            ],

            [
                'vendeur_id' => $vendeur1->id,
                'category_id' => $arachidesNaturelles->id,
                'name' => 'Arachides Grillées Salées 500g',
                'description' => 'Arachides grillées avec une touche de sel',
                'price' => 2500,
                'stock' => 80,
                'weight' => 0.5,
                'images' => [
                    $this->resolveImageUrl(
                        'products/arachides-grillees.jpg',
                        'product-golden-peanuts.jpg',
                        'arachides-grillees-salees-500g'
                    ),
                ],
                'created_by' => $vendeur1->user_id,
            ],

            [
                'vendeur_id' => $vendeur1->id,
                'category_id' => $arachidesCaramel->id,
                'name' => 'Arachides Caramélisées 250g',
                'description' => 'Arachides sucrées enrobées de caramel',
                'price' => 3000,
                'stock' => 60,
                'weight' => 0.25,
                'images' => [
                    $this->resolveImageUrl(
                        'products/arachides-caramel.jpg',
                        'product-caramel-bars.jpg',
                        'arachides-caramelisees-250g'
                    ),
                ],
                'created_by' => $vendeur1->user_id,
            ],

            [
                'vendeur_id' => $vendeur2->id,
                'category_id' => $confiseries->id,
                'name' => 'Croquants d’Arachide',
                'description' => 'Croquants traditionnels à base d’arachide',
                'price' => 1500,
                'stock' => 70,
                'weight' => 0.2,
                'images' => [
                    $this->resolveImageUrl(
                        'products/croquant-arachide.jpg',
                        'product-coated-peanuts.jpg',
                        'croquants-arachide'
                    ),
                ],
                'created_by' => $vendeur2->user_id,
            ],

            [
                'vendeur_id' => $vendeur2->id,
                'category_id' => $confiseries->id,
                'name' => 'Coco Caramélisé',
                'description' => 'Morceaux de coco enrobés de caramel',
                'price' => 2000,
                'stock' => 50,
                'weight' => 0.2,
                'images' => [
                    $this->resolveImageUrl(
                        'products/coco-caramel.jpg',
                        'coco.jpeg',
                        'coco-caramelise'
                    ),
                ],
                'created_by' => $vendeur2->user_id,
            ],

            [
                'vendeur_id' => $vendeur2->id,
                'category_id' => $biscuits->id,
                'name' => 'Biscuits Sablés Sénégalais',
                'description' => 'Biscuits sablés traditionnels faits maison',
                'price' => 2500,
                'stock' => 40,
                'weight' => 0.3,
                'images' => [
                    $this->resolveImageUrl(
                        'products/biscuits-sables.jpg',
                        'khertouba1.jpg',
                        'biscuits-sables-senegalais'
                    ),
                ],
                'created_by' => $vendeur2->user_id,
            ],

        ];

        foreach ($products as $product) {
            Product::updateOrCreate(
                [
                    'vendeur_id' => $product['vendeur_id'],
                    'name' => $product['name'],
                ],
                $product
            );
        }
    }
}
