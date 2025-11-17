<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Brand;

class BrandSeeder extends Seeder
{
    public function run(): void
    {
        $brands = [
            [
                'name' => 'DIOR',
                'description' => 'Marca de lujo francesa',
                'image' => '',
                'order' => 1,
                'is_active' => true
            ],
            [
                'name' => 'CHANEL',
                'description' => 'Marca de lujo francesa',
                'image' => '',
                'order' => 2,
                'is_active' => true
            ],
            [
                'name' => 'CAROLINA HERRERA',
                'description' => 'Marca de lujo venezolana-americana',
                'image' => '',
                'order' => 3,
                'is_active' => true
            ],
            [
                'name' => 'VERSACE',
                'description' => 'Marca de lujo italiana',
                'image' => '',
                'order' => 4,
                'is_active' => true
            ],
            [
                'name' => 'PACO RABANNE',
                'description' => 'Marca de lujo española',
                'image' => '',
                'order' => 5,
                'is_active' => true
            ]
        ];

        foreach ($brands as $brand) {
            Brand::updateOrCreate(
                ['name' => $brand['name']],
                $brand
            );
        }

        echo "✅ Marcas creadas correctamente\n";
    }
}
