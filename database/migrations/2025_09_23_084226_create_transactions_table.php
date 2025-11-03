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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'expense'])->default('income'); // ingreso o egreso
            $table->string('category'); // categoria (venta, compra, gasto operativo, etc.)
            $table->string('description');
            $table->decimal('amount', 10, 2)->default(0);
            $table->string('reference_type')->nullable(); // sale, purchase, other
            $table->unsignedBigInteger('reference_id')->nullable(); // ID de venta/compra relacionada
            $table->string('payment_method')->nullable(); // efectivo, tarjeta, transferencia
            $table->text('notes')->nullable();
            $table->date('transaction_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
