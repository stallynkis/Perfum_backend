<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Slide;
use Illuminate\Http\Request;

class SlideController extends Controller
{
    // Endpoint público para el carrusel del home
    public function publicIndex()
    {
        $slides = Slide::where('isActive', true)
            ->orderBy('order')
            ->get();
        return response()->json(['data' => $slides]);
    }

    public function index()
    {
        $slides = Slide::orderBy('order')->get();
        return response()->json(['data' => $slides]);
    }

    public function store(Request $request)
    {
        try {
            \Log::info('📸 Intentando crear slide', [
                'has_image' => $request->has('image'),
                'image_length' => $request->has('image') ? strlen($request->image) : 0,
                'title' => $request->title
            ]);

            $validated = $request->validate([
                'image' => 'required|string|max:16777215', // MEDIUMTEXT max
                'title' => 'required|string|max:255',
                'subtitle' => 'nullable|string',
                'buttonText' => 'nullable|string',
                'buttonLink' => 'nullable|string',
                'buttonAction' => 'nullable|string|in:navigate,modal,external',
                'actionValue' => 'nullable|string',
                'order' => 'integer',
                'isActive' => 'boolean'
            ]);

            $slide = Slide::create($validated);

            \Log::info('✅ Slide creado exitosamente', ['slide_id' => $slide->id]);

            return response()->json([
                'slide' => $slide,
                'message' => 'Slide creado exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Error de validación creando slide', [
                'errors' => $e->errors()
            ]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('❌ Error creando slide', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al crear slide: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(Slide $slide)
    {
        return response()->json(['data' => $slide]);
    }

    public function update(Request $request, Slide $slide)
    {
        try {
            $validated = $request->validate([
                'image' => 'sometimes|string',
                'title' => 'sometimes|string|max:255',
                'subtitle' => 'nullable|string',
                'buttonText' => 'nullable|string',
                'buttonLink' => 'nullable|string',
                'buttonAction' => 'nullable|string|in:navigate,modal,external',
                'actionValue' => 'nullable|string',
                'order' => 'nullable|integer',
                'isActive' => 'nullable|boolean'
            ]);

            $slide->update($validated);

            return response()->json([
                'slide' => $slide,
                'message' => 'Slide actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar slide',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function destroy(Slide $slide)
    {
        $slide->delete();

        return response()->json([
            'message' => 'Slide eliminado exitosamente'
        ]);
    }
}
