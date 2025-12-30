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
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            
            // Feature Definition
            $table->string('feature_key')->index(); // e.g., "24_7_access", "free_printing"
            $table->string('feature_name'); // Human-readable: "24/7 Access"
            $table->string('feature_type')->default('boolean'); // boolean, integer, string
            $table->text('feature_value')->nullable(); // Actual value
            $table->text('description')->nullable();
            
            // Display Order
            $table->integer('sort_order')->default(0);
            $table->boolean('is_visible')->default(true); // Show on pricing table
            
            $table->timestamps();
            
            // Composite unique key
            $table->unique(['plan_id', 'feature_key']);
            $table->index(['plan_id', 'is_visible']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_features');
    }
};
