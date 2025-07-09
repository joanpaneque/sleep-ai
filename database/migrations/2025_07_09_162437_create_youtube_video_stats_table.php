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
        Schema::create('youtube_video_stats', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('youtube_video_id')->index();
            $table->string('youtube_channel_id')->nullable();

            // Basic info
            $table->string('title')->nullable();
            $table->text('description')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->string('duration')->nullable(); // ISO 8601 format (PT4M13S)
            $table->integer('duration_seconds')->default(0);
            $table->string('category_id')->nullable();
            $table->json('tags')->nullable();

            // Thumbnails
            $table->string('thumbnail_default')->nullable();
            $table->string('thumbnail_medium')->nullable();
            $table->string('thumbnail_high')->nullable();
            $table->string('thumbnail_standard')->nullable();
            $table->string('thumbnail_maxres')->nullable();

            // Statistics
            $table->bigInteger('view_count')->default(0);
            $table->bigInteger('like_count')->default(0);
            $table->bigInteger('comment_count')->default(0);
            $table->bigInteger('favorite_count')->default(0);

            // Calculated metrics
            $table->decimal('engagement_rate', 8, 4)->default(0);
            $table->decimal('like_rate', 8, 4)->default(0);
            $table->decimal('comment_rate', 8, 4)->default(0);
            $table->decimal('views_per_day', 15, 2)->default(0);
            $table->integer('performance_score')->default(0); // 0-100

            // Status
            $table->string('privacy_status')->nullable(); // public, unlisted, private
            $table->string('upload_status')->nullable(); // uploaded, processed, failed
            $table->boolean('embeddable')->default(true);
            $table->boolean('made_for_kids')->default(false);

            // Sync metadata
            $table->timestamp('last_synced_at');
            $table->boolean('sync_successful')->default(true);
            $table->text('sync_error')->nullable();
            $table->timestamps();

            $table->index(['channel_id', 'last_synced_at']);
            $table->index(['youtube_channel_id', 'published_at']);
            $table->index('view_count');
            $table->index('engagement_rate');
            $table->unique(['channel_id', 'youtube_video_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_video_stats');
    }
};
