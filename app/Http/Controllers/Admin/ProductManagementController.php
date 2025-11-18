<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

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
        try {
            \Log::info('ProductManagementController@store - Inicio', [
                'name' => $request->input('name'),
                'has_image' => !empty($request->input('image')),
                'image_length' => $request->input('image') ? strlen($request->input('image')) : 0
            ]);
            
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'description' => 'nullable|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|integer|min:0',
                'category' => 'required|string|max:255',
                'brand' => 'nullable|string|max:255',
                'image' => 'nullable|string|max:16777215',
                'rating' => 'nullable|numeric|between:0,5',
                'original_price' => 'nullable|numeric|min:0',
                'notes' => 'nullable|string',
                'is_active' => 'nullable|boolean',
                'is_featured' => 'nullable|boolean',
            ]);

            if ($validator->fails()) {
                \Log::warning('ProductManagementController@store - Validación falló', ['errors' => $validator->errors()]);
                return response()->json([
                    'message' => 'Error de validación',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $request->all();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['is_featured'] = $data['is_featured'] ?? false;
            $data['rating'] = $data['rating'] ?? 4.5;
            $data['brand'] = $data['brand'] ?? 'Sin marca';
            $data['description'] = $data['description'] ?? '';
            $data['image'] = $data['image'] ?? null;
            
            \Log::info('ProductManagementController@store - Creando producto', ['data' => array_merge($data, ['image' => 'OMITTED'])]);
            
            $product = Product::create($data);
            
            \Log::info('ProductManagementController@store - Producto creado', ['product_id' => $product->id]);
            
            return response()->json([
                'message' => 'Producto creado exitosamente',
                'product' => $product
            ], 201);
        } catch (\Exception $e) {
            \Log::error('ProductManagementController@store - Error', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'message' => 'Error al crear producto: ' . $e->getMessage(),
                'error' => $e->getMessage()
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

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|integer|min:0',
            'category' => 'sometimes|string|max:255',
            'brand' => 'sometimes|nullable|string|max:255',
            'image' => 'sometimes|nullable|string|max:16777215',
            'rating' => 'sometimes|numeric|between:0,5',
            'original_price' => 'sometimes|nullable|numeric|min:0',
            'notes' => 'sometimes|nullable|array',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
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
            $product->update($data);
            
            return response()->json([
                'message' => 'Producto actualizado exitosamente',
                'product' => $product
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json([
                'message' => 'Producto eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar producto',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
