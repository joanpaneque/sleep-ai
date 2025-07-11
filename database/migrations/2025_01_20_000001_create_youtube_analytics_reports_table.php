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
        Schema::create('youtube_analytics_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('youtube_channel_id')->nullable();
            $table->date('report_date');
            $table->string('report_type', 50)->default('daily'); // daily, monthly, country, device, etc.
            $table->string('dimension_type', 100)->nullable(); // day, country, deviceType, etc.
            $table->string('dimension_value', 100)->nullable(); // ES, mobile, etc.
            
            // Core metrics
            $table->bigInteger('views')->default(0);
            $table->bigInteger('estimated_minutes_watched')->default(0);
            $table->decimal('average_view_duration', 10, 2)->default(0);
            $table->decimal('average_view_percentage', 8, 4)->default(0);
            
            // Engagement metrics
            $table->bigInteger('likes')->default(0);
            $table->bigInteger('dislikes')->default(0);
            $table->bigInteger('comments')->default(0);
            $table->bigInteger('shares')->default(0);
            $table->bigInteger('subscribers_gained')->default(0);
            $table->bigInteger('subscribers_lost')->default(0);
            
            // Playlist metrics
            $table->bigInteger('videos_added_to_playlists')->default(0);
            $table->bigInteger('videos_removed_from_playlists')->default(0);
            $table->bigInteger('playlist_views')->default(0);
            $table->bigInteger('playlist_starts')->default(0);
            $table->bigInteger('playlist_saves')->default(0);
            $table->decimal('average_time_in_playlist', 10, 2)->default(0);
            
            // Revenue metrics (nullable for non-monetized channels)
            $table->decimal('estimated_revenue', 15, 2)->nullable();
            $table->decimal('estimated_ad_revenue', 15, 2)->nullable();
            $table->decimal('gross_revenue', 15, 2)->nullable();
            $table->decimal('cpm', 8, 2)->nullable();
            $table->bigInteger('monetized_playbacks')->nullable();
            $table->bigInteger('ad_impressions')->nullable();
            
            // YouTube Premium metrics
            $table->bigInteger('red_views')->nullable();
            $table->bigInteger('estimated_red_minutes_watched')->nullable();
            $table->decimal('estimated_red_partner_revenue', 15, 2)->nullable();
            
            // Card and annotation metrics
            $table->bigInteger('card_impressions')->nullable();
            $table->bigInteger('card_clicks')->nullable();
            $table->decimal('card_click_rate', 8, 4)->nullable();
            $table->bigInteger('annotation_impressions')->nullable();
            $table->bigInteger('annotation_clicks')->nullable();
            $table->decimal('annotation_click_through_rate', 8, 4)->nullable();
            
            // Live streaming metrics
            $table->decimal('average_concurrent_viewers', 10, 2)->nullable();
            $table->bigInteger('peak_concurrent_viewers')->nullable();
            
            // Audience demographics (percentage values)
            $table->decimal('viewer_percentage', 8, 4)->nullable();
            
            // Sync metadata
            $table->timestamp('last_synced_at');
            $table->boolean('sync_successful')->default(true);
            $table->text('sync_error')->nullable();
            
            // Hash for unique constraint (more efficient than long composite key)
            $table->string('record_hash', 64)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['channel_id', 'report_date', 'report_type'], 'yt_analytics_ch_date_type_idx');
            $table->index(['channel_id', 'report_type', 'dimension_type'], 'yt_analytics_ch_type_dim_idx');
            $table->unique(['record_hash'], 'yt_analytics_hash_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_analytics_reports');
    }
}; 