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
        Schema::create('members', function (Blueprint $table) {
            $table->id();
            
            // Core Identity
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('member_code')->unique(); // e.g., "MEM-2025-001"
            
            // Member Type: individual or corporate
            $table->enum('member_type', ['individual', 'corporate'])->default('individual')->index();
            
            // Corporate Support (for team accounts)
            $table->foreignId('parent_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->string('company_name')->nullable();
            $table->string('company_vat_number')->nullable();
            $table->text('company_address')->nullable();
            
            // CRM Data
            $table->text('bio')->nullable();
            $table->json('interests')->nullable(); // ["Entrepreneurship", "Tech"]
            $table->string('linkedin_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('website_url')->nullable();
            
            // Preferences
            $table->json('workspace_preferences')->nullable(); // {"preferred_desk_area": "quiet_zone"}
            $table->json('notification_preferences')->nullable(); // {"email_weekly_digest": true}
            
            // Referral Tracking
            $table->foreignId('referred_by_member_id')->nullable()->constrained('members')->nullOnDelete();
            $table->integer('referral_count')->default(0);
            
            // Status
            $table->boolean('is_active')->default(true)->index();
            $table->text('deactivation_reason')->nullable();
            $table->timestamp('deactivated_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['member_type', 'is_active']);
            $table->index('parent_member_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
