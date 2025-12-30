<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Billing\Enums\OrderStatus;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('orders');

        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('order_number')->unique();
            $table->string('status')->default(OrderStatus::PENDING);

            // Polymorphic relation for orderable
            $table->nullableMorphs('orderable');

            // Financial fields
            $table->string('currency', 3)->default('USD');
            $table->decimal('subtotal', 10, 2)->default(0);
            $table->decimal('discount_total', 10, 2)->default(0)->nullable();
            $table->decimal('tax', 10, 2)->default(0)->nullable();
            $table->decimal('total', 10, 2)->default(0);

            // Payment information
            $table->string('payment_gateway')->nullable();
            $table->timestamp('paid_at')->nullable();

            // Address information
            $table->json('billing_address')->nullable();
            $table->json('shipping_address')->nullable();

            // Additional fields
            $table->text('note')->nullable();
            $table->json('meta')->nullable();

            $table->timestamps();

        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
