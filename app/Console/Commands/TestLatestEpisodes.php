<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class TestLatestEpisodes extends Command
{
    protected $signature = 'test:latest-episodes';
    protected $description = 'Test latest episodes query';

    public function handle()
    {
        $this->info('Testing Latest Episodes Query...');
        $this->newLine();

        // Get anime IDs ordered by latest video server activity
        $animeIds = DB::table('animes')
            ->join('episodes', 'animes.id', '=', 'episodes.anime_id')
            ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
            ->select('animes.id', 'animes.title', DB::raw('MAX(video_servers.updated_at) as latest_upload'))
            ->groupBy('animes.id', 'animes.title')
            ->orderBy('latest_upload', 'desc')
            ->limit(12)
            ->get();

        $this->info('Found ' . $animeIds->count() . ' anime with video servers:');
        $this->newLine();

        foreach ($animeIds as $anime) {
            $this->line($anime->title . ' => ' . $anime->latest_upload);
        }

        return 0;
    }
}
