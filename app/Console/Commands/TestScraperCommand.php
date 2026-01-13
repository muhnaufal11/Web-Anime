<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnimeSailService;

class TestScraperCommand extends Command
{
    protected $signature = 'test:scraper {url}';
    protected $description = 'Test AnimeSail scraper and dump HTML structure';

    public function handle()
    {
        $url = $this->argument('url');
        $service = new AnimeSailService();
        
        $this->info("Fetching: {$url}");
        
        try {
            $response = $service->getHttpClient()->get($url);
            
            if (!$response->successful()) {
                $this->error("HTTP failed: " . $response->status());
                return;
            }
            
            $html = $response->body();
            $this->info("HTML Length: " . strlen($html));
            
            // Show first 2000 chars to inspect structure
            $this->line("\n=== First 2000 chars of HTML ===");
            $this->line(substr($html, 0, 2000));
            $this->line("\n=== End of sample ===\n");
            
            // Extract all <a> tags with improved regex
            preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is', $html, $matches, PREG_SET_ORDER);
            
            $this->info("Total links found: " . count($matches));
            
            $episodeLinks = [];
            foreach ($matches as $match) {
                $href = $match[1];
                $text = trim($match[2]);
                
                if (preg_match('/episode|ep[-_\s]?\d+/i', $href . ' ' . $text)) {
                    $episodeLinks[] = [
                        'text' => $text,
                        'href' => $href
                    ];
                }
            }
            
            $this->info("Episode-like links: " . count($episodeLinks));
            
            if (count($episodeLinks) > 0) {
                $this->table(['Text', 'URL'], array_map(fn($l) => [$l['text'], $l['href']], array_slice($episodeLinks, 0, 10)));
            } else {
                $this->warn("No episode links found. Showing first 20 links:");
                $this->table(['Text', 'URL'], array_map(fn($m) => [trim($m[2]), $m[1]], array_slice($matches, 0, 20)));
            }
            
        } catch (\Exception $e) {
            $this->error("Error: " . $e->getMessage());
        }
    }
}
