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
            $table->foreignId('channel_id')->constrained('channels')->onDelete('cascade');
            $table->dateTime('datetime');
            $table->integer('total_views');
            $table->integer('total_videos');
            $table->decimal('avg_views_per_video', 15, 2);
            $table->timestamps();

            // Índice compuesto para búsquedas rápidas por canal y datetime
            $table->unique(['channel_id', 'datetime']);
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