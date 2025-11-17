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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source', 10)->default('web'); // web o seller
            
            // Customer information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('customer_document')->nullable();
            
            // Delivery information
            $table->enum('delivery_type', ['home', 'agency'])->default('home');
            $table->text('shipping_address')->nullable();
            $table->string('shipping_district')->nullable();
            $table->text('shipping_reference')->nullable();
            
            // Agency delivery info (Olva, Shalom)
            $table->enum('agency_type', ['olva', 'shalom'])->nullable();
            $table->string('agency_id')->nullable();
            $table->string('agency_name')->nullable();
            $table->text('agency_address')->nullable();
            
            // Order items and pricing (stored as JSON)
            $table->json('items');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2)->default(0);
            
            // Payment information
            $table->string('payment_method')->default('paypal');
            $table->string('transaction_id')->nullable();
            $table->string('approval_code')->nullable();
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Order status
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            
            // Notes
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Admin confirmation flag
            $table->boolean('requires_admin_confirmation')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
