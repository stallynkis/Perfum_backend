<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    // Endpoint público para categorías activas
    public function publicIndex()
    {
        $categories = Category::where('is_active', true)
            ->orderBy('order')
            ->get();
        return response()->json(['data' => $categories]);
    }

    public function index()
    {
        $categories = Category::orderBy('order')->get();
        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'order' => 'nullable|integer',
            'is_active' => 'nullable|boolean'
        ]);

        // Establecer valores por defecto
        $validated['description'] = $validated['description'] ?? '';
        $validated['image'] = $validated['image'] ?? '';
        $validated['order'] = $validated['order'] ?? Category::max('order') + 1;
        $validated['is_active'] = $validated['is_active'] ?? true;

        $category = Category::create($validated);

        return response()->json([
            'category' => $category,
            'message' => 'Categoría creada exitosamente'
        ], 201);
    }

    public function show(Category $category)
    {
        return response()->json(['data' => $category]);
    }

    public function update(Request $request, Category $category)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'order' => 'nullable|integer',
                'is_active' => 'nullable|boolean'
            ]);

            $category->update($validated);

            return response()->json([
                'category' => $category,
                'message' => 'Categoría actualizada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
}
