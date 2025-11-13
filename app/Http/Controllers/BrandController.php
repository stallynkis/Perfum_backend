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
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:brands',
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $brand = Brand::create($request->all());
            return response()->json([
                'message' => 'Marca creada exitosamente',
                'brand' => $brand
            ], 201);
        } catch (\Exception $e) {
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
            'name' => 'required|string|max:255|unique:brands,name,' . $id,
            'description' => 'nullable|string',
            'image' => 'nullable|string',
            'is_active' => 'boolean',
            'order' => 'integer',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Error de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $brand->update($request->all());
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
