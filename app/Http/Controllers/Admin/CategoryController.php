<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        $categories = Category::orderBy('order')->get();
        return response()->json(['data' => $categories]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'image' => 'required|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);

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
        $validated = $request->validate([
            'name' => 'string|max:255',
            'description' => 'string',
            'image' => 'string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);

        $category->update($validated);

        return response()->json([
            'category' => $category,
            'message' => 'Categoría actualizada exitosamente'
        ]);
    }

    public function destroy(Category $category)
    {
        $category->delete();

        return response()->json([
            'message' => 'Categoría eliminada exitosamente'
        ]);
    }
}
