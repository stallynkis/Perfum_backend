<?php

namespace App\Services;

use App\Models\BillingConfig;
use App\Models\BillingDocument;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class FactuFlashService
{
    protected BillingConfig $config;
    protected string $baseUrl;
    protected string $token;

    public function __construct(BillingConfig $config)
    {
        $this->config = $config;
        $this->baseUrl = rtrim($config->api_url, '/');
        $this->token = $config->api_token;
    }

    /**
     * Crear instancia desde la configuración activa.
     */
    public static function make(): ?self
    {
        $config = BillingConfig::getActive();
        if (!$config || !$config->isReady()) {
            return null;
        }
        return new self($config);
    }

    // ──────────────────────────────────────────
    // FACTURA (tipo_doc = 01)
    // ──────────────────────────────────────────
    public function emitirFactura(array $clientData, array $items, array $extras = []): array
    {
        $numeracion = $this->config->getNextCorrelativo('01');
        $calculatedItems = $this->calcularItems($items);
        $totales = $this->calcularTotales($calculatedItems);

        $payload = [
            'client' => [
                'tipo_doc' => '6', // RUC
                'num_doc' => $clientData['ruc'],
                'rzn_social' => $clientData['razon_social'],
            ],
            'items' => $calculatedItems,
            'tipo_operacion' => '0101',
            'tipo_doc' => '01',
            'serie' => $numeracion['serie'],
            'correlativo' => $numeracion['correlativo'],
            'fecha_emision' => now()->format('Y-m-d'),
            'tipo_moneda' => $this->config->tipo_moneda,
            'mto_oper_gravadas' => $totales['gravadas'],
            'mto_igv' => $totales['igv'],
            'total_impuestos' => $totales['igv'],
            'valor_venta' => $totales['gravadas'],
            'sub_total' => $totales['total'],
            'mto_imp_venta' => $totales['total'],
            'legend_value' => $this->montoEnLetras($totales['total']),
        ];

        // Separar metadata de BD del payload de la API
        $dbMeta = [
            'origin_type' => $extras['origin_type'] ?? null,
            'origin_id' => $extras['origin_id'] ?? null,
        ];
        $apiExtras = array_diff_key($extras, array_flip(['origin_type', 'origin_id']));
        $payload = array_merge($payload, $apiExtras);

        return $this->enviarDocumento($payload, 'generate-invoice', $clientData, $totales, [], $dbMeta);
    }

    // ──────────────────────────────────────────
    // BOLETA (tipo_doc = 03)
    // ──────────────────────────────────────────
    public function emitirBoleta(array $clientData, array $items, array $extras = []): array
    {
        $numeracion = $this->config->getNextCorrelativo('03');
        $calculatedItems = $this->calcularItems($items);
        $totales = $this->calcularTotales($calculatedItems);

        // Determinar tipo de documento del cliente
        $tipoDocCliente = '1'; // DNI por defecto
        $numDoc = $clientData['dni'] ?? $clientData['num_doc'] ?? '-';
        $nombre = $clientData['nombre'] ?? $clientData['razon_social'] ?? 'CLIENTE';

        if (empty($numDoc) || $numDoc === '-') {
            $tipoDocCliente = '0'; // Sin documento
            $numDoc = '-';
            $nombre = $nombre ?: 'CLIENTE SIN DOCUMENTO';
        }

        $payload = [
            'client' => [
                'tipo_doc' => $tipoDocCliente,
                'num_doc' => $numDoc,
                'rzn_social' => $nombre,
            ],
            'items' => $calculatedItems,
            'tipo_operacion' => '0101',
            'tipo_doc' => '03',
            'serie' => $numeracion['serie'],
            'correlativo' => $numeracion['correlativo'],
            'fecha_emision' => now()->format('Y-m-d'),
            'tipo_moneda' => $this->config->tipo_moneda,
            'mto_oper_gravadas' => $totales['gravadas'],
            'mto_igv' => $totales['igv'],
            'total_impuestos' => $totales['igv'],
            'valor_venta' => $totales['gravadas'],
            'sub_total' => $totales['total'],
            'mto_imp_venta' => $totales['total'],
            'legend_value' => $this->montoEnLetras($totales['total']),
        ];

        // Separar metadata de BD del payload de la API
        $dbMeta = [
            'origin_type' => $extras['origin_type'] ?? null,
            'origin_id' => $extras['origin_id'] ?? null,
        ];
        $apiExtras = array_diff_key($extras, array_flip(['origin_type', 'origin_id']));
        $payload = array_merge($payload, $apiExtras);

        return $this->enviarDocumento($payload, 'generate-invoice', $clientData, $totales, [], $dbMeta);
    }

    // ──────────────────────────────────────────
    // NOTA DE CRÉDITO (tipo_doc = 07)
    // ──────────────────────────────────────────
    public function emitirNotaCredito(
        string $tipoDocAfectado,
        string $numDocAfectado,
        string $codMotivo,
        string $desMotivo,
        array $clientData,
        array $items,
        array $extras = []
    ): array {
        $numeracion = $this->config->getNextCorrelativo('07', $tipoDocAfectado);
        $calculatedItems = $this->calcularItems($items);
        $totales = $this->calcularTotales($calculatedItems);

        // Tipo doc del cliente según el comprobante afectado
        $clienteTipoDoc = $tipoDocAfectado === '01' ? '6' : '1';

        $payload = [
            'tipo_doc' => '07',
            'serie' => $numeracion['serie'],
            'correlativo' => $numeracion['correlativo'],
            'fecha_emision' => now()->format('Y-m-d'),
            'tipo_doc_afectado' => $tipoDocAfectado,
            'num_doc_afectado' => $numDocAfectado,
            'cod_motivo' => $codMotivo,
            'des_motivo' => $desMotivo,
            'tipo_moneda' => $this->config->tipo_moneda,
            'client' => [
                'tipo_doc' => $clienteTipoDoc,
                'num_doc' => $clientData['num_doc'] ?? $clientData['ruc'] ?? $clientData['dni'] ?? '',
                'rzn_social' => $clientData['razon_social'] ?? $clientData['nombre'] ?? '',
            ],
            'items' => $calculatedItems,
            'mto_oper_gravadas' => $totales['gravadas'],
            'mto_igv' => $totales['igv'],
            'total_impuestos' => $totales['igv'],
            'mto_imp_venta' => $totales['total'],
        ];

        $payload = array_merge($payload, $extras);

        return $this->enviarDocumento($payload, 'generate-note', $clientData, $totales, [
            'tipo_doc_afectado' => $tipoDocAfectado,
            'num_doc_afectado' => $numDocAfectado,
            'cod_motivo' => $codMotivo,
            'des_motivo' => $desMotivo,
        ]);
    }

    // ──────────────────────────────────────────
    // NOTA DE DÉBITO (tipo_doc = 08)
    // ──────────────────────────────────────────
    public function emitirNotaDebito(
        string $tipoDocAfectado,
        string $numDocAfectado,
        string $codMotivo,
        string $desMotivo,
        array $clientData,
        array $items,
        array $extras = []
    ): array {
        $numeracion = $this->config->getNextCorrelativo('08', $tipoDocAfectado);
        $calculatedItems = $this->calcularItems($items);
        $totales = $this->calcularTotales($calculatedItems);

        $clienteTipoDoc = $tipoDocAfectado === '01' ? '6' : '1';

        $payload = [
            'tipo_doc' => '08',
            'serie' => $numeracion['serie'],
            'correlativo' => $numeracion['correlativo'],
            'fecha_emision' => now()->format('Y-m-d'),
            'tipo_doc_afectado' => $tipoDocAfectado,
            'num_doc_afectado' => $numDocAfectado,
            'cod_motivo' => $codMotivo,
            'des_motivo' => $desMotivo,
            'tipo_moneda' => $this->config->tipo_moneda,
            'client' => [
                'tipo_doc' => $clienteTipoDoc,
                'num_doc' => $clientData['num_doc'] ?? $clientData['ruc'] ?? $clientData['dni'] ?? '',
                'rzn_social' => $clientData['razon_social'] ?? $clientData['nombre'] ?? '',
            ],
            'items' => $calculatedItems,
            'mto_oper_gravadas' => $totales['gravadas'],
            'mto_igv' => $totales['igv'],
            'total_impuestos' => $totales['igv'],
            'mto_imp_venta' => $totales['total'],
        ];

        $payload = array_merge($payload, $extras);

        return $this->enviarDocumento($payload, 'generate-note', $clientData, $totales, [
            'tipo_doc_afectado' => $tipoDocAfectado,
            'num_doc_afectado' => $numDocAfectado,
            'cod_motivo' => $codMotivo,
            'des_motivo' => $desMotivo,
        ]);
    }

    // ──────────────────────────────────────────
    // COMUNICACIÓN DE BAJA
    // ──────────────────────────────────────────
    public function emitirComunicacionBaja(array $documents, string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        $payload = [
            'documents' => $documents,
            'date' => $date,
        ];

        try {
            $response = $this->makeRequest('generate-voided', $payload);

            return [
                'success' => $response['success'] ?? false,
                'ticket' => $response['ticket'] ?? null,
                'xml_path' => $response['xml_path'] ?? null,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error al emitir comunicación de baja', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ──────────────────────────────────────────
    // RESUMEN DIARIO
    // ──────────────────────────────────────────
    public function emitirResumenDiario(array $documents, string $date = null): array
    {
        $date = $date ?? now()->format('Y-m-d');

        $payload = [
            'documents' => $documents,
            'date' => $date,
        ];

        try {
            $response = $this->makeRequest('generate-daily-summary', $payload);

            return [
                'success' => $response['success'] ?? false,
                'ticket' => $response['ticket'] ?? null,
                'xml_path' => $response['xml_path'] ?? null,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            Log::error('Error al emitir resumen diario', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ──────────────────────────────────────────
    // CONSULTAR ESTADO (ticket de resumen o baja)
    // ──────────────────────────────────────────
    public function consultarEstado(string $ticket): array
    {
        try {
            $response = $this->makeRequest('get-status-summary', ['ticket' => $ticket]);
            return [
                'success' => $response['success'] ?? false,
                'response' => $response,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    // ══════════════════════════════════════════
    // MÉTODOS PRIVADOS
    // ══════════════════════════════════════════

    /**
     * Enviar documento a FactuFlash y registrar en BD.
     */
    private function enviarDocumento(
        array $payload,
        string $endpoint,
        array $clientData,
        array $totales,
        array $notaExtra = [],
        array $dbMeta = []
    ): array {
        try {
            $response = $this->makeRequest($endpoint, $payload);

            $estado = 'error';
            if (isset($response['success']) && $response['success']) {
                $estado = ($response['response']['status'] ?? '') === 'ACEPTADA' ? 'aceptada' : 'pendiente';
            } elseif (isset($response['response']['code']) && $response['response']['code'] >= 2000) {
                $estado = 'rechazada';
            }

            // Registrar en billing_documents
            $doc = BillingDocument::create([
                'tipo_doc' => $payload['tipo_doc'],
                'serie' => $payload['serie'],
                'correlativo' => (int) $payload['correlativo'],
                'fecha_emision' => $payload['fecha_emision'],
                'tipo_moneda' => $payload['tipo_moneda'] ?? $this->config->tipo_moneda,
                'origin_type' => $dbMeta['origin_type'] ?? null,
                'origin_id' => $dbMeta['origin_id'] ?? null,
                'cliente_tipo_doc' => $payload['client']['tipo_doc'],
                'cliente_num_doc' => $payload['client']['num_doc'],
                'cliente_razon_social' => $payload['client']['rzn_social'],
                'mto_oper_gravadas' => $totales['gravadas'],
                'mto_igv' => $totales['igv'],
                'total_impuestos' => $totales['igv'],
                'valor_venta' => $totales['gravadas'],
                'sub_total' => $totales['total'],
                'mto_imp_venta' => $totales['total'],
                'tipo_doc_afectado' => $notaExtra['tipo_doc_afectado'] ?? null,
                'num_doc_afectado' => $notaExtra['num_doc_afectado'] ?? null,
                'cod_motivo' => $notaExtra['cod_motivo'] ?? null,
                'des_motivo' => $notaExtra['des_motivo'] ?? null,
                'estado' => $estado,
                'sunat_code' => $response['response']['code'] ?? null,
                'sunat_description' => $response['response']['description'] ?? null,
                'sunat_notes' => isset($response['response']['notes']) ? json_encode($response['response']['notes']) : null,
                'xml_path' => $response['xml_path'] ?? null,
                'cdr_path' => $response['cdr_path'] ?? null,
                'items' => $payload['items'],
                'payload_enviado' => $payload,
                'respuesta_completa' => $response,
            ]);

            return [
                'success' => $estado === 'aceptada',
                'document' => $doc,
                'numero' => "{$payload['serie']}-{$payload['correlativo']}",
                'estado' => $estado,
                'response' => $response,
            ];

        } catch (\Exception $e) {
            Log::error('Error al enviar documento a FactuFlash', [
                'error' => $e->getMessage(),
                'endpoint' => $endpoint,
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'numero' => "{$payload['serie']}-{$payload['correlativo']}",
            ];
        }
    }

    /**
     * Realizar petición HTTP a FactuFlash.
     */
    private function makeRequest(string $endpoint, array $data): array
    {
        // URL: {baseUrl}/sunat/{RUC}/{endpoint}
        $ruc = $this->config->ruc;
        $url = "{$this->baseUrl}/sunat/{$ruc}/{$endpoint}";

        Log::info('🌐 FactuFlash request', ['url' => $url, 'endpoint' => $endpoint]);

        $response = Http::withHeaders([
            'Authorization' => "Bearer {$this->token}",
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ])->timeout(30)->post($url, $data);

        Log::info('🌐 FactuFlash response', ['status' => $response->status(), 'body' => substr($response->body(), 0, 500)]);

        if ($response->failed()) {
            throw new \Exception("Error HTTP {$response->status()}: {$response->body()}");
        }

        return $response->json() ?? [];
    }

    /**
     * Calcular items con IGV.
     */
    private function calcularItems(array $items): array
    {
        $igvRate = (float) $this->config->igv_porcentaje;
        $result = [];

        foreach ($items as $item) {
            // Si ya vienen calculados, pasarlos directo
            if (isset($item['base_igv'])) {
                $result[] = $item;
                continue;
            }

            // Calcular desde precio con IGV
            $cantidad = (float) ($item['cantidad'] ?? $item['quantity'] ?? 1);
            $precioConIgv = (float) ($item['precio_unitario'] ?? $item['price'] ?? $item['unit_price'] ?? 0);
            $valorUnitario = round($precioConIgv / (1 + $igvRate / 100), 2);
            $valorVenta = round($valorUnitario * $cantidad, 2);
            $igv = round($valorVenta * $igvRate / 100, 2);
            $baseIgv = $valorVenta;

            $result[] = [
                'cod_producto' => (string) ($item['cod_producto'] ?? $item['product_code'] ?? $item['product_id'] ?? 'P001'),
                'unidad' => $item['unidad'] ?? 'NIU',
                'cantidad' => $cantidad,
                'valor_unitario' => $valorUnitario,
                'descripcion' => $item['descripcion'] ?? $item['description'] ?? $item['product_name'] ?? 'Producto',
                'base_igv' => $baseIgv,
                'porcentaje_igv' => $igvRate,
                'igv' => $igv,
                'tipo_afe_igv' => $item['tipo_afe_igv'] ?? '10',
                'total_impuestos' => $igv,
                'valor_venta' => $valorVenta,
                'precio_unitario' => $precioConIgv,
            ];
        }

        return $result;
    }

    /**
     * Calcular totales a partir de los items.
     */
    private function calcularTotales(array $items): array
    {
        $gravadas = 0;
        $igv = 0;

        foreach ($items as $item) {
            $gravadas += (float) $item['valor_venta'];
            $igv += (float) $item['igv'];
        }

        $gravadas = round($gravadas, 2);
        $igv = round($igv, 2);
        $total = round($gravadas + $igv, 2);

        return [
            'gravadas' => $gravadas,
            'igv' => $igv,
            'total' => $total,
        ];
    }

    /**
     * Convertir monto a letras (simplificado).
     */
    private function montoEnLetras(float $monto): string
    {
        $entero = (int) $monto;
        $decimales = round(($monto - $entero) * 100);

        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', 'DIEZ', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $especiales = [
            11 => 'ONCE', 12 => 'DOCE', 13 => 'TRECE', 14 => 'CATORCE', 15 => 'QUINCE',
            16 => 'DIECISÉIS', 17 => 'DIECISIETE', 18 => 'DIECIOCHO', 19 => 'DIECINUEVE',
        ];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        $convertir = function (int $num) use ($unidades, $decenas, $especiales, $centenas): string {
            if ($num === 0) return 'CERO';
            if ($num === 100) return 'CIEN';

            $resultado = '';

            if ($num >= 1000) {
                $miles = (int) ($num / 1000);
                $num %= 1000;
                if ($miles === 1) {
                    $resultado = 'MIL ';
                } else {
                    // Recursión simple para miles
                    $resultado = ($miles < 10 ? $unidades[$miles] : (string) $miles) . ' MIL ';
                }
            }

            if ($num >= 100) {
                $c = (int) ($num / 100);
                $num %= 100;
                if ($num === 0 && $c === 1) {
                    $resultado .= 'CIEN';
                    return trim($resultado);
                }
                $resultado .= $centenas[$c] . ' ';
            }

            if ($num >= 11 && $num <= 19 && isset($especiales[$num])) {
                $resultado .= $especiales[$num];
            } elseif ($num === 10) {
                $resultado .= 'DIEZ';
            } elseif ($num >= 20) {
                $d = (int) ($num / 10);
                $u = $num % 10;
                $resultado .= $decenas[$d];
                if ($u > 0) {
                    $resultado .= ' Y ' . $unidades[$u];
                }
            } elseif ($num > 0) {
                $resultado .= $unidades[$num];
            }

            return trim($resultado);
        };

        $moneda = $this->config->tipo_moneda === 'USD' ? 'DÓLARES' : 'SOLES';

        return $convertir($entero) . " Y {$decimales}/100 {$moneda}";
    }
}
