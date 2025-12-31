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
        Schema::table('users', function (Blueprint $table) {
            $table->text('motivation')->nullable()->after('admin_notes');
            $table->text('startup_idea')->nullable()->after('motivation');
            $table->text('visited_coworking_space_before')->nullable()->after('startup_idea');
            $table->text('how_found_us')->nullable()->after('visited_coworking_space_before');
            $table->boolean('marketing_messages_accepted')->default(false)->after('how_found_us');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'motivation',
                'startup_idea',
                'visited_coworking_space_before',
                'how_found_us',
                'marketing_messages_accepted',
            ]);
        });
    }
};
