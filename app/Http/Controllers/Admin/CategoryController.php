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
        \Log::info('CategoryController@store - Request received', ['data' => $request->all()]);
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string',
                'order' => 'nullable|integer',
                'is_active' => 'boolean'
            ]);

            \Log::info('CategoryController@store - Validation passed', ['validated' => $validated]);

            // Establecer valores por defecto
            $validated['description'] = $validated['description'] ?? '';
            $validated['image'] = $validated['image'] ?? null;
            $validated['order'] = $validated['order'] ?? (Category::max('order') ?? 0) + 1;
            $validated['is_active'] = $validated['is_active'] ?? true;

            $category = Category::create($validated);

            \Log::info('CategoryController@store - Category created', ['category' => $category]);

            return response()->json([
                'category' => $category,
                'message' => 'Categoría creada exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('CategoryController@store - Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('CategoryController@store - Error', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al crear categoría',
                'error' => $e->getMessage()
            ], 500);
        }
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

            $validated['is_active'] = true;
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
