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
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            
            // Core Relations
            $table->foreignId('member_id')->constrained('members')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->restrictOnDelete();
            
            // Subscription Lifecycle Dates
            $table->date('start_date')->index();
            $table->date('end_date')->index();
            $table->date('next_billing_date')->nullable()->index();
            
            // Status Management
            $table->enum('status', [
                'pending',      // Awaiting payment
                'active',       // Currently active
                'expiring',     // Will expire soon (7 days)
                'grace_period', // Expired but grace period active
                'expired',      // Expired (no grace period)
                'cancelled',    // User cancelled
                'suspended'     // Admin suspended
            ])->default('pending')->index();
            
            // Renewal Settings
            $table->boolean('auto_renew')->default(false);
            $table->integer('grace_period_days')->default(0); // Days after expiry
            
            // Pricing (snapshot at subscription time)
            $table->decimal('price_at_subscription', 10, 2);
            $table->string('currency', 3)->default('EGP');
            
            // Payment Tracking (loosely coupled)
            $table->foreignId('last_payment_id')->nullable()->constrained('orders', 'id')->nullOnDelete();
            $table->timestamp('last_payment_at')->nullable();
            
            // Lifecycle Tracking
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->foreignId('cancelled_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for querying
            $table->index(['member_id', 'status']);
            $table->index(['status', 'end_date']);
            $table->index(['status', 'next_billing_date']);
            $table->index('auto_renew');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('subscriptions');
    }
};
