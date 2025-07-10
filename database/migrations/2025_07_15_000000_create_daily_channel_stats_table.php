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
        Schema::create('daily_channel_stats', function (Blueprint $table) {
            $table->id();
            $table->timestamp('datetime');  // Cambiado de date a timestamp
            $table->unsignedBigInteger('total_views')->default(0);
            $table->unsignedInteger('total_channels')->default(0);
            $table->unsignedInteger('total_videos')->default(0);
            $table->decimal('avg_views_per_video', 10, 2)->default(0);
            $table->decimal('avg_views_per_channel', 10, 2)->default(0);
            $table->timestamps();

            // Índice para búsquedas rápidas por datetime
            $table->index('datetime');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('daily_channel_stats');
    }
}; 