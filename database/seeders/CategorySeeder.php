<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'HOMBRES',
                'description' => 'Perfumes y fragancias para hombres',
                'image' => '',
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'MUJERES',
                'description' => 'Perfumes y fragancias para mujeres',
                'image' => '',
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'UNISEX',
                'description' => 'Perfumes y fragancias unisex',
                'image' => '',
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'NIÑOS',
                'description' => 'Perfumes y fragancias para niños',
                'image' => '',
                'order' => 4,
                'is_active' => true
            ]
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['name' => $category['name']],
                $category
            );
        }

        echo "✅ Categorías creadas correctamente\n";
    }
}
