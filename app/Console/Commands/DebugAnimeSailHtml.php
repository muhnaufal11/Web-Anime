<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\AnimeSailService;

class DebugAnimeSailHtml extends Command
{
    protected $signature = 'debug:animesail-html {url}';
    protected $description = 'Debug AnimeSail HTML and show all links';

    public function handle()
    {
        $url = $this->argument('url');
        $service = new AnimeSailService();
        
        $this->info("Fetching: {$url}");
        
        $response = $service->getHttpClient()->get($url);
        
        if (!$response->successful()) {
            $this->error("HTTP failed: " . $response->status());
            return;
        }
        
        $html = $response->body();
        $this->info("HTML Length: " . strlen($html) . " bytes");
        
        // Show sample
        $this->line("\n=== First 3000 chars ===");
        $this->line(substr($html, 0, 3000));
        $this->line("=== End sample ===\n");
        
        // Find all <a> tags
        preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>([^<]*)<\/a>/i', $html, $matches, PREG_SET_ORDER);
        
        $this->info("Total <a> tags found: " . count($matches));
        
        // Show all links
        if (!empty($matches)) {
            $this->line("\n=== All Links (first 30) ===");
            foreach (array_slice($matches, 0, 30) as $i => $match) {
                $href = substr($match[1], 0, 80);
                $text = substr(trim($match[2]), 0, 50);
                $this->line("[$i] HREF: {$href}");
                $this->line("    TEXT: {$text}\n");
            }
        }
        
        // Search for patterns
        $this->line("\n=== Pattern Search ===");
        
        // Pattern 1: episode in href
        preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']*episode[^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $html, $m1);
        $this->info("Links with 'episode' in href: " . count($m1[0]));
        
        // Pattern 2: ep- or ep_
        preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']*ep[-_]\d+[^"\']*)["\'][^>]*>([^<]*)<\/a>/i', $html, $m2);
        $this->info("Links with 'ep-' or 'ep_' in href: " . count($m2[0]));
        
        // Pattern 3: episode in text
        preg_match_all('/<a\s+[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>([^<]*episode[^<]*)<\/a>/i', $html, $m3);
        $this->info("Links with 'episode' in text: " . count($m3[0]));
        
        if (count($m1[0]) > 0) {
            $this->line("\n=== Episode links found (Pattern 1) ===");
            foreach (array_slice($m1[0], 0, 10) as $i => $link) {
                $this->line("[$i] {$link}");
            }
        }
    }
}
