<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BillingConfig;
use App\Models\BillingDocument;
use App\Services\FactuFlashService;
use Illuminate\Http\Request;

class BillingController extends Controller
{
    // ──────────────────────────────────────────
    // EMITIR FACTURA
    // ──────────────────────────────────────────
    public function emitirFactura(Request $request)
    {
        $request->validate([
            'ruc' => 'required|string|size:11',
            'razon_social' => 'required|string|max:200',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0.01',
            'origin_type' => 'nullable|string|in:sale,order',
            'origin_id' => 'nullable|integer',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        $result = $service->emitirFactura(
            ['ruc' => $request->ruc, 'razon_social' => $request->razon_social],
            $request->items
        );

        // Vincular con origen si se proporcionó
        if ($result['success'] && isset($result['document']) && $request->origin_type && $request->origin_id) {
            $result['document']->update([
                'origin_type' => $request->origin_type,
                'origin_id' => $request->origin_id,
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // EMITIR BOLETA
    // ──────────────────────────────────────────
    public function emitirBoleta(Request $request)
    {
        $request->validate([
            'dni' => 'nullable|string|max:20',
            'nombre' => 'nullable|string|max:200',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0.01',
            'origin_type' => 'nullable|string|in:sale,order',
            'origin_id' => 'nullable|integer',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        $result = $service->emitirBoleta(
            [
                'dni' => $request->dni ?? '-',
                'nombre' => $request->nombre ?? 'CLIENTE',
            ],
            $request->items
        );

        if ($result['success'] && isset($result['document']) && $request->origin_type && $request->origin_id) {
            $result['document']->update([
                'origin_type' => $request->origin_type,
                'origin_id' => $request->origin_id,
            ]);
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // EMITIR NOTA DE CRÉDITO
    // ──────────────────────────────────────────
    public function emitirNotaCredito(Request $request)
    {
        $request->validate([
            'tipo_doc_afectado' => 'required|string|in:01,03',
            'num_doc_afectado' => 'required|string', // F001-1, B001-1
            'cod_motivo' => 'required|string|in:01,02,03,04,05,06,07,08,09,10',
            'des_motivo' => 'required|string|max:200',
            'client' => 'required|array',
            'client.num_doc' => 'required|string',
            'client.razon_social' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0.01',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        $result = $service->emitirNotaCredito(
            $request->tipo_doc_afectado,
            $request->num_doc_afectado,
            $request->cod_motivo,
            $request->des_motivo,
            $request->client,
            $request->items
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // EMITIR NOTA DE DÉBITO
    // ──────────────────────────────────────────
    public function emitirNotaDebito(Request $request)
    {
        $request->validate([
            'tipo_doc_afectado' => 'required|string|in:01,03',
            'num_doc_afectado' => 'required|string',
            'cod_motivo' => 'required|string|in:01,02,03,10',
            'des_motivo' => 'required|string|max:200',
            'client' => 'required|array',
            'client.num_doc' => 'required|string',
            'client.razon_social' => 'required|string',
            'items' => 'required|array|min:1',
            'items.*.descripcion' => 'required|string',
            'items.*.cantidad' => 'required|numeric|min:0.01',
            'items.*.precio_unitario' => 'required|numeric|min:0.01',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        $result = $service->emitirNotaDebito(
            $request->tipo_doc_afectado,
            $request->num_doc_afectado,
            $request->cod_motivo,
            $request->des_motivo,
            $request->client,
            $request->items
        );

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // COMUNICACIÓN DE BAJA
    // ──────────────────────────────────────────
    public function comunicacionBaja(Request $request)
    {
        $request->validate([
            'documents' => 'required|array|min:1',
            'documents.*.tipo_doc' => 'required|string|in:01,03,07,08',
            'documents.*.serie' => 'required|string',
            'documents.*.correlativo' => 'required|string',
            'documents.*.motivo_baja' => 'required|string|max:200',
            'date' => 'nullable|date',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        $result = $service->emitirComunicacionBaja(
            $request->documents,
            $request->date
        );

        // Actualizar estado de los documentos anulados en BD
        if ($result['success']) {
            foreach ($request->documents as $doc) {
                BillingDocument::where('serie', $doc['serie'])
                    ->where('correlativo', $doc['correlativo'])
                    ->where('tipo_doc', $doc['tipo_doc'])
                    ->update(['estado' => 'anulada', 'ticket' => $result['ticket'] ?? null]);
            }
        }

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // RESUMEN DIARIO
    // ──────────────────────────────────────────
    public function resumenDiario(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        // Obtener boletas del día para armar resumen automático
        $boletas = BillingDocument::where('tipo_doc', '03')
            ->whereDate('fecha_emision', $request->date)
            ->whereIn('estado', ['aceptada', 'pendiente', 'anulada'])
            ->get();

        if ($boletas->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No hay boletas para esta fecha',
            ], 400);
        }

        $documents = $boletas->map(function ($boleta) {
            return [
                'tipo_doc' => '03',
                'serie_numero' => "{$boleta->serie}-{$boleta->correlativo}",
                'estado' => $boleta->estado === 'anulada' ? '3' : '1',
                'cliente_tipo_doc' => $boleta->cliente_tipo_doc,
                'cliente_numero' => $boleta->cliente_num_doc,
                'total' => (float) $boleta->mto_imp_venta,
                'gravadas' => (float) $boleta->mto_oper_gravadas,
                'inafectas' => (float) $boleta->mto_oper_inafectas,
                'exoneradas' => (float) $boleta->mto_oper_exoneradas,
                'igv' => (float) $boleta->mto_igv,
            ];
        })->toArray();

        $result = $service->emitirResumenDiario($documents, $request->date);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // CONSULTAR ESTADO DE TICKET
    // ──────────────────────────────────────────
    public function consultarEstado(Request $request)
    {
        $request->validate([
            'ticket' => 'required|string',
        ]);

        $service = FactuFlashService::make();
        if (!$service) {
            return response()->json([
                'success' => false,
                'message' => 'La facturación electrónica no está configurada o activa',
            ], 400);
        }

        $result = $service->consultarEstado($request->ticket);

        return response()->json($result, $result['success'] ? 200 : 400);
    }

    // ──────────────────────────────────────────
    // LISTAR DOCUMENTOS
    // ──────────────────────────────────────────
    public function documentos(Request $request)
    {
        $query = BillingDocument::query()->orderByDesc('created_at');

        if ($request->tipo_doc) {
            $query->where('tipo_doc', $request->tipo_doc);
        }
        if ($request->estado) {
            $query->where('estado', $request->estado);
        }
        if ($request->fecha_desde && $request->fecha_hasta) {
            $query->whereBetween('fecha_emision', [$request->fecha_desde, $request->fecha_hasta]);
        }
        if ($request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('serie', 'like', "%{$search}%")
                    ->orWhere('correlativo', 'like', "%{$search}%")
                    ->orWhere('cliente_num_doc', 'like', "%{$search}%")
                    ->orWhere('cliente_razon_social', 'like', "%{$search}%");
            });
        }

        $perPage = min((int) ($request->per_page ?? 20), 100);

        return response()->json($query->paginate($perPage));
    }

    // ──────────────────────────────────────────
    // VER DOCUMENTO INDIVIDUAL
    // ──────────────────────────────────────────
    public function verDocumento($id)
    {
        $doc = BillingDocument::findOrFail($id);
        return response()->json($doc);
    }
}
