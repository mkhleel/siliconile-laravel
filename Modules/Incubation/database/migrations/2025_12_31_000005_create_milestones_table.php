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
        Schema::create('milestones', function (Blueprint $table) {
            $table->id();

            // Link to Cohort
            $table->foreignId('cohort_id')->constrained('cohorts')->cascadeOnDelete();

            // Milestone Details
            $table->string('name'); // e.g., "MVP Launch", "First Customer", "First Investment"
            $table->text('description')->nullable();
            $table->string('category')->nullable(); // product, business, funding, etc.

            // Timeline
            $table->date('target_date')->nullable();
            $table->unsignedInteger('week_number')->nullable(); // Week of the program

            // Ordering
            $table->unsignedInteger('sort_order')->default(0);

            // Requirements
            $table->json('requirements')->nullable(); // [{ "item": "Launch landing page", "required": true }]
            $table->boolean('is_required')->default(false);

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['cohort_id', 'sort_order']);
        });

        // Pivot table: Track which applications achieved which milestones
        Schema::create('application_milestone', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();
            $table->foreignId('milestone_id')->constrained('milestones')->cascadeOnDelete();

            // Achievement Details
            $table->timestamp('achieved_at');
            $table->text('notes')->nullable();
            $table->string('evidence_url')->nullable(); // Link to proof
            $table->foreignId('verified_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            // Prevent duplicate entries
            $table->unique(['application_id', 'milestone_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_milestone');
        Schema::dropIfExists('milestones');
    }
};
