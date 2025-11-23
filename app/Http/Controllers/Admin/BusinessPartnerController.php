<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BusinessPartner;
use Illuminate\Http\Request;

class BusinessPartnerController extends Controller
{
    public function index(Request $request)
    {
        $query = BusinessPartner::query();

        // Filtrar por tipo si se especifica
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Buscar por nombre o RUC
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'LIKE', "%{$search}%")
                  ->orWhere('ruc', 'LIKE', "%{$search}%");
            });
        }

        $partners = $query->orderBy('name')->get();

        return response()->json(['data' => $partners]);
    }

    public function store(Request $request)
    {
        try {
            \Log::info('🤝 Creando socio de negocio', $request->all());

            $validated = $request->validate([
                'name' => 'required|string|max:255',
                'type' => 'required|in:supplier,customer,seller',
                'ruc' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'credit_limit' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
                'notes' => 'nullable|string'
            ]);

            // Verificar si ya existe un socio con el mismo RUC/DNI
            if (!empty($validated['ruc'])) {
                $existing = BusinessPartner::where('ruc', $validated['ruc'])->first();
                if ($existing) {
                    \Log::info('ℹ️ Socio ya existe', ['id' => $existing->id, 'ruc' => $validated['ruc']]);
                    return response()->json([
                        'partner' => $existing,
                        'message' => 'El socio ya existe'
                    ], 422);
                }
            }

            $partner = BusinessPartner::create($validated);

            \Log::info('✅ Socio creado', ['id' => $partner->id]);

            return response()->json([
                'partner' => $partner,
                'message' => 'Socio de negocio creado exitosamente'
            ], 201);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('❌ Error de validación', ['errors' => $e->errors()]);
            throw $e;
        } catch (\Exception $e) {
            \Log::error('❌ Error creando socio', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json([
                'message' => 'Error al crear socio: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show(BusinessPartner $businessPartner)
    {
        return response()->json(['data' => $businessPartner]);
    }

    public function update(Request $request, BusinessPartner $businessPartner)
    {
        try {
            $validated = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'type' => 'sometimes|required|in:supplier,customer,seller',
                'ruc' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'phone' => 'nullable|string|max:20',
                'address' => 'nullable|string',
                'contact_person' => 'nullable|string|max:255',
                'credit_limit' => 'nullable|numeric|min:0',
                'is_active' => 'boolean',
                'notes' => 'nullable|string'
            ]);

            $businessPartner->update($validated);

            return response()->json([
                'partner' => $businessPartner,
                'message' => 'Socio actualizado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al actualizar socio: ' . $e->getMessage()
            ], 500);
        }
    }

    public function destroy(BusinessPartner $businessPartner)
    {
        try {
            $businessPartner->delete();
            return response()->json([
                'message' => 'Socio eliminado exitosamente'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al eliminar socio: ' . $e->getMessage()
            ], 500);
        }
    }

    // Endpoints específicos por tipo
    public function suppliers()
    {
        $suppliers = BusinessPartner::where('type', 'supplier')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return response()->json(['data' => $suppliers]);
    }

    public function customers()
    {
        $customers = BusinessPartner::where('type', 'customer')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return response()->json(['data' => $customers]);
    }

    public function sellers()
    {
        $sellers = BusinessPartner::where('type', 'seller')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
        return response()->json(['data' => $sellers]);
    }
}
