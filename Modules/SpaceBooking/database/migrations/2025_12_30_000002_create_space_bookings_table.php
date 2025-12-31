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
     * Critical: Composite index on (space_resource_id, start_time, end_time, status)
     * enables fast overlap detection queries for conflict-free scheduling.
     */
    public function up(): void
    {
        Schema::create('space_bookings', function (Blueprint $table) {
            $table->id();
            $table->uuid('booking_code')->unique();

            // Resource being booked
            $table->foreignId('space_resource_id')
                ->constrained('space_resources')
                ->cascadeOnDelete();

            // Who made the booking - polymorphic to support User or Member
            $table->morphs('bookable'); // bookable_type, bookable_id

            // Time slot (Critical for overlap detection)
            $table->dateTime('start_time');
            $table->dateTime('end_time');

            // Actual usage times (for check-in/check-out tracking)
            $table->dateTime('checked_in_at')->nullable();
            $table->dateTime('checked_out_at')->nullable();

            // Booking status workflow
            $table->string('status')->default('pending'); // pending, confirmed, cancelled, completed, no_show

            // Pricing at time of booking (snapshot)
            $table->decimal('unit_price', 10, 2);
            $table->string('price_unit'); // hour, day, month
            $table->unsignedInteger('quantity'); // number of units
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('total_price', 10, 2);
            $table->string('currency', 3)->default('EGP');

            // Credits used from member's plan
            $table->decimal('credits_used', 10, 2)->default(0);

            // Additional info
            $table->text('notes')->nullable();
            $table->text('admin_notes')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->dateTime('cancelled_at')->nullable();

            // Number of attendees (for meeting rooms)
            $table->unsignedSmallInteger('attendees_count')->nullable();

            // Recurring booking reference (if part of a series)
            $table->foreignId('parent_booking_id')
                ->nullable()
                ->constrained('space_bookings')
                ->nullOnDelete();

            // Payment tracking
            $table->string('payment_status')->default('unpaid'); // unpaid, paid, refunded, partial
            $table->foreignId('order_id')->nullable(); // Link to Billing module

            $table->timestamps();
            $table->softDeletes();

            // CRITICAL INDEX: For fast overlap detection queries
            // Query pattern: WHERE space_resource_id = ? AND start_time < ? AND end_time > ? AND status IN (...)
            $table->index(['space_resource_id', 'start_time', 'end_time', 'status'], 'booking_overlap_check');

            // For user's booking history
            $table->index(['bookable_type', 'bookable_id', 'start_time']);

            // For calendar views
            $table->index(['start_time', 'status']);
            $table->index(['space_resource_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_bookings');
    }
};
