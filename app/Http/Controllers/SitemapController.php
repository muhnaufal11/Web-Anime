<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SitemapController extends Controller
{
    /**
     * Serve sitemap.xml for crawlers.
     */
    public function index()
    {
        $xml = Cache::remember('sitemap.xml', 300, function () {
            $urls = [];

            $urls[] = $this->buildUrl(route('home'), now());
            $urls[] = $this->buildUrl(route('latest-episodes'), now());
            $urls[] = $this->buildUrl(route('search'), now());

            $animes = Anime::select('id', 'slug', 'updated_at')
                ->latest('updated_at')
                ->limit(500)
                ->get();

            foreach ($animes as $anime) {
                $urls[] = $this->buildUrl(route('detail', $anime), $anime->updated_at);
            }

            $episodes = Episode::select('id', 'slug', 'updated_at')
                ->latest('updated_at')
                ->limit(500)
                ->get();

            foreach ($episodes as $episode) {
                $urls[] = $this->buildUrl(route('watch', $episode), $episode->updated_at);
            }

            $body = collect($urls)->map(function ($item) {
                return "  <url>\n" .
                    "    <loc>{$item['loc']}</loc>\n" .
                    "    <lastmod>{$item['lastmod']}</lastmod>\n" .
                    "    <changefreq>{$item['changefreq']}</changefreq>\n" .
                    "    <priority>{$item['priority']}</priority>\n" .
                    "  </url>";
            })->implode("\n");

            return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
{$body}
</urlset>
XML;
        });

        return response($xml, 200)->header('Content-Type', 'application/xml');
    }

    private function buildUrl(string $loc, $updatedAt = null): array
    {
        $lastmod = $updatedAt instanceof Carbon ? $updatedAt : now();

        return [
            'loc' => htmlspecialchars($loc, ENT_XML1 | ENT_QUOTES, 'UTF-8'),
            'lastmod' => $lastmod->toAtomString(),
            'changefreq' => 'daily',
            'priority' => '0.8',
        ];
    }
}
