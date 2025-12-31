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
        Schema::create('applications', function (Blueprint $table) {
            $table->id();

            // Reference
            $table->string('application_code')->unique(); // e.g., "APP-2025-001"
            $table->foreignId('cohort_id')->constrained('cohorts')->cascadeOnDelete();

            // Applicant Info (can be linked to user or standalone)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('startup_name');
            $table->string('email');
            $table->string('phone')->nullable();

            // Founders Data (JSON structure)
            // [{ "name": "", "email": "", "role": "", "linkedin": "", "bio": "" }]
            $table->json('founders_data');

            // Business Information
            $table->text('problem_statement');
            $table->text('solution');
            $table->string('industry')->nullable();
            $table->string('business_model')->nullable(); // B2B, B2C, B2B2C, etc.
            $table->string('stage')->nullable(); // idea, mvp, growth, scaling
            $table->text('traction')->nullable(); // Description of current progress
            $table->decimal('funding_raised', 12, 2)->nullable();
            $table->string('funding_currency', 3)->default('SDG');

            // Pitch Materials
            $table->string('pitch_deck_url')->nullable();
            $table->string('pitch_deck_path')->nullable(); // Local storage path
            $table->string('video_pitch_url')->nullable();
            $table->string('website_url')->nullable();

            // Additional Info
            $table->text('why_apply')->nullable(); // Why should we accept you?
            $table->json('social_links')->nullable(); // { "linkedin": "", "twitter": "", "facebook": "" }
            $table->text('additional_notes')->nullable();

            // Selection Pipeline Status
            // submitted -> screening -> interview_scheduled -> interviewed -> accepted/rejected
            $table->string('status')->default('submitted')->index();
            $table->string('previous_status')->nullable(); // Track status changes

            // Scoring (for internal evaluation)
            $table->decimal('score', 5, 2)->nullable(); // 0-100 score
            $table->json('evaluation_scores')->nullable(); // { "innovation": 8, "team": 7, "market": 9 }
            $table->text('internal_notes')->nullable(); // Private notes for reviewers

            // Interview Scheduling
            $table->timestamp('interview_scheduled_at')->nullable();
            $table->string('interview_location')->nullable(); // "Online" or physical address
            $table->string('interview_meeting_link')->nullable();
            $table->text('interview_notes')->nullable();

            // Decision
            $table->timestamp('decision_at')->nullable();
            $table->foreignId('decided_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('rejection_reason')->nullable();

            // Onboarding (when accepted)
            $table->foreignId('onboarded_member_id')->nullable(); // Link to Membership module
            $table->timestamp('onboarded_at')->nullable();

            // Source Tracking
            $table->string('source')->nullable(); // "website", "referral", "event", etc.
            $table->string('referral_source')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index(['cohort_id', 'status']);
            $table->index(['status', 'created_at']);
            $table->index('email');
            $table->index('startup_name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
