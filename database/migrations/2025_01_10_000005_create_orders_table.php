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
        if (!Schema::hasTable('orders')) {
            Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->enum('source', ['web', 'seller'])->default('web');
            
            // Información del cliente
            $table->string('customer_name');
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_document')->nullable();
            
            // Información de entrega
            $table->enum('delivery_type', ['home', 'agency'])->default('home');
            $table->text('shipping_address')->nullable();
            $table->string('shipping_district')->nullable();
            $table->text('shipping_reference')->nullable();
            
            // Información de agencia
            $table->enum('agency_type', ['olva', 'shalom'])->nullable();
            $table->string('agency_id')->nullable();
            $table->string('agency_name')->nullable();
            $table->text('agency_address')->nullable();
            
            // Información del pedido
            $table->json('items');
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Información de pago
            $table->string('payment_method')->default('paypal');
            $table->string('transaction_id')->nullable();
            $table->string('approval_code')->nullable();
            $table->string('payment_status')->default('pending');
            
            // Estado del pedido
            $table->string('status')->default('pending');
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Tracking
            $table->string('tracking_number')->nullable();
            $table->string('carrier')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            
            // Confirmación admin
            $table->boolean('requires_admin_confirmation')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
