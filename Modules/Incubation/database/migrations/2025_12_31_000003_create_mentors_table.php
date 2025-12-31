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
        Schema::create('mentors', function (Blueprint $table) {
            $table->id();

            // Link to User (optional - can be external mentors)
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            // Profile Information
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('title')->nullable(); // Job title
            $table->string('company')->nullable();
            $table->text('bio')->nullable();
            $table->string('profile_photo')->nullable();

            // Expertise (tags stored as JSON array)
            // ["Marketing", "Fundraising", "Technology", "Finance"]
            $table->json('expertise')->nullable();

            // Availability
            $table->boolean('is_active')->default(true)->index('incubation_mentors_is_active_idx');
            $table->json('availability')->nullable(); // { "monday": ["09:00-12:00"], "wednesday": ["14:00-17:00"] }
            $table->unsignedInteger('max_sessions_per_week')->default(5);

            // Social/Contact
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('website_url')->nullable();

            // Statistics
            $table->unsignedInteger('total_sessions')->default(0);
            $table->unsignedInteger('total_mentees')->default(0);
            $table->decimal('avg_rating', 3, 2)->nullable(); // 0.00 - 5.00

            // Internal notes
            $table->text('internal_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('is_active');
            $table->index('name');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mentors');
    }
};
