<?php

namespace App\Console\Commands;

use App\Models\YoutubeVideoStat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanOldVideos extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'youtube:clean-old-videos {--dry-run : Show what would be deleted without making changes}';

    /**
     * The console command description.
     */
    protected $description = 'Remove videos published before January 1, 2025';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $dryRun = $this->option('dry-run');
        
        if ($dryRun) {
            $this->info('🔍 Ejecutando en modo dry-run - no se realizarán cambios');
        } else {
            $this->info('🗑️  Eliminando videos anteriores al 1 de enero de 2025...');
        }

        $cutoffDate = '2025-01-01';
        
        // Count videos to be deleted
        $oldVideosCount = YoutubeVideoStat::where('published_at', '<', $cutoffDate)->count();
        
        if ($oldVideosCount === 0) {
            $this->info('✅ No hay videos anteriores al 1 de enero de 2025 para eliminar');
            return 0;
        }

        $this->info("📊 Encontrados {$oldVideosCount} videos anteriores al 1 de enero de 2025");

        if ($dryRun) {
            // Show some examples of what would be deleted
            $sampleVideos = YoutubeVideoStat::where('published_at', '<', $cutoffDate)
                ->limit(10)
                ->get(['youtube_video_id', 'title', 'published_at']);

            $this->info("\nEjemplos de videos que serían eliminados:");
            foreach ($sampleVideos as $video) {
                $this->line("  - {$video->youtube_video_id}: {$video->title} ({$video->published_at})");
            }
            
            if ($oldVideosCount > 10) {
                $this->line("  ... y " . ($oldVideosCount - 10) . " más");
            }
        } else {
            // Confirm deletion
            if (!$this->confirm("¿Estás seguro de que quieres eliminar {$oldVideosCount} videos?")) {
                $this->info('❌ Operación cancelada');
                return 1;
            }

            // Delete old videos
            $deletedCount = YoutubeVideoStat::where('published_at', '<', $cutoffDate)->delete();
            
            $this->info("✅ {$deletedCount} videos eliminados exitosamente");
        }

        return 0;
    }
}
