<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnimeSailService;

class TestEpisodeServersFromHtml extends Command
{
    protected $signature = 'scraper:test-episode-html {path : Path to HTML file} {--episode-url= : Optional episode URL for base resolution}';
    protected $description = 'Parse an episode HTML file and list extracted video servers';

    public function handle(AnimeSailService $service)
    {
        $path = $this->argument('path');
        $episodeUrl = $this->option('episode-url');
        if (!is_string($path) || !file_exists($path)) {
            $this->error('File not found: ' . $path);
            return 1;
        }

        $html = file_get_contents($path);
        $servers = $service->getEpisodeServersFromHtml($html, $episodeUrl ?: null);

        if (empty($servers)) {
            $this->warn('No servers found.');
            return 0;
        }

        $this->info('Servers extracted: ' . count($servers));
        foreach ($servers as $i => $s) {
            $this->line(sprintf('%2d. [%s] %s', $i + 1, $s['name'] ?? 'Unknown', $s['url'] ?? '-'));
        }

        return 0;
    }
}
