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
     * Single Table Inheritance (STI) approach for Resource Flexibility:
     * - All resource types (Meeting Rooms, Hot Desks, Private Offices) in one table
     * - `resource_type` enum discriminates the type
     * - `attributes` JSON stores type-specific data (amenities, floor, etc.)
     * - `pricing_rules` JSON stores flexible pricing by user type/plan
     */
    public function up(): void
    {
        Schema::create('space_resources', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('resource_type'); // Enum: meeting_room, hot_desk, private_office
            $table->text('description')->nullable();

            // Physical attributes
            $table->unsignedSmallInteger('capacity')->default(1);
            $table->string('location')->nullable(); // Floor, building, etc.
            $table->string('image')->nullable();

            // Pricing - base rates (can be overridden by pricing_rules)
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('daily_rate', 10, 2)->nullable();
            $table->decimal('monthly_rate', 10, 2)->nullable();
            $table->string('currency', 3)->default('EGP');

            // Buffer time (minutes) between bookings for cleaning/preparation
            $table->unsignedSmallInteger('buffer_minutes')->default(0);

            // Operating hours (null = 24/7)
            $table->time('available_from')->nullable();
            $table->time('available_until')->nullable();

            // Minimum/Maximum booking duration (in minutes)
            $table->unsignedInteger('min_booking_minutes')->default(30);
            $table->unsignedInteger('max_booking_minutes')->nullable();

            // Type-specific attributes stored as JSON
            // Examples: amenities, equipment, desk_number, office_size
            $table->json('attributes')->nullable();

            // Dynamic pricing rules JSON structure:
            // [{"plan_id": 1, "discount_percent": 100, "free_hours_monthly": 5}]
            $table->json('pricing_rules')->nullable();

            // Availability
            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(false);

            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            // Indexes for filtering and searching
            $table->index('resource_type');
            $table->index('is_active');
            $table->index(['resource_type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_resources');
    }
};
