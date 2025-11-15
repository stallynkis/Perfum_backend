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
        // Solo crear si no existe
        if (!Schema::hasTable('notifications')) {
            Schema::create('notifications', function (Blueprint $table) {
                $table->id();
                
                // Tipo de notificación
                $table->enum('type', ['order', 'sale', 'contact', 'system', 'payment', 'stock', 'new_order', 'order_status_change', 'payment_confirmed'])->default('system');
                
                // Contenido
                $table->string('title');
                $table->text('message');
                
                // Prioridad
                $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
                
                // Estado
                $table->boolean('read')->default(false);
                
                // Navegación
                $table->string('related_tab')->nullable(); // orders, vendor_sales, web_sales, contact, etc.
                $table->string('related_id')->nullable(); // ID de la operación relacionada
                
                // Relaciones opcionales
                $table->foreignId('order_id')->nullable()->constrained('orders')->onDelete('cascade');
                $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('vendor_id')->nullable()->constrained('users')->onDelete('cascade');
                $table->foreignId('contact_form_id')->nullable()->constrained('contact_forms')->onDelete('cascade');
                
                // Datos adicionales en JSON
                $table->json('data')->nullable();
                
                $table->timestamps();
                
                // Índices para búsqueda rápida
                $table->index('type');
                $table->index('read');
                $table->index('priority');
                $table->index('created_at');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
