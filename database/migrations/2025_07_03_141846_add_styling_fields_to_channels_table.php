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
        Schema::table('channels', function (Blueprint $table) {
            $table->text('background_video')->nullable();
            $table->text('frame_image')->nullable();
            $table->text('image_style_prompt')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('channels', function (Blueprint $table) {
            $table->dropColumn('background_video');
            $table->dropColumn('frame_image');
            $table->dropColumn('image_style_prompt');
        });
    }
};
