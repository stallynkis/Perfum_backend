<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DocumentVerificationController extends Controller
{
    /**
     * Consultar DNI en RENIEC usando panconqueso API
     */
    public function consultarDNI($dni)
    {
        try {
            // Validar DNI
            if (strlen($dni) !== 8 || !is_numeric($dni)) {
                return response()->json([
                    'success' => false,
                    'message' => 'DNI inválido. Debe tener 8 dígitos.'
                ], 400);
            }

            // Llamar a la API de RENIEC usando POST con Content-Type: application/x-www-form-urlencoded
            $response = Http::asForm()
                ->timeout(15)
                ->retry(2, 100)
                ->post('https://panconqueso.bonelektroniks.com/api/consulta-reniec-simple', [
                    'dni' => $dni
                ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Log para debug
                Log::info('RENIEC Response RAW:', $data);
                
                // Extraer datos - intentar de ambos niveles
                $apiData = $data['data'] ?? $data;
                $nombres = $apiData['nombres'] ?? $apiData['nombre'] ?? '';
                $apellidos = $apiData['apellidos'] ?? $apiData['apellido'] ?? '';
                $nombreCompleto = trim($nombres . ' ' . $apellidos);
                
                Log::info('Nombre completo procesado:', [
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'nombreCompleto' => $nombreCompleto
                ]);
                
                return response()->json([
                    'success' => true,
                    'data' => [
                        'nombres' => $nombres,
                        'apellidos' => $apellidos,
                        'nombreCompleto' => $nombreCompleto,
                        'dni' => $dni,
                        'raw' => $data
                    ],
                    'message' => 'Datos obtenidos correctamente'
                ]);
            } else {
                Log::error('Error API RENIEC Status ' . $response->status() . ': ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información del DNI',
                    'error' => $response->body()
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Exception consultando RENIEC: ' . $e->getMessage());
            Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar DNI: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Consultar RUC en SUNAT
     */
    public function consultarRUC($ruc)
    {
        try {
            // Validar RUC
            if (strlen($ruc) !== 11 || !is_numeric($ruc)) {
                return response()->json([
                    'success' => false,
                    'message' => 'RUC inválido. Debe tener 11 dígitos.'
                ], 400);
            }

            // Llamar a la API de SUNAT usando POST con form data
            $response = Http::asForm()->post('https://panconqueso.bonelektroniks.com/api/consulta-sunat', [
                'ruc' => $ruc
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'message' => 'Datos obtenidos correctamente'
                ]);
            } else {
                Log::error('Error API SUNAT: ' . $response->body());
                return response()->json([
                    'success' => false,
                    'message' => 'No se pudo obtener información del RUC'
                ], $response->status());
            }

        } catch (\Exception $e) {
            Log::error('Error consultando SUNAT: ' . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'message' => 'Error al consultar RUC: ' . $e->getMessage()
            ], 500);
        }
    }
}
