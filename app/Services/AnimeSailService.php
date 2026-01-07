<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\VideoServer;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Symfony\Component\DomCrawler\Crawler;

class AnimeSailService
{
    protected $baseUrl = 'https://animesail.in';

    // ... (Bagian __construct dan http client biarkan sama, skip ke bawah) ...
    public function __construct()
    {
        $this->baseUrl = config('services.animesail.base_url', $this->baseUrl);
    }
    
    protected function http()
    {
        $verify = (bool) config('services.animesail.verify_ssl', true);
        return Http::withOptions(['verify' => $verify])
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36'])
            ->timeout(10)->retry(1, 100);
    }

    /**
     * Search anime pada AnimeSail (HTML fetch)
     */
    public function searchAnime($query)
    {
        try {
            $searchUrl = rtrim($this->baseUrl, '/') . '/?s=' . urlencode($query);
            $response = $this->http()->get($searchUrl);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            $results = [];

            $crawler->filter('.post-item, .post, .search-result, .entry-title')->each(function (Crawler $node) use (&$results) {
                try {
                    $linkNode = $node->filter('a')->first();
                    $title = $linkNode->text();
                    $url = $linkNode->attr('href');
                    $results[] = [
                        'title' => trim($title),
                        'url' => $url,
                        'slug' => basename(parse_url($url, PHP_URL_PATH)),
                    ];
                } catch (\Exception $e) {
                    // skip invalid entry
                }
            });

            return $results;
        } catch (\Exception $e) {
            \Log::error("AnimeSail search failed: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Ambil detail anime langsung dari URL
     */
    public function getAnimeDetails($animeUrl)
    {
        try {
            $response = $this->http()->get($animeUrl);
            if (!$response->successful()) {
                return null;
            }

            return $this->parseEpisodeList($response->body(), $animeUrl);
        } catch (\Exception $e) {
            \Log::error("AnimeSail anime details failed: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Parse episode list dari HTML upload (tanpa request jaringan)
     */
    public function getAnimeDetailsFromHtml(?string $animeUrl, string $html): array
    {
        try {
            return $this->parseEpisodeList($html, $animeUrl);
        } catch (\Exception $e) {
            return ['episodes' => []];
        }
    }

    /**
     * Parser reusable untuk daftar episode
     */
    protected function parseEpisodeList(string $html, ?string $baseUrl = null): array
    {
        $crawler = new Crawler($html);
        $episodes = [];

        $scheme = $baseUrl ? (parse_url($baseUrl, PHP_URL_SCHEME) ?: 'https') : 'https';
        $host = $baseUrl ? parse_url($baseUrl, PHP_URL_HOST) : parse_url($this->baseUrl, PHP_URL_HOST);
        $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

        // ====== DETEKSI SUMBER HTML ======
        
        // Cek apakah dari NontonAnimeID
        if (str_contains($html, 'nontonanimeid') || str_contains($html, 'kotakanimeid') || str_contains($html, 'episode-item')) {
            return $this->parseNontonAnimeIdEpisodeList($html, $crawler, $base);
        }

        // Default: AnimeSail format
        $crawler->filter('.episode-list a, .episodelist a, .entry-content a, .eplister a, .episodes a, ul li a')->each(function (Crawler $node) use (&$episodes, $base) {
            try {
                $text = $node->text();
                $href = $node->attr('href');

                if ($href && strpos($href, '//') === 0) {
                    $href = 'https:' . $href;
                } elseif ($href && !preg_match('/^https?:/i', $href)) {
                    $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                }

                if (preg_match('/episode|ep\s*\d+/i', $text)) {
                    $episodeNumber = $this->extractEpisodeNumber($text);
                    if ($episodeNumber) {
                        $episodes[] = [
                            'number' => $episodeNumber,
                            'title' => trim($text),
                            'url' => $href,
                        ];
                    }
                }
            } catch (\Exception $e) {
                // skip invalid entry
            }
        });

        return ['episodes' => $episodes];
    }

    /**
     * Parse episode list dari NontonAnimeID HTML
     */
    protected function parseNontonAnimeIdEpisodeList(string $html, Crawler $crawler, string $base): array
    {
        $episodes = [];

        // Pattern 1: Episode cards dengan class "episode-item"
        // <a href="URL" class="episode-item"><span class="ep-title">Episode X</span>...
        try {
            $crawler->filter('a.episode-item, .episode-list-items a, .meta-episodes a.ep-link')->each(function (Crawler $node) use (&$episodes, $base) {
                $href = $node->attr('href');
                
                // Normalize URL
                if ($href && strpos($href, '//') === 0) {
                    $href = 'https:' . $href;
                } elseif ($href && !preg_match('/^https?:/i', $href)) {
                    $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                }

                // Extract episode number from URL or text
                $episodeNumber = null;
                $title = '';
                
                // Try to get from ep-title span
                try {
                    $titleNode = $node->filter('.ep-title');
                    if ($titleNode->count()) {
                        $title = trim($titleNode->text());
                    }
                } catch (\Exception $e) {}
                
                // Fallback to full text
                if (empty($title)) {
                    $title = trim($node->text());
                }
                
                // Extract episode number from title
                if (preg_match('/Episode\s*(\d+)/i', $title, $m)) {
                    $episodeNumber = (int) $m[1];
                }
                // Or from URL
                elseif (preg_match('/episode-?(\d+)/i', $href, $m)) {
                    $episodeNumber = (int) $m[1];
                }
                
                if ($episodeNumber && $href) {
                    // Avoid duplicates
                    $exists = false;
                    foreach ($episodes as $ep) {
                        if ($ep['number'] === $episodeNumber) {
                            $exists = true;
                            break;
                        }
                    }
                    
                    if (!$exists) {
                        $episodes[] = [
                            'number' => $episodeNumber,
                            'title' => $title ?: "Episode {$episodeNumber}",
                            'url' => $href,
                        ];
                    }
                }
            });
        } catch (\Exception $e) {}

        // Pattern 2: JSON-LD Schema (lebih reliable)
        if (empty($episodes)) {
            try {
                if (preg_match('/<script[^>]*type="application\/ld\+json"[^>]*>(.*?)<\/script>/is', $html, $jsonMatch)) {
                    $jsonData = json_decode($jsonMatch[1], true);
                    if (isset($jsonData['@graph'])) {
                        foreach ($jsonData['@graph'] as $item) {
                            if (isset($item['@type']) && $item['@type'] === 'TVSeries' && isset($item['episode'])) {
                                foreach ($item['episode'] as $ep) {
                                    if (isset($ep['episodeNumber']) && isset($ep['url'])) {
                                        $episodes[] = [
                                            'number' => (int) $ep['episodeNumber'],
                                            'title' => $ep['name'] ?? "Episode {$ep['episodeNumber']}",
                                            'url' => $ep['url'],
                                        ];
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {}
        }

        // Sort by episode number
        usort($episodes, fn($a, $b) => $a['number'] <=> $b['number']);

        return ['episodes' => $episodes];
    }

    // === BAGIAN UTAMA YANG DIPERBAIKI (Sync dari HTML Upload) ===

    public function syncEpisodesFromHtml(Anime $anime, string $html, ?string $animeUrl = null): array
    {
        // 1. Parse Episode dari HTML
        $details = $this->getAnimeDetailsFromHtml($animeUrl, $html);
        if (empty($details['episodes'])) {
            return ['created' => 0, 'updated' => 0, 'errors' => ['No episodes found in HTML']];
        }

        // Detect if HTML is from NontonAnimeID (don't try to fetch servers online)
        $isNontonAnimeId = str_contains($html, 'nontonanimeid') || str_contains($html, 'kotakanimeid');

        // 2. Parse Video Server dari HTML (Logika Baru)
        $directServers = $this->getEpisodeServersFromHtml($html, $animeUrl);
        $directEpisodeNum = null;

        // Coba tebak nomor episode dari Judul Halaman
        if (preg_match('/<h1[^>]*>(.*?)<\/h1>/si', $html, $m)) {
            $titleText = trim(strip_tags($m[1]));
            $directEpisodeNum = $this->extractEpisodeNumber($titleText);
        }

        $created = 0; $updated = 0; $errors = [];

        foreach ($details['episodes'] as $episodeData) {
            try {
                $slug = Str::slug("{$anime->title} Episode {$episodeData['number']}");
                
                $episode = Episode::updateOrCreate(
                    ['anime_id' => $anime->id, 'episode_number' => $episodeData['number']],
                    ['title' => $episodeData['title'], 'slug' => $slug]
                );

                // LOGIKA PENTING: Jika episode cocok, pakai server dari HTML upload
                if ($directEpisodeNum && $episodeData['number'] === $directEpisodeNum) {
                    $servers = $directServers;
                } elseif ($isNontonAnimeId) {
                    // NontonAnimeID: Skip fetching servers (butuh AJAX, tidak bisa langsung)
                    // User harus upload HTML per episode atau bulk sync di halaman Episode
                    $servers = [];
                } else {
                    // AnimeSail: Episode lain fetch online (mungkin gagal kalau diproteksi)
                    $servers = $this->getEpisodeServers($episodeData['url']);
                }

                foreach ($servers as $serverData) {
                    $serverUrl = $serverData['url'] ?? '';
                    if (empty($serverUrl)) continue;
                    
                    VideoServer::updateOrCreate(
                        ['episode_id' => $episode->id, 'embed_url' => $serverUrl],
                        ['server_name' => $serverData['name'] ?? 'Unknown', 'is_active' => true, 'source' => 'sync']
                    );
                }

                if ($episode->wasRecentlyCreated) $created++; else $updated++;
            } catch (\Exception $e) {
                $errors[] = "Ep {$episodeData['number']} Error: " . $e->getMessage();
            }
        }

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    // === PERBAIKAN LOGIKA EKSTRAKSI SERVER ===

    public function getEpisodeServersFromHtml(string $html, ?string $episodeUrl = null): array
    {
        try {
            $crawler = new Crawler($html);
            $servers = [];

            // ====== DETEKSI SUMBER HTML ======
            
            // Cek apakah dari NontonAnimeID (s7.nontonanimeid.boats / kotakanimeid)
            if (str_contains($html, 'nontonanimeid') || str_contains($html, 'kotakanimeid') || str_contains($html, 'kotakajax')) {
                return $this->parseNontonAnimeIdServers($html, $crawler);
            }

            // Default: AnimeSail format
            $base = 'https://animesail.in'; 

            // 1. Cek Default Player (#pembed)
            try {
                $pembed = $crawler->filter('#pembed');
                if ($pembed->count()) {
                    $encoded = $pembed->first()->attr('data-default');
                    $this->extractServersFromBase64($encoded, $base, $servers, 'Default');
                }
            } catch (\Exception $e) {}

            // 2. Cek Dropdown Mirror (select.mirror option)
            try {
                $crawler->filter('select.mirror option[data-em]')->each(function (Crawler $node) use (&$servers, $base) {
                    $label = trim($node->text());
                    $encoded = $node->attr('data-em');
                    // Label cleaning (hapus resolusi biar bersih)
                    $cleanLabel = trim(preg_replace('/(360p|480p|720p|1080p|HD|SD)/i', '', $label));
                    $this->extractServersFromBase64($encoded, $base, $servers, $label); // Kirim label asli buat deteksi resolusi
                });
            } catch (\Exception $e) {}

            return $servers;
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Parse servers dari NontonAnimeID HTML
     * Dari HTML hanya bisa ambil server pertama (yang ada iframe-nya)
     * Server lain perlu AJAX fetch via NontonAnimeIdFetcher
     */
    protected function parseNontonAnimeIdServers(string $html, Crawler $crawler): array
    {
        $servers = [];
        $serverList = [];
        
        // 1. Extract ALL server names dari player tabs (untuk info)
        // <li id="player-option-1" data-post="152760" data-type="Lokal-c" data-nume="1">
        try {
            $crawler->filter('ul.tabs1.player li[data-type], ul.player li[data-type]')->each(function (Crawler $node) use (&$serverList) {
                $serverType = $node->attr('data-type');
                $serverName = trim($node->text());
                // Clean server name (remove "S-" prefix)
                $serverName = preg_replace('/^S-/i', '', $serverName);
                
                if ($serverType) {
                    $serverList[] = [
                        'name' => $serverName ?: $serverType,
                        'type' => $serverType,
                        'post_id' => $node->attr('data-post'),
                        'nume' => $node->attr('data-nume'),
                    ];
                }
            });
        } catch (\Exception $e) {}

        // 2. Extract main iframe URL (HANYA server pertama yang punya URL di HTML)
        $firstIframeUrl = null;
        try {
            // Coba berbagai selector untuk iframe
            $iframeSelectors = [
                '#videoku iframe',
                '.player_embed iframe', 
                '.player-embed iframe',
                '#pembed iframe',
                'iframe[data-src*="kotakanimeid"]',
                'iframe[data-src*="video-embed"]',
                'iframe[src*="kotakanimeid"]',
            ];
            
            foreach ($iframeSelectors as $selector) {
                try {
                    $iframe = $crawler->filter($selector)->first();
                    if ($iframe->count()) {
                        $firstIframeUrl = $iframe->attr('data-src') ?: $iframe->attr('src');
                        if ($firstIframeUrl && str_starts_with($firstIframeUrl, 'http')) {
                            break;
                        }
                    }
                } catch (\Exception $e) {}
            }
        } catch (\Exception $e) {}

        // 3. Jika ada iframe URL, assign ke server pertama
        if ($firstIframeUrl && !empty($serverList)) {
            $servers[] = [
                'name' => $serverList[0]['name'],
                'url' => $firstIframeUrl,
                'type' => 'nontonanimeid',
            ];
        } elseif ($firstIframeUrl) {
            // Tidak ada server list tapi ada iframe
            $servers[] = [
                'name' => 'Default',
                'url' => $firstIframeUrl,
                'type' => 'iframe',
            ];
        }
        
        // 4. Log info untuk debugging
        if (count($serverList) > 1) {
            \Log::info("NontonAnimeID: Found " . count($serverList) . " servers but only 1st has embed URL. Use 'Fetch via URL' for all servers.");
        }

        return $servers;
    }

    /**
     * Ambil video servers dari halaman episode (HTTP fetch)
     */
    public function getEpisodeServers($episodeUrl)
    {
        try {
            $response = $this->http()->get($episodeUrl);

            if (!$response->successful()) {
                return [];
            }

            $crawler = new Crawler($response->body());
            $servers = [];
            $scheme = parse_url($episodeUrl, PHP_URL_SCHEME) ?: 'https';
            $host = parse_url($episodeUrl, PHP_URL_HOST);
            $base = ($scheme && $host) ? ($scheme . '://' . $host) : rtrim($this->baseUrl, '/');

            // Cari iframe player
            $crawler->filter('iframe')->each(function (Crawler $node) use (&$servers, $base) {
                try {
                    $src = $node->attr('src') ?? $node->attr('data-src');
                    if ($src && strpos($src, '//') === 0) {
                        $src = 'https:' . $src;
                    } elseif ($src && !preg_match('/^https?:/i', $src)) {
                        $src = rtrim($base, '/') . '/' . ltrim($src, '/');
                    }

                    if ($this->isValidVideoUrl($src)) {
                        $serverName = $this->getServerName($src);
                        $servers[] = [
                            'name' => $serverName,
                            'url' => $src,
                            'type' => 'iframe',
                        ];
                    }
                } catch (\Exception $e) {
                    // skip invalid iframe
                }
            });

            // Cari link video di konten
            $crawler->filter('.entry-content a, .server-list a, a')->each(function (Crawler $node) use (&$servers, $base) {
                try {
                    $href = $node->attr('href');
                    $text = $node->text();
                    if ($href && strpos($href, '//') === 0) {
                        $href = 'https:' . $href;
                    } elseif ($href && !preg_match('/^https?:/i', $href)) {
                        $href = rtrim($base, '/') . '/' . ltrim($href, '/');
                    }

                    if ($this->isValidVideoUrl($href)) {
                        $serverName = !empty($text) ? trim($text) : $this->getServerName($href);

                        $exists = false;
                        foreach ($servers as $server) {
                            if ($server['url'] === $href) {
                                $exists = true;
                                break;
                            }
                        }

                        if (!$exists) {
                            $servers[] = [
                                'name' => $serverName,
                                'url' => $href,
                                'type' => 'link',
                            ];
                        }
                    }
                } catch (\Exception $e) {
                    // skip invalid link
                }
            });

            return $servers;
        } catch (\Exception $e) {
            \Log::error("AnimeSail episode servers failed: " . $e->getMessage());
            return [];
        }
    }
    
    protected function extractServersFromBase64($encoded, $base, &$servers, $label = null) {
        if (empty($encoded)) return;
        $decoded = @base64_decode($encoded, true);
        if (!$decoded) return;
        
        // PENTING: Gunakan Regex untuk ekstrak SRC karena DomCrawler kadang bingung kalau HTML tidak valid sempurna
        // Regex ini support kutip satu (') dan kutip dua (")
        if (preg_match('/src=["\']([^"\']+)["\']/i', $decoded, $matches)) {
            $src = html_entity_decode($matches[1]);
            
            // Fix Protocol Relative URL (//google.com -> https://google.com)
            if (strpos($src, '//') === 0) {
                $src = 'https:' . $src;
            }
            
            // Masukkan ke list
            $serverName = !empty($label) ? $label : $this->getServerName($src);
            
            // Hindari duplikat
            foreach ($servers as $s) {
                if (($s['url'] ?? null) === $src) return;
            }

            $servers[] = [
                'name' => $serverName,
                'url' => $src,
                'type' => 'iframe',
            ];
        }
    }

    // === LIST SERVER VALID & PENAMAAN ===

    protected function isValidVideoUrl($url)
    {
        return true; // Terima semua URL hasil decode, filter nanti di frontend
    }

    protected function getServerName($url)
    {
        $host = parse_url($url, PHP_URL_HOST);
        $nameMap = [
            'youtube.com' => 'YouTube', 'youtu.be' => 'YouTube',
            'mp4upload.com' => 'MP4Upload', 'streamtape.com' => 'StreamTape',
            'doodstream.com' => 'DoodStream', 'streamsb.net' => 'StreamSB',
            'fembed.com' => 'Fembed', 'dailymotion.com' => 'Dailymotion', 
            'acefile.co' => 'AceFile', 'mixdrop' => 'MixDrop', 
            'krakenfiles.com' => 'Kraken', 'aghanim.xyz' => 'Lokal',
            'doply.net' => 'Dodo', 'buzzheavier.com' => 'Buzi'
        ];

        foreach ($nameMap as $domain => $name) {
            if (stripos($host, $domain) !== false) return $name;
        }

        // Deteksi Server Internal (Pixel, Kamado, Pompom)
        if (stripos($url, '154.26.137.28') !== false) {
            if (strpos($url, '/pixel/') !== false) return 'Pixel';
            if (strpos($url, '/kodir2/') !== false) return 'Kamado';
            if (strpos($url, '/pomf/') !== false) return 'Pompom';
            if (strpos($url, '/framezilla/') !== false) return 'Mega';
            return 'VIP Server';
        }

        return $host ?? 'Unknown';
    }
    
    // ... (Fungsi extractEpisodeNumber dll biarkan) ...
    protected function extractEpisodeNumber($text) {
        if (preg_match('/(?:episode|ep)[-?_\s]*(\d+)/i', $text, $matches)) return (int) $matches[1];
        if (preg_match_all('/\d+/', $text, $all) && !empty($all[0])) return (int) end($all[0]);
        return null;
    }
}