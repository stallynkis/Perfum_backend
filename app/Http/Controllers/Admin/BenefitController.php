<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    // Endpoint público para beneficios activos
    public function publicIndex()
    {
        $benefits = Benefit::where('is_active', true)
            ->orderBy('order')
            ->get();
        return response()->json(['data' => $benefits]);
    }

    public function index()
    {
        $benefits = Benefit::orderBy('order')->get();
        return response()->json(['data' => $benefits]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'icon' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        // Establecer valores por defecto
        $validated['description'] = $validated['description'] ?? '';
        $validated['icon'] = $validated['icon'] ?? 'star';
        $validated['order'] = $validated['order'] ?? Benefit::max('order') + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $benefit = Benefit::create($validated);

        return response()->json([
            'benefit' => $benefit,
            'message' => 'Beneficio creado exitosamente'
        ], 201);
    }

    public function show(Benefit $benefit)
    {
        return response()->json(['data' => $benefit]);
    }

    public function update(Request $request, Benefit $benefit)
    {
        try {
            $validated = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'icon' => 'nullable|string',
                'order' => 'nullable|integer',
                'is_active' => 'nullable|boolean'
            ]);

            $benefit->update($validated);

            return response()->json([
                'benefit' => $benefit,
                'message' => 'Beneficio actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar beneficio',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Benefit $benefit)
    {
        $benefit->delete();

        return response()->json([
            'message' => 'Beneficio eliminado exitosamente'
        ]);
    }
}
