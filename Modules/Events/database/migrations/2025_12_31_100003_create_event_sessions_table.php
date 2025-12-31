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
     * Event Sessions for multi-day courses/workshops.
     * Each session can have its own date, time, and location.
     */
    public function up(): void
    {
        Schema::create('event_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('event_id')
                ->constrained('events')
                ->cascadeOnDelete();

            $table->string('title');
            $table->text('description')->nullable();

            // Session timing
            $table->dateTime('start_time');
            $table->dateTime('end_time')->nullable();

            // Location (can differ from main event)
            $table->string('location_name')->nullable();
            $table->text('location_address')->nullable();
            $table->string('location_link')->nullable();
            $table->foreignId('space_resource_id')->nullable()->constrained('space_resources')->nullOnDelete();

            // Speaker/Instructor
            $table->string('speaker_name')->nullable();
            $table->string('speaker_bio')->nullable();
            $table->string('speaker_image')->nullable();

            // Session-specific capacity (for breakout sessions)
            $table->unsignedInteger('capacity')->nullable();

            // Sorting
            $table->unsignedInteger('sort_order')->default(0);

            // Status
            $table->string('status')->default('scheduled'); // scheduled, completed, cancelled

            $table->timestamps();

            // Indexes
            $table->index(['event_id', 'start_time']);
            $table->index(['event_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('event_sessions');
    }
};
