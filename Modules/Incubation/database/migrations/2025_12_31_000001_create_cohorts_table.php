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
        Schema::create('cohorts', function (Blueprint $table) {
            $table->id();

            // Identity
            $table->string('name'); // e.g., "Cycle 1 - Winter 2025"
            $table->string('slug')->unique();
            $table->text('description')->nullable();

            // Timeline
            $table->date('application_start_date')->nullable();
            $table->date('application_end_date')->nullable();
            $table->date('start_date');
            $table->date('end_date');

            // Capacity
            $table->unsignedInteger('capacity')->default(10);
            $table->unsignedInteger('accepted_count')->default(0);

            // Status: draft, open_for_applications, reviewing, active, completed, archived
            $table->string('status')->default('draft')->index();

            // Program Details
            $table->json('eligibility_criteria')->nullable(); // JSON array of criteria
            $table->json('benefits')->nullable(); // JSON array of benefits
            $table->string('program_manager')->nullable(); // Name or user reference
            $table->foreignId('program_manager_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Media
            $table->string('cover_image')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['status', 'start_date']);
            $table->index('application_end_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cohorts');
    }
};
