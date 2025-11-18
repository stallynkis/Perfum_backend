<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        try {
            \Log::info('CategoryController@store - Inicio', [
                'name' => $request->input('name'),
                'has_image' => !empty($request->input('image')),
                'image_length' => $request->input('image') ? strlen($request->input('image')) : 0
            ]);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'image' => 'nullable|string|max:16777215', // Tamaño máximo MEDIUMTEXT
                'is_active' => 'nullable|boolean',
                'order' => 'nullable|integer',
            ]);

            if ($validator->fails()) {
                \Log::warning('CategoryController@store - Validación falló', ['errors' => $validator->errors()]);
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['description'] = $data['description'] ?? '';
            $data['image'] = $data['image'] ?? null;
            $data['order'] = $data['order'] ?? (Category::max('order') ?? 0) + 1;
            
            \Log::info('CategoryController@store - Creando categoría', ['data' => array_merge($data, ['image' => 'OMITTED'])]);
            
            $category = Category::create($data);
            
            \Log::info('CategoryController@store - Categoría creada', ['category_id' => $category->id]);
            
            return response()->json([
                'message' => 'Categoría creada exitosamente',
                'category' => $category
            ], 201);
        } catch (\Exception $e) {
            \Log::error('CategoryController@store - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al crear categoría: ' . $e->getMessage(),
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
        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['is_active'] = true;
            $category->update($data);
            return response()->json([
                'message' => 'Categoría actualizada exitosamente',
                'category' => $category
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
        try {
            $category->delete();
            return response()->json([
                'message' => 'Categoría eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar categoría',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
