<?php

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
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->nullableMorphs('payable');
            $table->decimal('amount', 10, 2);
            $table->decimal('paid_amount', 10, 2);
            $table->string('currency', 3)->default('USD');
            $table->string('reference')->nullable();
            $table->string('gateway')->nullable();
            $table->mediumText('gateway_response')->nullable();
            $table->string('status');
            $table->timestamp('completed_at')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};
