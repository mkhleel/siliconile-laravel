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
        Schema::create('network_sync_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('member_id')
                ->nullable()
                ->constrained('members')
                ->nullOnDelete();
            $table->string('action', 50); // Enum: created, updated, enabled, disabled, kicked, etc.
            $table->string('status', 20); // success, failed
            $table->text('error_message')->nullable();
            $table->json('metadata')->nullable(); // Additional context data
            $table->string('router_ip', 45)->nullable();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['member_id', 'created_at']);
            $table->index(['action', 'status', 'created_at']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('network_sync_logs');
    }
};
