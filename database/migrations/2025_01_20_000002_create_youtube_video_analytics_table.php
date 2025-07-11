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
        Schema::create('youtube_video_analytics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')->constrained()->onDelete('cascade');
            $table->string('youtube_video_id', 20)->index();
            $table->string('youtube_channel_id')->nullable();
            $table->date('report_date');
            $table->string('report_type', 50)->default('daily'); // daily, country, device, traffic_source, etc.
            $table->string('dimension_type', 100)->nullable(); // day, country, deviceType, insightTrafficSourceType, etc.
            $table->string('dimension_value', 100)->nullable(); // ES, mobile, YT_SEARCH, etc.
            
            // Core video metrics
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
            
            // Audience retention metrics
            $table->decimal('audience_watch_ratio', 8, 4)->nullable();
            $table->decimal('relative_retention_performance', 8, 4)->nullable();
            $table->decimal('elapsed_video_time_ratio', 8, 4)->nullable();
            
            // Revenue metrics (for monetized videos)
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
            
            // Live streaming metrics (for live videos)
            $table->decimal('average_concurrent_viewers', 10, 2)->nullable();
            $table->bigInteger('peak_concurrent_viewers')->nullable();
            
            // Traffic source details (when dimension is traffic source)
            $table->string('traffic_source_type')->nullable(); // YT_SEARCH, EXTERNAL, etc.
            $table->string('traffic_source_detail')->nullable(); // google.com, specific search term, etc.
            
            // Device and playback details
            $table->string('device_type')->nullable(); // MOBILE, DESKTOP, TV, TABLET
            $table->string('operating_system')->nullable(); // ANDROID, IOS, WINDOWS, etc.
            $table->string('playback_location_type')->nullable(); // WATCH, EMBEDDED, etc.
            $table->string('playback_location_detail')->nullable(); // youtube.com, specific website
            
            // Demographics (when dimension is demographics)
            $table->string('age_group')->nullable(); // 18-24, 25-34, etc.
            $table->string('gender')->nullable(); // male, female
            $table->decimal('viewer_percentage', 8, 4)->nullable();
            
            // Geographic data (when dimension is geographic)
            $table->string('country')->nullable();
            $table->string('province')->nullable();
            $table->string('city')->nullable();
            
            // Subscriber status
            $table->string('subscribed_status')->nullable(); // SUBSCRIBED, UNSUBSCRIBED
            
            // Content type
            $table->string('live_or_on_demand')->nullable(); // LIVE, ON_DEMAND
            
            // Sync metadata
            $table->timestamp('last_synced_at');
            $table->boolean('sync_successful')->default(true);
            $table->text('sync_error')->nullable();
            
            // Hash for unique constraint (more efficient than long composite key)
            $table->string('record_hash', 64)->nullable();
            $table->timestamps();
            
            // Indexes
            $table->index(['channel_id', 'youtube_video_id', 'report_date'], 'yt_video_ch_vid_date_idx');
            $table->index(['channel_id', 'report_type', 'dimension_type'], 'yt_video_ch_type_dim_idx');
            $table->index(['youtube_video_id', 'report_date'], 'yt_video_vid_date_idx');
            $table->index(['report_date', 'views'], 'yt_video_date_views_idx');
            $table->unique(['record_hash'], 'yt_video_hash_unique_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('youtube_video_analytics');
    }
}; 