<?php

declare(strict_types=1);

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
        Schema::create('invoice_items', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('invoice_id')
                ->constrained('invoices')
                ->cascadeOnDelete();
            
            // Description of the line item
            $table->string('description');
            
            // Pricing
            $table->integer('quantity')->default(1);
            $table->decimal('unit_price', 12, 2);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2);
            
            // Polymorphic: Source entity (Booking, Product, etc.) - optional
            $table->string('origin_type')->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->index(['origin_type', 'origin_id'], 'invoice_items_origin_index');
            
            // Additional metadata per item
            $table->json('metadata')->nullable();
            
            // Ordering
            $table->unsignedInteger('sort_order')->default(0);
            
            $table->timestamps();
            
            // Performance index
            $table->index('invoice_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
    }
};
