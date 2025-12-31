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
     * Session attendance tracking for multi-session courses.
     * Allows per-session check-in for courses spanning multiple days.
     */
    public function up(): void
    {
        Schema::create('session_attendances', function (Blueprint $table) {
            $table->id();

            $table->foreignId('attendee_id')
                ->constrained('attendees')
                ->cascadeOnDelete();

            $table->foreignId('event_session_id')
                ->constrained('event_sessions')
                ->cascadeOnDelete();

            $table->dateTime('checked_in_at')->nullable();
            $table->foreignId('checked_in_by')->nullable()->constrained('users')->nullOnDelete();

            $table->dateTime('checked_out_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            // Prevent duplicate attendance records
            $table->unique(['attendee_id', 'event_session_id'], 'unique_session_attendance');

            // Indexes
            $table->index(['event_session_id', 'checked_in_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('session_attendances');
    }
};
