<?php

use App\Enums\ApplicationStatus;
use App\Enums\Gender;
use App\Enums\MembershipStatus;
use App\Enums\UserType;
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
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name')->index(); // Index for search/filtering
            $table->string('email')->unique();
            $table->string('phone', 20)->nullable()->index(); // Index for lookup
            $table->string('password');

            // Member Type & Application Status
            $table->string('type')->nullable()->index(); // UserType enum
            $table->string('application_status')
                ->default(ApplicationStatus::PENDING->value)
                ->index(); // ApplicationStatus enum - Track application workflow
            $table->string('membership_status')
                ->default(MembershipStatus::INACTIVE->value)
                ->index(); // MembershipStatus enum - Current membership state
            
            // Type-Specific Fields
            // Freelancer
            $table->string('job_title')->nullable();
            $table->text('skills')->nullable(); // Changed to text for longer content
            
            // Company
            $table->string('company_name')->nullable()->index(); // Index for company search
            $table->string('company_field')->nullable();
            $table->string('company_registration_number')->nullable(); // For verification
            
            // Student
            $table->string('university')->nullable();
            $table->string('faculty')->nullable();
            $table->string('grade')->nullable();
            $table->string('student_id')->nullable(); // University ID for verification

            // Contact & Location
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('Egypt');
            $table->string('whatsapp', 20)->nullable();
            
            // Emergency Contact (Important for physical space access)
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('emergency_contact_relationship')->nullable();

            // Profile & Verification
            $table->string('photo')->nullable();
            $table->string('gender')->nullable(); // Gender enum
            $table->date('dob')->nullable();
            $table->string('national_id', 50)->nullable()->unique(); // Government ID for verification
            $table->string('national_id_document')->nullable(); // Path to uploaded ID scan
            
            // Membership Tracking
            $table->date('membership_started_at')->nullable()->index(); // When they became active member
            $table->date('membership_expires_at')->nullable()->index(); // For automatic expiry checks
            $table->foreignId('current_plan_id')->nullable()->constrained('plans')->nullOnDelete(); // Link to active plan
            
            // Verification & Admin
            $table->timestamp('verified_at')->nullable(); // When admin approved the application
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete(); // Which admin verified
            $table->text('admin_notes')->nullable(); // Internal notes for staff
            $table->text('rejection_reason')->nullable(); // If application rejected, why?

            // Authentication
            $table->timestamp('email_verified_at')->nullable();
            $table->rememberToken();
            
            // Timestamps & Soft Deletes
            $table->timestamps();
            $table->softDeletes(); // Keep historical data for audit trail

            // Composite Indexes for Common Queries
            $table->index(['membership_status', 'membership_expires_at']); // Find expiring members
            $table->index(['application_status', 'created_at']); // Process applications in order
            $table->index(['type', 'membership_status']); // Filter by type and status
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};
