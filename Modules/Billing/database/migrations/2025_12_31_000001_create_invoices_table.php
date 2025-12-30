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
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            
            // Sequential invoice number (generated on finalization)
            $table->string('number')->nullable()->unique()->index();
            
            // Polymorphic: Who is being billed (Member or User)
            $table->string('billable_type');
            $table->unsignedBigInteger('billable_id');
            $table->index(['billable_type', 'billable_id'], 'invoices_billable_index');
            
            // Polymorphic: Origin entity (Subscription, Booking, etc.) - optional
            $table->string('origin_type')->nullable();
            $table->unsignedBigInteger('origin_id')->nullable();
            $table->index(['origin_type', 'origin_id'], 'invoices_origin_index');
            
            // Invoice status lifecycle
            $table->string('status')->default('draft');
            
            // Dates
            $table->date('issue_date')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('voided_at')->nullable();
            
            // Financial fields (all stored in minor currency units or decimal)
            $table->string('currency', 3)->default('SAR');
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('discount_description')->nullable();
            $table->decimal('tax_rate', 5, 2)->default(15.00); // VAT rate (15% for Saudi)
            $table->decimal('tax_amount', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);
            
            // Payment tracking
            $table->string('payment_reference')->nullable(); // Transaction ref from Payment module
            $table->string('payment_method')->nullable();
            
            // Billing details (snapshot at invoice time)
            $table->json('billing_details')->nullable();
            
            // Additional metadata
            $table->text('notes')->nullable();
            $table->text('terms')->nullable();
            $table->json('metadata')->nullable();
            
            // PDF caching
            $table->string('pdf_path')->nullable();
            $table->timestamp('pdf_generated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Performance indexes
            $table->index('status', 'invoices_status_idx');
            $table->index('due_date');
            $table->index('issue_date');
            $table->index(['status', 'due_date'], 'invoices_overdue_idx'); // For overdue queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
