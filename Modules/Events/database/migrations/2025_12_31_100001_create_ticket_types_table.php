<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Ticket Types represent different pricing tiers for an event.
     * Examples: "Early Bird", "Regular", "VIP", "Student Discount"
     */
    public function up(): void
    {
        Schema::create('ticket_types', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            // Ticket Details
            $table->string('name'); // e.g., "Early Bird", "VIP", "Standard"
            $table->text('description')->nullable();

            // Pricing
            $table->decimal('price', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('EGP');
            $table->boolean('is_free')->default(false);

            // Stock Management - CRITICAL for concurrency control
            $table->unsignedInteger('quantity')->nullable(); // Null = unlimited
            $table->unsignedInteger('quantity_sold')->default(0);
            $table->unsignedInteger('quantity_reserved')->default(0); // For pending payments

            // Per-order limits
            $table->unsignedInteger('min_per_order')->default(1);
            $table->unsignedInteger('max_per_order')->default(10);

            // Sale Period
            $table->dateTime('sale_start_date')->nullable();
            $table->dateTime('sale_end_date')->nullable();

            // Visibility & Access
            $table->string('status')->default('active'); // active, paused, sold_out, expired
            $table->boolean('is_hidden')->default(false); // Hidden tickets (promo codes only)
            $table->boolean('requires_promo_code')->default(false);

            // Benefits/Perks (JSON for flexibility)
            $table->json('benefits')->nullable(); // ["WiFi Access", "Lunch Included", "Certificate"]

            // Sorting
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Indexes
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'sale_start_date', 'sale_end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_types');
    }
};
