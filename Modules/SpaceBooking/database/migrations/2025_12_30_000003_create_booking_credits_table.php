<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Tracks allocated and used booking credits per member per resource type.
     * This enables the "5 free meeting room hours/month for Gold Members" feature.
     */
    public function up(): void
    {
        Schema::create('booking_credits', function (Blueprint $table) {
            $table->id();
            
            // Member who owns these credits (from Membership module)
            $table->foreignId('member_id')
                ->constrained('members')
                ->cascadeOnDelete();
            
            // Which resource type these credits apply to
            $table->string('resource_type'); // meeting_room, hot_desk, private_office
            
            // Credit allocation period
            $table->date('period_start');
            $table->date('period_end');
            
            // Credit amounts (in the resource's price_unit: hours for rooms, days for desks)
            $table->decimal('allocated_credits', 10, 2)->default(0);
            $table->decimal('used_credits', 10, 2)->default(0);
            
            // Source of credits
            $table->foreignId('plan_id')->nullable()
                ->constrained('plans')
                ->nullOnDelete();
            $table->foreignId('subscription_id')->nullable()
                ->constrained('subscriptions')
                ->nullOnDelete();
            
            $table->timestamps();
            
            // Unique constraint: one credit allocation per member per type per period
            $table->unique(['member_id', 'resource_type', 'period_start', 'period_end'], 'unique_member_credit_period');
            
            // Index for quick lookup of available credits
            $table->index(['member_id', 'resource_type', 'period_end']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('booking_credits');
    }
};
