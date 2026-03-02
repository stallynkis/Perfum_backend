<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SettingsController extends Controller
{
    /**
     * GET /settings/business — público, lo usan vendedores y cualquier cliente.
     */
    public function getBusinessInfo()
    {
        $setting = DB::table('settings')->where('key', 'business_info')->first();

        if (!$setting) {
            return response()->json([
                'name'      => 'HERLINSO PERFUMERÍA',
                'ruc'       => '20123456789',
                'address'   => 'Av. Principal 123, Lima, Perú',
                'phone'     => '+51 999 999 999',
                'whatsapp'  => '51999999999',
                'email'     => 'contacto@herlinsoperfumeria.com',
                'website'   => 'www.herlinsoperfumeria.com',
                'slogan'    => 'La fragancia perfecta para cada momento',
            ]);
        }

        return response()->json(json_decode($setting->value, true));
    }

    /**
     * PUT /admin/settings/business — solo admin autenticado.
     */
    public function updateBusinessInfo(Request $request)
    {
        $validated = $request->validate([
            'name'      => 'required|string|max:100',
            'ruc'       => 'required|string|max:11',
            'address'   => 'required|string|max:255',
            'phone'     => 'required|string|max:50',
            'whatsapp'  => 'nullable|string|max:20',
            'email'     => 'nullable|email|max:100',
            'website'   => 'nullable|string|max:100',
            'slogan'    => 'nullable|string|max:200',
        ]);

        DB::table('settings')->updateOrInsert(
            ['key' => 'business_info'],
            [
                'value'      => json_encode($validated),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Configuración guardada correctamente',
            'data'    => $validated,
        ]);
    }
}
