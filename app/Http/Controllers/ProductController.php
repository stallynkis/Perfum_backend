<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Product::query();

        // Filtro por productos destacados
        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }

        // Filtro por categoría
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filtro por estado activo (solo mostrar activos al público)
        if ($request->has('active_only') && $request->active_only === 'true') {
            $query->where('is_active', true);
        }

        // Optimización: solo seleccionar campos necesarios para dashboard
        if ($request->has('fields') && $request->fields === 'minimal') {
            $query->select('id', 'name', 'stock', 'price', 'is_active', 'category', 'brand');
        }

        // Ordenar
        $query->orderBy('created_at', 'desc');

        // Paginación opcional para grandes volúmenes
        if ($request->has('paginate') && $request->paginate === 'true') {
            $perPage = $request->get('per_page', 50);
            $products = $query->paginate($perPage);
            return response()->json($products);
        }

        // Sin paginación (para compatibilidad)
        $products = $query->get();

        return response()->json([
            'products' => $products,
            'total' => $products->count()
        ]);
    }

    public function show(Product $product): JsonResponse
    {
        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'required|string|max:255',
            'brand' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'rating' => 'sometimes|numeric|between:0,5',
            'original_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean'
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Producto creado exitosamente',
            'product' => $product
        ], 201);
    }

    public function update(Request $request, Product $product): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'sometimes|string|max:255',
            'brand' => 'nullable|string|max:255',
            'image' => 'nullable|string',
            'rating' => 'sometimes|numeric|between:0,5',
            'original_price' => 'nullable|numeric|min:0',
            'notes' => 'nullable|array',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean'
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Producto actualizado exitosamente',
            'product' => $product
        ]);
    }

    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }
}
