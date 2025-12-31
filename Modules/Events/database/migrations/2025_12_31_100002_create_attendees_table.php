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
     * Attendees represent individual ticket holders for an event.
     * Supports both registered users and guest registrations.
     */
    public function up(): void
    {
        Schema::create('attendees', function (Blueprint $table) {
            $table->id();

            // Event & Ticket Type
            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->foreignId('ticket_type_id')
                ->constrained('ticket_types')
                ->cascadeOnDelete();

            // User (nullable for guest checkout)
            $table->foreignId('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            // Guest Details (for guest checkout or CRM sync)
            $table->string('guest_name')->nullable();
            $table->string('guest_email')->nullable();
            $table->string('guest_phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();

            // Status Lifecycle
            // pending_payment -> confirmed -> checked_in
            // pending_payment -> cancelled
            // pending_payment -> expired (timeout)
            $table->string('status')->default('pending_payment');

            // Unique Reference & QR Code
            $table->string('reference_no')->unique(); // Human-readable: EVT-2024-XXXX
            $table->string('qr_code_hash', 64)->unique(); // SHA-256 hash for QR validation

            // Payment Tracking (links to Billing/Payment module)
            $table->foreignId('invoice_id')->nullable(); // Links to invoices table
            $table->decimal('amount_paid', 12, 2)->default(0.00);
            $table->string('currency', 3)->default('EGP');

            // Check-in Tracking
            $table->dateTime('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('check_in_method')->nullable(); // qr_scan, manual, bulk

            // Ticket Delivery
            $table->boolean('ticket_sent')->default(false);
            $table->dateTime('ticket_sent_at')->nullable();
            $table->string('ticket_pdf_path')->nullable();

            // Cancellation
            $table->dateTime('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();
            $table->boolean('refund_requested')->default(false);
            $table->boolean('refund_processed')->default(false);

            // CRM Integration (links to CRM Leads if exists)
            $table->unsignedBigInteger('crm_lead_id')->nullable();

            // Additional Info
            $table->json('custom_fields')->nullable(); // For event-specific questions
            $table->json('metadata')->nullable();
            $table->text('special_requirements')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('reference_no');
            $table->index('qr_code_hash');
            $table->index('status');
            $table->index(['event_id', 'status']);
            $table->index(['event_id', 'ticket_type_id']);
            $table->index('guest_email');
            $table->index('checked_in_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendees');
    }
};
