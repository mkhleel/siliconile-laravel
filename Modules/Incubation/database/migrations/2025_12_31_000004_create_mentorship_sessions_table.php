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
        Schema::create('mentorship_sessions', function (Blueprint $table) {
            $table->id();

            // Session Reference
            $table->string('session_code')->unique(); // e.g., "MS-2025-001"

            // Participants
            $table->foreignId('mentor_id')->constrained('mentors')->cascadeOnDelete();
            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();

            // Session Details
            $table->string('title')->nullable(); // Optional session topic
            $table->text('description')->nullable();
            $table->string('type')->default('one_on_one'); // one_on_one, group, workshop

            // Scheduling
            $table->timestamp('scheduled_at');
            $table->unsignedInteger('duration_minutes')->default(60);
            $table->timestamp('ended_at')->nullable();

            // Location/Meeting
            $table->string('location_type')->default('online'); // online, in_person, hybrid
            $table->string('location')->nullable(); // Physical address or room name
            $table->string('meeting_link')->nullable(); // Zoom/Google Meet link

            // Status: pending, confirmed, in_progress, completed, cancelled, no_show
            $table->string('status')->default('pending')->index();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Notes & Outcomes
            $table->text('mentor_notes')->nullable(); // Private notes from mentor
            $table->text('startup_notes')->nullable(); // Notes from startup
            $table->text('summary')->nullable(); // Session summary
            $table->json('action_items')->nullable(); // [{ "task": "", "assignee": "", "due_date": "" }]

            // Feedback
            $table->unsignedTinyInteger('startup_rating')->nullable(); // 1-5 rating from startup
            $table->text('startup_feedback')->nullable();
            $table->unsignedTinyInteger('mentor_rating')->nullable(); // 1-5 rating from mentor
            $table->text('mentor_feedback')->nullable();

            // Booking metadata
            $table->foreignId('booked_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['mentor_id', 'scheduled_at']);
            $table->index(['application_id', 'scheduled_at']);
            $table->index(['status', 'scheduled_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentorship_sessions');
    }
};
