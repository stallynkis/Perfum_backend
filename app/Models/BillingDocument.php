<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingDocument extends Model
{
    protected $fillable = [
        'tipo_doc',
        'serie',
        'correlativo',
        'fecha_emision',
        'tipo_moneda',
        'origin_type',
        'origin_id',
        'cliente_tipo_doc',
        'cliente_num_doc',
        'cliente_razon_social',
        'mto_oper_gravadas',
        'mto_oper_exoneradas',
        'mto_oper_inafectas',
        'mto_igv',
        'total_impuestos',
        'valor_venta',
        'sub_total',
        'mto_imp_venta',
        'tipo_doc_afectado',
        'num_doc_afectado',
        'cod_motivo',
        'des_motivo',
        'estado',
        'sunat_code',
        'sunat_description',
        'sunat_notes',
        'xml_path',
        'cdr_path',
        'pdf_path',
        'ticket',
        'items',
        'payload_enviado',
        'respuesta_completa',
    ];

    protected $casts = [
        'correlativo' => 'integer',
        'fecha_emision' => 'date',
        'mto_oper_gravadas' => 'decimal:2',
        'mto_oper_exoneradas' => 'decimal:2',
        'mto_oper_inafectas' => 'decimal:2',
        'mto_igv' => 'decimal:2',
        'total_impuestos' => 'decimal:2',
        'valor_venta' => 'decimal:2',
        'sub_total' => 'decimal:2',
        'mto_imp_venta' => 'decimal:2',
        'items' => 'array',
        'payload_enviado' => 'array',
        'respuesta_completa' => 'array',
    ];

    /**
     * Nombre legible del tipo de documento.
     */
    public function getTipoDocNombreAttribute(): string
    {
        return match ($this->tipo_doc) {
            '01' => 'Factura',
            '03' => 'Boleta de Venta',
            '07' => 'Nota de Crédito',
            '08' => 'Nota de Débito',
            default => 'Documento',
        };
    }

    /**
     * Número completo (serie-correlativo).
     */
    public function getNumeroCompletoAttribute(): string
    {
        return "{$this->serie}-{$this->correlativo}";
    }

    /**
     * Relación polimórfica con el origen (Sale u Order).
     */
    public function origin()
    {
        return $this->morphTo('origin', 'origin_type', 'origin_id');
    }

    /**
     * Scope para filtrar por tipo de documento.
     */
    public function scopeTipo($query, string $tipo)
    {
        return $query->where('tipo_doc', $tipo);
    }

    /**
     * Scope para filtrar por estado.
     */
    public function scopeEstado($query, string $estado)
    {
        return $query->where('estado', $estado);
    }

    /**
     * Scope para rango de fechas.
     */
    public function scopeFechaEntre($query, string $desde, string $hasta)
    {
        return $query->whereBetween('fecha_emision', [$desde, $hasta]);
    }
}
