<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BillingConfig;
use Illuminate\Http\Request;

class BillingConfigController extends Controller
{
    /**
     * GET /admin/billing/config
     * Obtener la configuración de facturación.
     */
    public function index()
    {
        $config = BillingConfig::first();

        if (!$config) {
            return response()->json([
                'configured' => false,
                'data' => $this->getDefaults(),
            ]);
        }

        return response()->json([
            'configured' => true,
            'data' => $config,
            'is_ready' => $config->isReady(),
        ]);
    }

    /**
     * PUT /admin/billing/config
     * Guardar/actualizar configuración de facturación.
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            // Datos del emisor
            'ruc' => 'required|string|size:11',
            'razon_social' => 'required|string|max:200',
            'nombre_comercial' => 'nullable|string|max:200',
            'direccion' => 'nullable|string|max:300',
            'ubigeo' => 'nullable|string|max:6',
            'departamento' => 'nullable|string|max:100',
            'provincia' => 'nullable|string|max:100',
            'distrito' => 'nullable|string|max:100',

            // Series
            'serie_factura' => 'required|string|max:10',
            'serie_boleta' => 'required|string|max:10',
            'serie_nota_credito_factura' => 'required|string|max:10',
            'serie_nota_credito_boleta' => 'required|string|max:10',
            'serie_nota_debito_factura' => 'required|string|max:10',
            'serie_nota_debito_boleta' => 'required|string|max:10',

            // API
            'api_url' => 'required|url|max:255',
            'api_token' => 'nullable|string',

            // Configuración
            'tipo_moneda' => 'required|string|in:PEN,USD',
            'igv_porcentaje' => 'required|numeric|min:0|max:100',
            'facturacion_activa' => 'required|boolean',
            'enviar_automatico' => 'required|boolean',
            'modo_pruebas' => 'required|boolean',
        ]);

        $validated['api_url'] = $this->normalizeApiUrl($validated['api_url']);

        $config = BillingConfig::first();

        if ($config) {
            // No sobrescribir el token si viene vacío (campo oculto)
            if (empty($validated['api_token'])) {
                unset($validated['api_token']);
            }
            $config->update($validated);
        } else {
            $config = BillingConfig::create($validated);
        }

        return response()->json([
            'success' => true,
            'message' => 'Configuración de facturación guardada correctamente',
            'data' => $config->fresh(),
            'is_ready' => $config->fresh()->isReady(),
        ]);
    }

    /**
     * GET /admin/billing/status
     * Estado general de la facturación.
     */
    public function status()
    {
        $config = BillingConfig::first();

        if (!$config) {
            return response()->json([
                'configured' => false,
                'active' => false,
                'series' => [],
            ]);
        }

        return response()->json([
            'configured' => true,
            'active' => $config->facturacion_activa,
            'is_ready' => $config->isReady(),
            'modo_pruebas' => $config->modo_pruebas,
            'series' => [
                'factura' => [
                    'serie' => $config->serie_factura,
                    'ultimo_correlativo' => $config->correlativo_factura,
                ],
                'boleta' => [
                    'serie' => $config->serie_boleta,
                    'ultimo_correlativo' => $config->correlativo_boleta,
                ],
                'nota_credito_factura' => [
                    'serie' => $config->serie_nota_credito_factura,
                    'ultimo_correlativo' => $config->correlativo_nota_credito_factura,
                ],
                'nota_credito_boleta' => [
                    'serie' => $config->serie_nota_credito_boleta,
                    'ultimo_correlativo' => $config->correlativo_nota_credito_boleta,
                ],
                'nota_debito_factura' => [
                    'serie' => $config->serie_nota_debito_factura,
                    'ultimo_correlativo' => $config->correlativo_nota_debito_factura,
                ],
                'nota_debito_boleta' => [
                    'serie' => $config->serie_nota_debito_boleta,
                    'ultimo_correlativo' => $config->correlativo_nota_debito_boleta,
                ],
            ],
        ]);
    }

    private function getDefaults(): array
    {
        return [
            'ruc' => '',
            'razon_social' => '',
            'nombre_comercial' => '',
            'direccion' => '',
            'ubigeo' => '',
            'departamento' => '',
            'provincia' => '',
            'distrito' => '',
            'serie_factura' => 'F001',
            'correlativo_factura' => 0,
            'serie_boleta' => 'B001',
            'correlativo_boleta' => 0,
            'serie_nota_credito_factura' => 'FC01',
            'correlativo_nota_credito_factura' => 0,
            'serie_nota_credito_boleta' => 'BC01',
            'correlativo_nota_credito_boleta' => 0,
            'serie_nota_debito_factura' => 'FD01',
            'correlativo_nota_debito_factura' => 0,
            'serie_nota_debito_boleta' => 'BD01',
            'correlativo_nota_debito_boleta' => 0,
            'api_url' => 'https://factuflash.pe/api',
            'api_token' => '',
            'tipo_moneda' => 'PEN',
            'igv_porcentaje' => 18.00,
            'facturacion_activa' => false,
            'enviar_automatico' => false,
            'modo_pruebas' => true,
        ];
    }

    /**
     * Normaliza la URL base de la API para evitar que se guarden endpoints completos.
     */
    private function normalizeApiUrl(string $apiUrl): string
    {
        $apiUrl = rtrim($apiUrl, '/');

        // Si pegan una ruta completa de emisión, la reducimos a la base esperada.
        $apiUrl = preg_replace('#/sunat/[^/]+/.+$#i', '', $apiUrl) ?? $apiUrl;
        $apiUrl = preg_replace('#/(generate-invoice|generate-note|generate-voided|generate-daily-summary|get-status-summary)$#i', '', $apiUrl) ?? $apiUrl;

        return rtrim($apiUrl, '/');
    }
}
