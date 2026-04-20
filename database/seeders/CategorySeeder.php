<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Arachides Naturelles',
                'slug' => 'arachides-naturelles',
                'description' => 'Arachides crues, grillées ou salées',
            ],
            [
                'name' => 'Arachides Caramélisées',
                'slug' => 'arachides-caramelisees',
                'description' => 'Arachides sucrées, croquants et arachides au caramel',
            ],
            [
                'name' => 'Croquants',
                'slug' => 'croquants',
                'description' => 'Croquants d’arachide, coco caramélisé et bonbons locaux',
            ],
            [
                'name' => 'Biscuits',
                'slug' => 'biscuits',
                'description' => 'Biscuits sablés sénégalais et autres pâtisseries locales',
            ],
            [
                'name' => 'Snacks Traditionnels',
                'slug' => 'snacks-traditionnels',
                'description' => 'Snacks et gourmandises traditionnelles africaines',
            ],
        ];
        foreach ($categories as $category) {
            Category::create($category);
        }
    }
}
