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
     * Normalized amenities table for better filtering and management.
     * Many-to-many relationship with space_resources.
     */
    public function up(): void
    {
        Schema::create('resource_amenities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('icon')->nullable(); // Heroicon name
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        // Pivot table for space_resources <-> amenities
        Schema::create('space_resource_amenity', function (Blueprint $table) {
            $table->id();
            $table->foreignId('space_resource_id')
                ->constrained('space_resources')
                ->cascadeOnDelete();
            $table->foreignId('resource_amenity_id')
                ->constrained('resource_amenities')
                ->cascadeOnDelete();
            
            $table->unique(['space_resource_id', 'resource_amenity_id'], 'resource_amenity_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('space_resource_amenity');
        Schema::dropIfExists('resource_amenities');
    }
};
