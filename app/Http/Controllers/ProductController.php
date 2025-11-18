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

        // POR DEFECTO: Solo mostrar productos ACTIVOS al público
        // Solo permitir ver inactivos si viene del admin con un parámetro específico
        if (!$request->has('include_inactive') || $request->include_inactive !== 'true') {
            $query->where('is_active', true);
        }

        // Filtro por productos destacados
        if ($request->has('featured') && $request->featured === 'true') {
            $query->where('is_featured', true);
        }

        // Filtro por categoría
        if ($request->has('category') && $request->category !== 'all') {
            $query->where('category', $request->category);
        }

        // Filtro por marca
        if ($request->has('brand') && $request->brand !== 'all') {
            $query->where('brand', $request->brand);
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
        // Solo permitir ver productos activos al público
        if (!$product->is_active) {
            return response()->json([
                'message' => 'Producto no disponible'
            ], 404);
        }
        
        return response()->json($product);
    }

    public function store(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'nullable|integer|min:0',
                'category' => 'required|string|max:255',
                'brand' => 'nullable|string|max:255',
                'image' => 'nullable|string',
                'rating' => 'nullable|numeric|between:0,5',
                'original_price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|array',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean'
            ]);

            // Limpiar comillas extras de category y brand
            if (isset($validated['category'])) {
                $validated['category'] = trim(str_replace(['"', "'"], '', $validated['category']));
            }
            if (isset($validated['brand'])) {
                $validated['brand'] = trim(str_replace(['"', "'"], '', $validated['brand']));
            }

            // Valores por defecto
            $validated['stock'] = $validated['stock'] ?? 0;
            $validated['rating'] = $validated['rating'] ?? 4.5;
            $validated['is_active'] = $validated['is_active'] ?? true;
            $validated['is_featured'] = $validated['is_featured'] ?? false;

            $product = Product::create($validated);

            return response()->json([
                'message' => 'Producto creado exitosamente',
                'product' => $product
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear producto: ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al crear producto',
                'error' => $e->getMessage()
            ], 500);
        }
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

        // Limpiar comillas extras de category y brand
        if (isset($validated['category'])) {
            $validated['category'] = trim(str_replace(['"', "'"], '', $validated['category']));
        }
        if (isset($validated['brand'])) {
            $validated['brand'] = trim(str_replace(['"', "'"], '', $validated['brand']));
        }

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
