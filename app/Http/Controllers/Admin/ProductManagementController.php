<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductManagementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::orderBy('created_at', 'desc')->get();
        return response()->json(['data' => $products]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        \Log::info('ProductManagementController@store - Request received', ['data' => $request->all()]);
        
        try {
            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'nullable|integer|min:0',
                'category_id' => 'nullable|exists:categories,id',
                'brand_id' => 'nullable|exists:brands,id',
                'category' => 'nullable|string|max:255',
                'brand' => 'nullable|string|max:255',
                'image' => 'nullable|string',
                'rating' => 'nullable|numeric|between:0,5',
                'original_price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|array',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
            ]);

            \Log::info('ProductManagementController@store - Validation passed', ['validated' => $validated]);

            // Valores por defecto
            $validated['is_active'] = true;
            $validated['is_featured'] = $validated['is_featured'] ?? false;
            $validated['stock'] = $validated['stock'] ?? 0;
            $validated['rating'] = $validated['rating'] ?? 4.5;

            $product = Product::create($validated);

            \Log::info('ProductManagementController@store - Product created', ['product' => $product]);

            return response()->json([
                'product' => $product,
                'message' => 'Producto creado exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::warning('ProductManagementController@store - Validation failed', ['errors' => $e->errors()]);
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            \Log::error('Error al crear producto (Admin): ' . $e->getMessage(), [
                'request' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al crear producto',
                'error' => $e->getMessage(),
                'details' => config('app.debug') ? $e->getTraceAsString() : null
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);
        return response()->json(['data' => $product]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $product = Product::findOrFail($id);

        // Log para debug
        \Log::info('📥 Datos recibidos para actualizar producto:', [
            'id' => $id,
            'request_all' => $request->all(),
            'is_featured' => $request->input('is_featured'),
        ]);

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'sometimes|string|max:255',
            'brand' => 'sometimes|nullable|string|max:255',
            'image' => 'sometimes|nullable|string',
            'rating' => 'sometimes|numeric|between:0,5',
            'original_price' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'sometimes|nullable|array',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        \Log::info('✅ Datos validados:', $validated);

        $validated['is_active'] = true;
        $product->update($validated);

        \Log::info('💾 Producto guardado en BD:', [
            'id' => $product->id,
            'is_featured' => $product->is_featured,
            'is_active' => $product->is_active,
        ]);

        return response()->json([
            'product' => $product,
            'message' => 'Producto actualizado exitosamente'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json([
            'message' => 'Producto eliminado exitosamente'
        ]);
    }
}
