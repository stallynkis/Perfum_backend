<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null');
            
            // Source: 'web' (tienda online) o 'seller' (vendedores)
            $table->enum('source', ['web', 'seller'])->default('web');
            
            // Customer Information
            $table->string('customer_name');
            $table->string('customer_email');
            $table->string('customer_phone');
            $table->string('customer_document')->nullable();
            
            // Shipping Information
            $table->enum('delivery_type', ['home', 'agency'])->default('home');
            $table->text('shipping_address')->nullable();
            $table->string('shipping_district')->nullable();
            $table->text('shipping_reference')->nullable();
            
            // Agency Information (for agency pickup)
            $table->enum('agency_type', ['olva', 'shalom'])->nullable();
            $table->string('agency_id')->nullable();
            $table->string('agency_name')->nullable();
            $table->text('agency_address')->nullable();
            
            // Order Details
            $table->json('items'); // Array of products
            $table->decimal('subtotal', 10, 2);
            $table->decimal('tax', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2)->default(0);
            $table->decimal('total', 10, 2);
            
            // Payment Information
            $table->enum('payment_method', ['paypal', 'yape', 'cash', 'card', 'transfer'])->default('paypal');
            $table->string('transaction_id')->nullable();
            $table->string('approval_code')->nullable(); // For Yape
            $table->enum('payment_status', ['pending', 'paid', 'failed', 'refunded'])->default('pending');
            
            // Order Status
            $table->enum('status', ['pending', 'processing', 'shipped', 'delivered', 'cancelled'])->default('pending');
            
            // Additional Information
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->boolean('requires_admin_confirmation')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index('order_number');
            $table->index('customer_email');
            $table->index('status');
            $table->index('payment_status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
