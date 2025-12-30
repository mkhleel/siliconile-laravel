<?php

use App\Enums\PlanType;
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
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Daily Pass", "Monthly Premium"
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            
            $table->string('type')->index(); // PlanType enum
            $table->integer('duration_days'); // Number of days this plan is valid for
            
            $table->decimal('price', 10, 2); // Plan price
            $table->string('currency', 3)->default('EGP');
            
            // Features & Limits
            $table->boolean('wifi_access')->default(true);
            $table->boolean('meeting_room_access')->default(false);
            $table->integer('meeting_hours_included')->default(0); // Hours per period
            $table->boolean('private_desk')->default(false);
            $table->boolean('locker_access')->default(false);
            $table->integer('guest_passes')->default(0); // Number of guest passes included
            
            // Availability
            $table->boolean('is_active')->default(true)->index();
            $table->integer('max_members')->nullable(); // Limit for special plans
            $table->integer('current_members')->default(0); // Track usage
            
            // Display Order
            $table->integer('sort_order')->default(0);
            $table->boolean('is_featured')->default(false);
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['is_active', 'sort_order']);
            $table->index(['type', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plans');
    }
};
