<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Episode;

class CleanupEmptyEpisodes extends Command
{
    protected $signature = 'cleanup:empty-episodes {--anime-id= : Cleanup specific anime}';
    protected $description = 'Delete episodes without video servers';

    public function handle()
    {
        $animeId = $this->option('anime-id');
        
        $query = Episode::doesntHave('videoServers');
        
        if ($animeId) {
            $query->where('anime_id', $animeId);
        }
        
        $count = $query->count();
        
        if ($count === 0) {
            $this->info('No empty episodes found.');
            return;
        }
        
        $this->warn("Found {$count} episodes without video servers");
        
        if (!$this->confirm('Delete them?')) {
            return;
        }
        
        if ($animeId) {
            $deleted = Episode::doesntHave('videoServers')
                ->where('anime_id', $animeId)
                ->delete();
        } else {
            $deleted = Episode::doesntHave('videoServers')->delete();
        }
        
        $this->info("Deleted {$deleted} empty episodes");
    }
}
