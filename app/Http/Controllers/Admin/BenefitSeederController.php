<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Http\Request;

class BenefitSeederController extends Controller
{
    public function seedDefaultBenefits()
    {
        // Verificar si ya existen beneficios
        $existingCount = Benefit::count();
        
        if ($existingCount > 0) {
            return response()->json([
                'message' => 'Ya existen beneficios en la base de datos',
                'count' => $existingCount
            ]);
        }

        // Beneficios por defecto
        $defaultBenefits = [
            [
                'title' => 'Calidad Premium',
                'description' => 'Fragancias auténticas de las mejores marcas',
                'icon' => 'Award',
                'order' => 1,
                'is_active' => true
            ],
            [
                'title' => 'Atención Personalizada',
                'description' => 'Te ayudamos a encontrar tu fragancia perfecta',
                'icon' => 'Heart',
                'order' => 2,
                'is_active' => true
            ],
            [
                'title' => 'Garantía de Satisfacción',
                'description' => '30 días de garantía en todos nuestros productos',
                'icon' => 'Star',
                'order' => 3,
                'is_active' => true
            ],
            [
                'title' => 'Envíos Rápidos',
                'description' => 'Recibe tus fragancias en tiempo récord',
                'icon' => 'Truck',
                'order' => 4,
                'is_active' => true
            ]
        ];

        // Crear cada beneficio
        foreach ($defaultBenefits as $benefitData) {
            Benefit::create($benefitData);
        }

        return response()->json([
            'message' => 'Beneficios por defecto creados exitosamente',
            'count' => count($defaultBenefits)
        ], 201);
    }
}
