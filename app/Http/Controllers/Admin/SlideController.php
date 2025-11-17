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
        $validated = $request->validate([
            'image' => 'required|string',
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

        return response()->json([
            'slide' => $slide,
            'message' => 'Slide creado exitosamente'
        ], 201);
    }

    public function show(Slide $slide)
    {
        return response()->json(['data' => $slide]);
    }

    public function update(Request $request, Slide $slide)
    {
        $validated = $request->validate([
            'image' => 'string',
            'title' => 'string|max:255',
            'subtitle' => 'nullable|string',
            'buttonText' => 'nullable|string',
            'buttonLink' => 'nullable|string',
            'buttonAction' => 'nullable|string|in:navigate,modal,external',
            'actionValue' => 'nullable|string',
            'order' => 'integer',
            'isActive' => 'boolean'
        ]);

        $slide->update($validated);

        return response()->json([
            'slide' => $slide,
            'message' => 'Slide actualizado exitosamente'
        ]);
    }

    public function destroy(Slide $slide)
    {
        $slide->delete();

        return response()->json([
            'message' => 'Slide eliminado exitosamente'
        ]);
    }
}
