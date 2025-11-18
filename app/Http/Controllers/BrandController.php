<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class BrandController extends Controller
{
    // Obtener todas las marcas (público)
    public function index()
    {
        try {
            $brands = Brand::active()->ordered()->get();
            return response()->json($brands);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener marcas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Obtener todas las marcas (admin - incluye inactivas)
    public function adminIndex()
    {
        try {
            $brands = Brand::ordered()->get();
            return response()->json($brands);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al obtener marcas',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Crear nueva marca
    public function store(Request $request)
    {
        \Log::info('BrandController@store request', ['request' => $request->all()]);
        
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:brands',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'nullable|boolean',
            'order' => 'nullable|integer',
        ]);

        if ($validator->fails()) {
            \Log::warning('BrandController@store validation failed', ['errors' => $validator->errors()]);
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $data = $request->all();
            $data['is_active'] = $data['is_active'] ?? true;
            $data['description'] = $data['description'] ?? '';
            $data['image'] = $data['image'] ?? null;
            $data['order'] = $data['order'] ?? (Brand::max('order') ?? 0) + 1;
            $brand = Brand::create($data);
            \Log::info('BrandController@store success', ['brand' => $brand]);
            return response()->json([
                'message' => 'Marca creada exitosamente',
                'brand' => $brand
            ], 201);
        } catch (\Exception $e) {
            \Log::error('BrandController@store error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json([
                'message' => 'Error al crear marca',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Actualizar marca
    public function update(Request $request, $id)
    {
        $brand = Brand::find($id);
        
        if (!$brand) {
            return response()->json(['message' => 'Marca no encontrada'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:255|unique:brands,name,' . $id,
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
            $brand->update($data);
            return response()->json([
                'message' => 'Marca actualizada exitosamente',
                'brand' => $brand
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar marca',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    // Eliminar marca
    public function destroy($id)
    {
        try {
            $brand = Brand::find($id);
            
            if (!$brand) {
                return response()->json(['message' => 'Marca no encontrada'], 404);
            }

            $brand->delete();
            
            return response()->json([
                'message' => 'Marca eliminada exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar marca',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
