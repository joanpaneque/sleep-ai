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
        Schema::create('youtube_channel_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('youtube_channel_id')->nullable();
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->string('country')->nullable();
            $table->timestamp('published_at')->nullable();

            // Statistics
            $table->bigInteger('subscriber_count')->default(0);
            $table->bigInteger('video_count')->default(0);
            $table->bigInteger('view_count')->default(0);
            $table->boolean('hidden_subscriber_count')->default(false);

            // Branding
            $table->string('banner_image_url')->nullable();
            $table->string('profile_image_url')->nullable();
            $table->string('channel_keywords')->nullable();
            $table->string('default_language')->nullable();

            // Calculated metrics
            $table->decimal('avg_views_per_video', 15, 2)->default(0);
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->decimal('growth_rate_30d', 8, 4)->default(0);
            $table->integer('videos_last_30d')->default(0);

            // Sync metadata
            $table->timestamp('last_synced_at');
            $table->boolean('sync_successful')->default(true);
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'last_synced_at']);
            $table->index('youtube_channel_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_channel_stats');
    }
};
