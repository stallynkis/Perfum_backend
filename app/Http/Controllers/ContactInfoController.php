<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ContactInfo;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class ContactInfoController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            $contactInfo = ContactInfo::getActive();
            
            return response()->json([
                'success' => true,
                'contact_info' => $contactInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener información de contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function update(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|string|max:255',
            'subtitle' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:255',
            'hours' => 'nullable|string',
            'whatsapp' => 'nullable|string|max:20',
            'description' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Datos de validación incorrectos',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            ContactInfo::where('is_active', true)->update(['is_active' => false]);
            
            $contactInfo = ContactInfo::create([
                'title' => $request->title,
                'subtitle' => $request->subtitle,
                'phone' => $request->phone,
                'email' => $request->email,
                'address' => $request->address,
                'city' => $request->city,
                'hours' => $request->hours,
                'whatsapp' => $request->whatsapp,
                'description' => $request->description,
                'is_active' => true
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Información de contacto actualizada exitosamente',
                'contact_info' => $contactInfo
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar información de contacto',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function history(): JsonResponse
    {
        try {
            $history = ContactInfo::orderBy('created_at', 'desc')->get();
            
            return response()->json([
                'success' => true,
                'history' => $history
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener historial',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
