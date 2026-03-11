<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('billing_configs', function (Blueprint $table) {
            $table->id();

            // Datos del emisor (empresa)
            $table->string('ruc', 11);
            $table->string('razon_social', 200);
            $table->string('nombre_comercial', 200)->nullable();
            $table->string('direccion', 300)->nullable();
            $table->string('ubigeo', 6)->nullable();
            $table->string('departamento', 100)->nullable();
            $table->string('provincia', 100)->nullable();
            $table->string('distrito', 100)->nullable();

            // Series para comprobantes
            $table->string('serie_factura', 10)->default('F001');
            $table->integer('correlativo_factura')->default(0);
            $table->string('serie_boleta', 10)->default('B001');
            $table->integer('correlativo_boleta')->default(0);
            $table->string('serie_nota_credito_factura', 10)->default('FC01');
            $table->integer('correlativo_nota_credito_factura')->default(0);
            $table->string('serie_nota_credito_boleta', 10)->default('BC01');
            $table->integer('correlativo_nota_credito_boleta')->default(0);
            $table->string('serie_nota_debito_factura', 10)->default('FD01');
            $table->integer('correlativo_nota_debito_factura')->default(0);
            $table->string('serie_nota_debito_boleta', 10)->default('BD01');
            $table->integer('correlativo_nota_debito_boleta')->default(0);

            // Configuración de la API FactuFlash
            $table->string('api_url', 255)->default('https://factuflash.pe/api');
            $table->text('api_token')->nullable();

            // Configuración general
            $table->string('tipo_moneda', 3)->default('PEN');
            $table->decimal('igv_porcentaje', 5, 2)->default(18.00);
            $table->boolean('facturacion_activa')->default(false);
            $table->boolean('enviar_automatico')->default(false);
            $table->boolean('modo_pruebas')->default(true);

            $table->timestamps();
        });

        // Tabla para registrar comprobantes emitidos
        Schema::create('billing_documents', function (Blueprint $table) {
            $table->id();
            $table->string('tipo_doc', 2); // 01=Factura, 03=Boleta, 07=NC, 08=ND
            $table->string('serie', 10);
            $table->integer('correlativo');
            $table->date('fecha_emision');
            $table->string('tipo_moneda', 3)->default('PEN');

            // Referencia al origen
            $table->string('origin_type', 50)->nullable(); // sale, order
            $table->unsignedBigInteger('origin_id')->nullable();

            // Cliente
            $table->string('cliente_tipo_doc', 2); // 6=RUC, 1=DNI, 0=Sin doc
            $table->string('cliente_num_doc', 20);
            $table->string('cliente_razon_social', 200);

            // Montos
            $table->decimal('mto_oper_gravadas', 12, 2)->default(0);
            $table->decimal('mto_oper_exoneradas', 12, 2)->default(0);
            $table->decimal('mto_oper_inafectas', 12, 2)->default(0);
            $table->decimal('mto_igv', 12, 2)->default(0);
            $table->decimal('total_impuestos', 12, 2)->default(0);
            $table->decimal('valor_venta', 12, 2)->default(0);
            $table->decimal('sub_total', 12, 2)->default(0);
            $table->decimal('mto_imp_venta', 12, 2)->default(0);

            // Para notas de crédito/débito
            $table->string('tipo_doc_afectado', 2)->nullable();
            $table->string('num_doc_afectado', 20)->nullable();
            $table->string('cod_motivo', 2)->nullable();
            $table->string('des_motivo', 200)->nullable();

            // Respuesta SUNAT
            $table->enum('estado', ['pendiente', 'aceptada', 'rechazada', 'anulada', 'error'])->default('pendiente');
            $table->string('sunat_code', 10)->nullable();
            $table->text('sunat_description')->nullable();
            $table->text('sunat_notes')->nullable();
            $table->string('xml_path', 500)->nullable();
            $table->string('cdr_path', 500)->nullable();
            $table->string('pdf_path', 500)->nullable();
            $table->string('ticket')->nullable(); // Para resúmenes/comunicaciones

            // Items (JSON)
            $table->json('items')->nullable();
            // Payload completo enviado (para debug)
            $table->json('payload_enviado')->nullable();
            // Respuesta completa (para debug)
            $table->json('respuesta_completa')->nullable();

            $table->timestamps();

            $table->index(['tipo_doc', 'serie', 'correlativo']);
            $table->index(['origin_type', 'origin_id']);
            $table->index('estado');
            $table->index('fecha_emision');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('billing_documents');
        Schema::dropIfExists('billing_configs');
    }
};
