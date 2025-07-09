<?php

namespace App\Console\Commands;

use App\Models\YoutubeChannelStat;
use App\Models\YoutubeVideoStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanUtf8Data extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'youtube:clean-utf8 {--dry-run : Show what would be cleaned without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Clean malformed UTF-8 characters from existing YouTube data';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Ejecutando en modo dry-run - no se realizarán cambios');
        } else {
            $this->info('🧹 Limpiando datos UTF-8 malformados...');
        }

        $this->cleanChannelStats($dryRun);
        $this->cleanVideoStats($dryRun);

        $this->info('✅ Limpieza completada');
    }

    /**
     * Clean channel statistics
     */
    private function cleanChannelStats(bool $dryRun): void
    {
        $this->info('📊 Limpiando estadísticas de canales...');
        
        $channels = YoutubeChannelStat::all();
        $cleaned = 0;

        foreach ($channels as $channel) {
            $needsUpdate = false;
            $updates = [];

            // Check title
            if ($channel->title && !mb_check_encoding($channel->title, 'UTF-8')) {
                $cleaned_title = $this->cleanUtf8Text($channel->title);
                if ($cleaned_title !== $channel->title) {
                    $updates['title'] = $cleaned_title;
                    $needsUpdate = true;
                }
            }

            // Check description
            if ($channel->description && !mb_check_encoding($channel->description, 'UTF-8')) {
                $cleaned_description = $this->cleanUtf8Text($channel->description);
                if ($cleaned_description !== $channel->description) {
                    $updates['description'] = $cleaned_description;
                    $needsUpdate = true;
                }
            }

            // Check channel keywords
            if ($channel->channel_keywords && !mb_check_encoding($channel->channel_keywords, 'UTF-8')) {
                $cleaned_keywords = $this->cleanUtf8Text($channel->channel_keywords);
                if ($cleaned_keywords !== $channel->channel_keywords) {
                    $updates['channel_keywords'] = $cleaned_keywords;
                    $needsUpdate = true;
                }
            }

            if ($needsUpdate) {
                $cleaned++;
                if ($dryRun) {
                    $this->line("  - Canal {$channel->id}: " . implode(', ', array_keys($updates)));
                } else {
                    $channel->update($updates);
                }
            }
        }

        $this->info("  ✅ {$cleaned} canales " . ($dryRun ? 'necesitan' : 'limpiados'));
    }

    /**
     * Clean video statistics
     */
    private function cleanVideoStats(bool $dryRun): void
    {
        $this->info('🎥 Limpiando estadísticas de videos...');
        
        // Process in chunks to avoid memory issues
        $cleaned = 0;
        
        YoutubeVideoStat::chunk(100, function ($videos) use (&$cleaned, $dryRun) {
            foreach ($videos as $video) {
                $needsUpdate = false;
                $updates = [];

                // Check title
                if ($video->title && !mb_check_encoding($video->title, 'UTF-8')) {
                    $cleaned_title = $this->cleanUtf8Text($video->title);
                    if ($cleaned_title !== $video->title) {
                        $updates['title'] = $cleaned_title;
                        $needsUpdate = true;
                    }
                }

                // Check description
                if ($video->description && !mb_check_encoding($video->description, 'UTF-8')) {
                    $cleaned_description = $this->cleanUtf8Text($video->description);
                    if ($cleaned_description !== $video->description) {
                        $updates['description'] = $cleaned_description;
                        $needsUpdate = true;
                    }
                }

                // Check tags
                if ($video->tags && is_array($video->tags)) {
                    $cleanedTags = [];
                    $tagsNeedCleaning = false;
                    
                    foreach ($video->tags as $tag) {
                        if (!mb_check_encoding($tag, 'UTF-8')) {
                            $cleanedTags[] = $this->cleanUtf8Text($tag);
                            $tagsNeedCleaning = true;
                        } else {
                            $cleanedTags[] = $tag;
                        }
                    }
                    
                    if ($tagsNeedCleaning) {
                        $updates['tags'] = $cleanedTags;
                        $needsUpdate = true;
                    }
                }

                if ($needsUpdate) {
                    $cleaned++;
                    if ($dryRun) {
                        $this->line("  - Video {$video->youtube_video_id}: " . implode(', ', array_keys($updates)));
                    } else {
                        $video->update($updates);
                    }
                }
            }
        });

        $this->info("  ✅ {$cleaned} videos " . ($dryRun ? 'necesitan' : 'limpiados'));
    }

    /**
     * Clean malformed UTF-8 characters from text
     */
    private function cleanUtf8Text(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        // Remove malformed UTF-8 characters
        $cleaned = mb_convert_encoding($text, 'UTF-8', 'UTF-8');
        
        // Remove null bytes and other problematic characters
        $cleaned = str_replace(["\0", "\x00"], '', $cleaned);
        
        // Ensure proper UTF-8 encoding
        if (!mb_check_encoding($cleaned, 'UTF-8')) {
            // If still not valid UTF-8, remove non-UTF-8 characters
            $cleaned = mb_convert_encoding($cleaned, 'UTF-8', 'auto');
        }
        
        return $cleaned;
    }
}
