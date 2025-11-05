<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class ReniecController extends Controller
{
    public function consultarDNI($dni)
    {
        try {
            // Validar formato DNI
            if (!preg_match('/^\d{8}$/', $dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'El DNI debe tener 8 dígitos'
                ], 400);
            }

            // API de RENIEC (puedes usar diferentes proveedores)
            // Ejemplo con apis.net.pe
            $apiToken = env('RENIEC_API_TOKEN', '');
            
            if (empty($apiToken)) {
                // Si no hay API token configurado, retornar datos de prueba
                return $this->getDatosPrueba($dni);
            }

            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiToken,
            ])->get("https://api.apis.net.pe/v2/reniec/dni?numero={$dni}");

            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'dni' => $dni,
                        'nombres' => $data['nombres'] ?? '',
                        'apellido_paterno' => $data['apellidoPaterno'] ?? '',
                        'apellido_materno' => $data['apellidoMaterno'] ?? '',
                    ]
                ]);
            }

            // Si falla la API, retornar datos de prueba para desarrollo
            return $this->getDatosPrueba($dni);

        } catch (\Exception $e) {
            // En caso de error, retornar datos de prueba para desarrollo
            return $this->getDatosPrueba($dni);
        }
    }

    private function getDatosPrueba($dni)
    {
        // Datos de prueba para desarrollo
        $datosPrueba = [
            '12345678' => [
                'nombres' => 'JUAN CARLOS',
                'apellido_paterno' => 'PEREZ',
                'apellido_materno' => 'GARCIA'
            ],
            '87654321' => [
                'nombres' => 'MARIA ELENA',
                'apellido_paterno' => 'RODRIGUEZ',
                'apellido_materno' => 'LOPEZ'
            ]
        ];

        if (isset($datosPrueba[$dni])) {
            return response()->json([
                'success' => true,
                'data' => [
                    'dni' => $dni,
                    'nombres' => $datosPrueba[$dni]['nombres'],
                    'apellido_paterno' => $datosPrueba[$dni]['apellido_paterno'],
                    'apellido_materno' => $datosPrueba[$dni]['apellido_materno'],
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'DNI no encontrado'
        ], 404);
    }
}
