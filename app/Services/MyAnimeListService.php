<?php

namespace App\Services;

use App\Models\Anime;
use App\Models\Genre;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class MyAnimeListService
{
    protected $baseUrl = 'https://api.jikan.moe/v4';
    
    /**
     * Fetch anime by season (current/upcoming)
     */
    public function fetchSeasonalAnime($year = null, $season = null, $limit = 25)
    {
        $year = $year ?? date('Y');
        $season = $season ?? $this->getCurrentSeason();
        $target = max(1, (int) $limit);
        $perPage = 25; // Jikan seasonal endpoint effectively caps at 25
        $page = 1;
        $results = [];

        try {
            while (count($results) < $target) {
                $response = Http::timeout(30)
                    ->retry(3, 1000)
                    ->get("{$this->baseUrl}/seasons/{$year}/{$season}", [
                        'page' => $page,
                        'limit' => $perPage,
                    ]);

                if (!$response->successful()) {
                    throw new \Exception("API returned status: " . $response->status());
                }

                $data = $response->json()['data'] ?? [];

                if (empty($data)) {
                    break; // no more data
                }

                foreach ($data as $item) {
                    if (count($results) >= $target) {
                        break 2;
                    }
                    $results[] = $item;
                }

                // If less than perPage returned, no next page
                if (count($data) < $perPage) {
                    break;
                }

                $page++;
                usleep(400000); // 0.4s to respect Jikan rate limits
            }

            return $results;
        } catch (\Exception $e) {
            \Log::error("Failed to fetch seasonal anime: " . $e->getMessage());
            throw new \Exception("Gagal mengambil data dari MyAnimeList. Pastikan koneksi internet Anda stabil dan coba lagi.");
        }
    }

    /**
     * Fetch top anime by rating
     */
    public function fetchTopAnime($limit = 25)
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get("{$this->baseUrl}/top/anime", [
                    'page' => 1,
                    'limit' => $limit,
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            
            throw new \Exception("API returned status: " . $response->status());
        } catch (\Exception $e) {
            \Log::error("Failed to fetch top anime: " . $e->getMessage());
            throw new \Exception("Gagal mengambil data dari MyAnimeList. Pastikan koneksi internet Anda stabil dan coba lagi.");
        }
    }

    /**
     * Search anime by title
     */
    public function searchAnime($query, $limit = 10)
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get("{$this->baseUrl}/anime", [
                    'q' => $query,
                    'limit' => $limit,
                    'order_by' => 'members',
                    'sort' => 'desc',
                ]);

            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            
            throw new \Exception("API returned status: " . $response->status());
        } catch (\Exception $e) {
            \Log::error("Failed to search anime: " . $e->getMessage());
            throw new \Exception("Gagal mencari anime. Pastikan koneksi internet Anda stabil dan coba lagi.");
        }
    }

    /**
     * Get anime details by MAL ID
     */
    public function getAnimeDetails($malId)
    {
        try {
            $response = Http::timeout(30)
                ->retry(3, 1000)
                ->get("{$this->baseUrl}/anime/{$malId}/full");

            if ($response->successful()) {
                return $response->json()['data'] ?? null;
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error("Failed to get anime details: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Sync anime from MAL to database
     */
    public function syncAnime($malData, $downloadImage = true)
    {
        $slug = Str::slug($malData['title']);
        
        // Check if anime already exists
        $anime = Anime::where('slug', $slug)
            ->orWhere('title', $malData['title'])
            ->first();

        $imageUrl = null;
        if ($downloadImage && isset($malData['images']['jpg']['large_image_url'])) {
            $imageUrl = $this->downloadImage($malData['images']['jpg']['large_image_url'], $slug);
        }

        $animeData = [
            'title' => $malData['title'],
            'slug' => $slug,
            'synopsis' => $malData['synopsis'] ?? '',
            'poster_image' => $imageUrl ?? null,
            'rating' => isset($malData['score']) ? round($malData['score'], 1) : null,
            'status' => $this->mapStatus($malData['status'] ?? 'Unknown'),
            'type' => $this->mapType($malData['type'] ?? 'TV'),
            'release_year' => isset($malData['year']) ? (int)$malData['year'] : null,
        ];

        if ($anime) {
            $anime->update($animeData);
        } else {
            $anime = Anime::create($animeData);
        }

        // Sync genres
        if (isset($malData['genres']) && is_array($malData['genres'])) {
            $this->syncGenres($anime, $malData['genres']);
        }

        return $anime;
    }

    /**
     * Sync genres for anime
     */
    protected function syncGenres($anime, $malGenres)
    {
        $genreIds = [];

        foreach ($malGenres as $malGenre) {
            $genre = Genre::firstOrCreate(
                ['slug' => Str::slug($malGenre['name'])],
                ['name' => $malGenre['name']]
            );
            $genreIds[] = $genre->id;
        }

        $anime->genres()->sync($genreIds);
    }

    /**
     * Download and store image locally
     */
    protected function downloadImage($url, $slug)
    {
        try {
            $response = Http::timeout(30)->get($url);
            
            if ($response->successful()) {
                $extension = pathinfo(parse_url($url, PHP_URL_PATH), PATHINFO_EXTENSION);
                $filename = "posters/{$slug}-" . time() . ".{$extension}";
                
                Storage::disk('public')->put($filename, $response->body());
                
                return $filename;
            }
        } catch (\Exception $e) {
            // Log error but don't fail the sync
            \Log::warning("Failed to download image for {$slug}: " . $e->getMessage());
        }

        return null;
    }

    /**
     * Map MAL status to local status
     */
    protected function mapStatus($malStatus)
    {
        $statusMap = [
            'Finished Airing' => 'Completed',
            'Currently Airing' => 'Ongoing',
            'Not yet aired' => 'Ongoing',
        ];

        return $statusMap[$malStatus] ?? 'Ongoing';
    }

    /**
     * Map MAL type to local type
     */
    protected function mapType($malType)
    {
        $typeMap = [
            'TV' => 'TV',
            'Movie' => 'Movie',
            'OVA' => 'ONA',
            'ONA' => 'ONA',
            'Special' => 'ONA',
        ];

        return $typeMap[$malType] ?? 'TV';
    }

    /**
     * Get current season
     */
    protected function getCurrentSeason()
    {
        $month = date('n');
        
        if ($month >= 1 && $month <= 3) return 'winter';
        if ($month >= 4 && $month <= 6) return 'spring';
        if ($month >= 7 && $month <= 9) return 'summer';
        return 'fall';
    }

    /**
     * Rate limiting - Jikan API has 3 requests per second limit
     */
    protected function rateLimit()
    {
        usleep(350000); // 350ms delay between requests
    }

    /**
     * Batch sync multiple anime
     */
    public function batchSync($animeList, $downloadImages = true)
    {
        $synced = [];
        $failed = [];

        foreach ($animeList as $index => $malData) {
            try {
                $anime = $this->syncAnime($malData, $downloadImages);
                $synced[] = [
                    'id' => $anime->id,
                    'title' => $anime->title,
                    'status' => 'success',
                ];
                
                // Rate limit after each sync
                if ($index < count($animeList) - 1) {
                    $this->rateLimit();
                }
            } catch (\Exception $e) {
                $failed[] = [
                    'title' => $malData['title'] ?? 'Unknown',
                    'error' => $e->getMessage(),
                ];
                \Log::error("Failed to sync anime: " . $e->getMessage());
            }
        }

        return [
            'synced' => $synced,
            'failed' => $failed,
            'total' => count($animeList),
            'success_count' => count($synced),
            'failed_count' => count($failed),
        ];
    }

    /**
     * Sync seasonal anime automatically
     */
    public function syncSeasonalAnime($year = null, $season = null, $limit = 25, $downloadImages = true)
    {
        $animeList = $this->fetchSeasonalAnime($year, $season, $limit);
        return $this->batchSync($animeList, $downloadImages);
    }

    /**
     * Sync top anime automatically
     */
    public function syncTopAnime($limit = 25, $downloadImages = true)
    {
        $animeList = $this->fetchTopAnime($limit);
        return $this->batchSync($animeList, $downloadImages);
    }
}
