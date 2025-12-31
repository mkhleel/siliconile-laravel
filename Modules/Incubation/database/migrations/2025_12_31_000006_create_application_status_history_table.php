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
        // Track all status changes for applications (audit trail)
        Schema::create('application_status_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('application_id')->constrained('applications')->cascadeOnDelete();

            // Status Change
            $table->string('from_status')->nullable();
            $table->string('to_status');

            // Who made the change
            $table->foreignId('changed_by_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Context
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable(); // Additional context data

            $table->timestamp('created_at');

            // Indexes
            $table->index(['application_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('application_status_history');
    }
};
