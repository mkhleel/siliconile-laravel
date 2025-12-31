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
        Schema::create('events', function (Blueprint $table) {
            $table->id();

            // Basic Info
            $table->string('title');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('short_description')->nullable();

            // Event Type: workshop, course, meetup
            $table->string('type')->default('meetup');

            // Date & Time
            $table->dateTime('start_date');
            $table->dateTime('end_date')->nullable();
            $table->string('timezone')->default('Asia/Riyadh');

            // Location - can be physical or virtual
            $table->enum('location_type', ['physical', 'virtual', 'hybrid'])->default('physical');
            $table->string('location_name')->nullable();
            $table->text('location_address')->nullable();
            $table->string('location_link')->nullable(); // For maps or virtual meeting link
            $table->foreignId('space_resource_id')->nullable()->constrained('space_resources')->nullOnDelete();

            // Media
            $table->string('banner_image')->nullable();
            $table->string('thumbnail_image')->nullable();

            // Capacity
            $table->unsignedInteger('total_capacity')->nullable(); // Null = unlimited
            $table->unsignedInteger('registered_count')->default(0); // Denormalized counter

            // Pricing
            $table->boolean('is_free')->default(true);
            $table->string('currency', 3)->default('EGP');

            // Multi-session support for courses
            $table->boolean('is_multi_session')->default(false);
            $table->unsignedInteger('session_count')->nullable();

            // Status & Visibility
            $table->string('status')->default('draft'); // draft, published, cancelled, completed
            $table->boolean('is_featured')->default(false);
            $table->boolean('allow_waitlist')->default(false);
            $table->boolean('require_approval')->default(false); // For invite-only events

            // Registration Settings
            $table->dateTime('registration_start_date')->nullable();
            $table->dateTime('registration_end_date')->nullable();
            $table->boolean('allow_guest_registration')->default(true);
            $table->unsignedInteger('max_tickets_per_order')->default(5);

            // Organizer
            $table->foreignId('organizer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('organizer_name')->nullable();
            $table->string('organizer_email')->nullable();

            // SEO & Metadata
            $table->string('meta_title')->nullable();
            $table->string('meta_description')->nullable();
            $table->json('tags')->nullable();
            $table->json('metadata')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // Indexes for performance
            $table->index('slug');
            $table->index('type');
            $table->index('status');
            $table->index('start_date');
            $table->index(['status', 'start_date']);
            $table->index('is_featured');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
