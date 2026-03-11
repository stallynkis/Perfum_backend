<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillingConfig extends Model
{
    protected $fillable = [
        'ruc',
        'razon_social',
        'nombre_comercial',
        'direccion',
        'ubigeo',
        'departamento',
        'provincia',
        'distrito',
        'serie_factura',
        'correlativo_factura',
        'serie_boleta',
        'correlativo_boleta',
        'serie_nota_credito_factura',
        'correlativo_nota_credito_factura',
        'serie_nota_credito_boleta',
        'correlativo_nota_credito_boleta',
        'serie_nota_debito_factura',
        'correlativo_nota_debito_factura',
        'serie_nota_debito_boleta',
        'correlativo_nota_debito_boleta',
        'api_url',
        'api_token',
        'tipo_moneda',
        'igv_porcentaje',
        'facturacion_activa',
        'enviar_automatico',
        'modo_pruebas',
    ];

    protected $casts = [
        'correlativo_factura' => 'integer',
        'correlativo_boleta' => 'integer',
        'correlativo_nota_credito_factura' => 'integer',
        'correlativo_nota_credito_boleta' => 'integer',
        'correlativo_nota_debito_factura' => 'integer',
        'correlativo_nota_debito_boleta' => 'integer',
        'igv_porcentaje' => 'decimal:2',
        'facturacion_activa' => 'boolean',
        'enviar_automatico' => 'boolean',
        'modo_pruebas' => 'boolean',
    ];

    protected $hidden = [
        'api_token',
    ];

    /**
     * Obtiene la configuración activa (singleton pattern).
     */
    public static function getActive(): ?self
    {
        return static::first();
    }

    /**
     * Obtiene el siguiente correlativo para un tipo de documento.
     */
    public function getNextCorrelativo(string $tipoDoc, string $tipoDocAfectado = null): array
    {
        switch ($tipoDoc) {
            case '01': // Factura
                $serie = $this->serie_factura;
                $correlativo = ++$this->correlativo_factura;
                break;
            case '03': // Boleta
                $serie = $this->serie_boleta;
                $correlativo = ++$this->correlativo_boleta;
                break;
            case '07': // Nota de crédito
                if ($tipoDocAfectado === '01') {
                    $serie = $this->serie_nota_credito_factura;
                    $correlativo = ++$this->correlativo_nota_credito_factura;
                } else {
                    $serie = $this->serie_nota_credito_boleta;
                    $correlativo = ++$this->correlativo_nota_credito_boleta;
                }
                break;
            case '08': // Nota de débito
                if ($tipoDocAfectado === '01') {
                    $serie = $this->serie_nota_debito_factura;
                    $correlativo = ++$this->correlativo_nota_debito_factura;
                } else {
                    $serie = $this->serie_nota_debito_boleta;
                    $correlativo = ++$this->correlativo_nota_debito_boleta;
                }
                break;
            default:
                throw new \InvalidArgumentException("Tipo de documento no soportado: {$tipoDoc}");
        }

        $this->save();

        return [
            'serie' => $serie,
            'correlativo' => (string) $correlativo,
        ];
    }

    /**
     * Verifica si la facturación está correctamente configurada.
     */
    public function isReady(): bool
    {
        return $this->facturacion_activa
            && !empty($this->ruc)
            && !empty($this->razon_social)
            && !empty($this->api_token)
            && !empty($this->api_url);
    }
}
