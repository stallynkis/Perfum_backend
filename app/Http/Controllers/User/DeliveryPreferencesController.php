<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\DeliveryPreference;
use Illuminate\Http\Request;

class DeliveryPreferencesController extends Controller
{
    public function show(Request $request)
    {
        $preference = DeliveryPreference::where('user_id', $request->user()->id)->first();
        
        if (!$preference) {
            return response()->json(null, 404);
        }

        return response()->json($preference);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'deliveryOption' => 'required|in:home,agency',
            'phone' => 'nullable|string',
            'address' => 'nullable|string',
            'agencyType' => 'nullable|in:olva,shalom',
            'selectedAgencyId' => 'nullable|string',
            'selectedAgencyName' => 'nullable|string',
            'selectedAgencyAddress' => 'nullable|string'
        ]);

        $preference = DeliveryPreference::updateOrCreate(
            ['user_id' => $request->user()->id],
            $validated
        );

        return response()->json([
            'data' => $preference,
            'message' => 'Preferencias guardadas exitosamente'
        ]);
    }

    public function destroy(Request $request)
    {
        DeliveryPreference::where('user_id', $request->user()->id)->delete();

        return response()->json([
            'message' => 'Preferencias eliminadas exitosamente'
        ]);
    }
}
