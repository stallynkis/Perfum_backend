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
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->enum('type', ['income', 'withdrawal', 'expense']); // Ingreso, Retiro, Gasto
            $table->enum('category', ['negocio', 'personal'])->default('negocio');
            $table->decimal('amount', 10, 2);
            $table->text('description')->nullable();
            $table->unsignedBigInteger('cash_register_id')->nullable(); // Opcional
            $table->unsignedBigInteger('user_id')->nullable(); // Quien registró (nullable)
            $table->date('transaction_date'); // Fecha de la transacción
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
