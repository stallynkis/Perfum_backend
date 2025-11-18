<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('business_partners', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nombre del socio
            $table->string('type'); // 'supplier' (proveedor), 'customer' (cliente), 'seller' (vendedor)
            $table->string('ruc')->nullable(); // RUC o DNI
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person')->nullable(); // Persona de contacto
            $table->decimal('credit_limit', 10, 2)->default(0); // Límite de crédito
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('business_partners');
    }
};
