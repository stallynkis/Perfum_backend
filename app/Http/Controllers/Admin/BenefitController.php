<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Benefit;
use Illuminate\Http\Request;

class BenefitController extends Controller
{
    public function index()
    {
        $benefits = Benefit::orderBy('order')->get();
        return response()->json(['data' => $benefits]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'icon' => 'required|string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);

        $benefit = Benefit::create($validated);

        return response()->json([
            'benefit' => $benefit,
            'message' => 'Beneficio creado exitosamente'
        ], 201);
    }

    public function show(Benefit $benefit)
    {
        return response()->json(['data' => $benefit]);
    }

    public function update(Request $request, Benefit $benefit)
    {
        $validated = $request->validate([
            'title' => 'string|max:255',
            'description' => 'string',
            'icon' => 'string',
            'order' => 'integer',
            'is_active' => 'boolean'
        ]);

        $benefit->update($validated);

        return response()->json([
            'benefit' => $benefit,
            'message' => 'Beneficio actualizado exitosamente'
        ]);
    }

    public function destroy(Benefit $benefit)
    {
        $benefit->delete();

        return response()->json([
            'message' => 'Beneficio eliminado exitosamente'
        ]);
    }
}
