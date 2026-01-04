<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\WatchHistory;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Display the homepage.
     */
    public function index()
    {
        // Continue watching for logged in users - group by anime (show only latest episode per anime)
        $continueWatching = null;
        if (auth()->check()) {
            // Get the latest watched episode per anime
            $latestPerAnime = WatchHistory::where('user_id', auth()->id())
                ->select('anime_id', \DB::raw('MAX(id) as latest_id'))
                ->groupBy('anime_id')
                ->pluck('latest_id');
            
            $continueWatching = WatchHistory::whereIn('id', $latestPerAnime)
                ->with(['episode.anime.genres', 'anime.genres'])
                ->orderBy('last_watched_at', 'desc')
                ->limit(6)
                ->get();
        }

        $featuredAnimes = Anime::where('featured', true)
            ->with('genres', 'episodes')
            ->limit(5)
            ->get();

        // Latest episodes: show ONLY the latest episode per anime (no duplicates)
        // Get the latest episode number per anime with their latest video server update time
        $latestEpisodesData = \DB::table('episodes')
            ->join('animes', 'episodes.anime_id', '=', 'animes.id')
            ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
            ->where('video_servers.is_active', true)
            ->select(
                'episodes.id as episode_id',
                'animes.id as anime_id',
                'episodes.episode_number',
                \DB::raw('MAX(video_servers.updated_at) as latest_server_update'),
                \DB::raw('ROW_NUMBER() OVER (PARTITION BY animes.id ORDER BY episodes.episode_number DESC, MAX(video_servers.updated_at) DESC) as rn')
            )
            ->groupBy('episodes.id', 'animes.id', 'episodes.episode_number')
            ->orderBy('latest_server_update', 'desc')
            ->get();

        // Filter to get only the latest episode per anime
        $latestPerAnime = [];
        foreach ($latestEpisodesData as $row) {
            // Keep only the first (latest) episode for each anime
            if (!isset($latestPerAnime[$row->anime_id])) {
                $latestPerAnime[$row->anime_id] = $row;
            }
        }

        // Sort by latest_server_update and limit
        $latestPerAnime = collect($latestPerAnime)
            ->sortBy('latest_server_update', SORT_REGULAR, true)
            ->take(12)
            ->values()
            ->all();

        // Get episode IDs in order
        $episodeIds = array_map(fn($row) => $row->episode_id, $latestPerAnime);
        $episodeOrder = array_flip($episodeIds);

        // Load episodes with their anime
        $episodes = Episode::whereIn('id', $episodeIds)
            ->with(['anime.genres', 'videoServers' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->sort(function($a, $b) use ($episodeOrder) {
                return ($episodeOrder[$a->id] ?? 999) <=> ($episodeOrder[$b->id] ?? 999);
            })
            ->values();

        // Create anime objects for each episode (for display purposes)
        $latestEpisodes = $episodes->map(function($episode) {
            $anime = clone $episode->anime;
            $anime->setRelation('episodes', collect([$episode]));
            return $anime;
        });

        $popularAnimes = Anime::with('genres')
            ->orderBy('rating', 'desc')
            ->limit(10)
            ->get();

        $genres = Genre::all();

        return view('home', [
            'featuredAnimes' => $featuredAnimes,
            'latestEpisodes' => $latestEpisodes,
            'popularAnimes' => $popularAnimes,
            'genres' => $genres,
            'continueWatching' => $continueWatching,
        ]);
    }

    /**
     * Search animes by title, status, type, genre, year, or season.
     * Supports fuzzy search with typo tolerance.
     */
    public function search()
    {
        $query = Anime::query();
        $rawSearch = request('search');
        $didYouMean = null; // Suggestion untuk "Apakah maksud Anda..."
        $usedFuzzySearch = false;

        if ($rawSearch) {
            $search = trim($rawSearch);
            $searchLower = Str::lower($search);
            
            // Step 1: Coba exact match dulu
            $exactQuery = clone $query;
            $exactQuery->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('synopsis', 'like', "%{$search}%");
            });
            
            $exactCount = $exactQuery->count();
            
            if ($exactCount > 0) {
                // Exact match ditemukan
                $query->where(function($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                      ->orWhere('synopsis', 'like', "%{$search}%");
                });
            } else {
                // Step 2: Fuzzy search - cari dengan toleransi typo
                $usedFuzzySearch = true;
                
                // Buat variasi pencarian
                $searchWords = preg_split('/\s+/', $searchLower);
                
                $query->where(function($q) use ($search, $searchLower, $searchWords) {
                    // Method 1: LIKE dengan setiap kata
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 2) {
                            $q->orWhere('title', 'like', "%{$word}%");
                        }
                    }
                    
                    // Method 2: Partial match - potong huruf terakhir (toleransi 1-2 huruf)
                    // Ini akan menangani "lifi" -> "lif" yang bisa match "life"
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 3) {
                            $partial = substr($word, 0, -1); // Buang 1 huruf terakhir
                            $q->orWhere('title', 'like', "%{$partial}%");
                        }
                        if (strlen($word) >= 4) {
                            $partial = substr($word, 0, -2); // Buang 2 huruf terakhir
                            $q->orWhere('title', 'like', "%{$partial}%");
                        }
                    }
                    
                    // Method 3: Potong huruf pertama (toleransi typo di awal kata)
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 4) {
                            $partial = substr($word, 1); // Buang 1 huruf pertama
                            $q->orWhere('title', 'like', "%{$partial}%");
                        }
                    }
                    
                    // Method 4: Ganti huruf umum yang sering typo (i<->e, a<->e, dll)
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 3) {
                            // i -> e (lifi -> life)
                            $variation = str_replace('i', 'e', $word);
                            if ($variation !== $word) {
                                $q->orWhere('title', 'like', "%{$variation}%");
                            }
                            // e -> i
                            $variation = str_replace('e', 'i', $word);
                            if ($variation !== $word) {
                                $q->orWhere('title', 'like', "%{$variation}%");
                            }
                        }
                    }
                    
                    // Method 5: Gabungan kata tanpa spasi
                    $noSpace = str_replace(' ', '', $searchLower);
                    if (strlen($noSpace) >= 3) {
                        $q->orWhereRaw('LOWER(REPLACE(title, " ", "")) LIKE ?', ["%{$noSpace}%"]);
                    }
                });
                
                // Cari suggestion "Apakah maksud Anda..."
                $didYouMean = $this->findBestMatch($searchLower);
            }
        }

        if (request('genre')) {
            $query->whereHas('genres', fn ($q) => $q->where('genres.id', request('genre')));
        }

        if (request('status')) {
            $query->where('status', request('status'));
        }

        if (request('type')) {
            $query->where('type', request('type'));
        }

        if (request('year')) {
            $query->where('release_year', request('year'));
        }

        if (request('season')) {
            $query->where('season', request('season'));
        }

        // Ambil hasil dan urutkan berdasarkan relevansi jika fuzzy search
        if ($usedFuzzySearch && $rawSearch) {
            $animes = $query->with('genres', 'episodes')
                ->get()
                ->map(function ($anime) use ($rawSearch) {
                    $anime->relevance_score = $this->calculateRelevance($anime->title, $rawSearch);
                    return $anime;
                })
                ->sortByDesc('relevance_score')
                ->values();
            
            // Manual pagination
            $page = request('page', 1);
            $perPage = 12;
            $total = $animes->count();
            $items = $animes->forPage($page, $perPage);
            
            $animes = new \Illuminate\Pagination\LengthAwarePaginator(
                $items,
                $total,
                $perPage,
                $page,
                ['path' => request()->url(), 'query' => request()->except('page')]
            );
        } else {
            $animes = $query->with('genres', 'episodes')
                ->orderBy('updated_at', 'desc')
                ->paginate(12)
                ->appends(request()->except('page'));
        }

        // Fuzzy suggestion jika hasil masih kosong
        $suggestions = collect();
        if ($animes->isEmpty() && $rawSearch) {
            $needle = Str::lower(trim($rawSearch));

            // Ambil kandidat yang kira-kira mirip
            $baseSelect = ['id', 'title', 'slug', 'poster_image', 'type', 'release_year', 'rating'];

            $candidates = Anime::select($baseSelect)
                ->orderBy('updated_at', 'desc')
                ->limit(2000)
                ->get();

            $scored = $candidates->map(function ($anime) use ($needle) {
                $title = Str::lower($anime->title);

                // Similarity percentage
                similar_text($needle, $title, $percent);

                // Levenshtein distance
                $distance = levenshtein(
                    Str::limit($needle, 60, ''),
                    Str::limit($title, 60, '')
                );
                
                // Bonus jika ada kata yang sama
                $needleWords = preg_split('/\s+/', $needle);
                $titleWords = preg_split('/\s+/', $title);
                $commonWords = count(array_intersect($needleWords, $titleWords));

                return [
                    'anime' => $anime,
                    'percent' => $percent + ($commonWords * 10), // Bonus untuk kata yang sama
                    'distance' => $distance,
                ];
            })
            ->filter(function ($item) {
                return $item['percent'] >= 35 || $item['distance'] <= 8;
            })
            ->sortBy([
                ['percent', 'desc'],
                ['distance', 'asc'],
            ])
            ->take(6)
            ->values();

            $suggestions = $scored->pluck('anime');
            
            // Jika tidak ada didYouMean tapi ada suggestions, gunakan yang pertama
            if (!$didYouMean && $suggestions->isNotEmpty()) {
                $didYouMean = $suggestions->first();
            }
        }

        $genres = Genre::all();
        
        // Get available years for filter
        $availableYears = Anime::distinct()
            ->whereNotNull('release_year')
            ->orderBy('release_year', 'desc')
            ->pluck('release_year');

        return view('search', [
            'animes' => $animes,
            'genres' => $genres,
            'availableYears' => $availableYears,
            'suggestions' => $suggestions,
            'didYouMean' => $didYouMean,
            'usedFuzzySearch' => $usedFuzzySearch,
        ]);
    }
    
    /**
     * Find the best matching anime title for "Did you mean" suggestion
     */
    private function findBestMatch(string $searchTerm): ?Anime
    {
        $candidates = Anime::select(['id', 'title', 'slug', 'poster_image', 'type', 'release_year', 'rating'])
            ->limit(1000)
            ->get();
        
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($candidates as $anime) {
            $title = Str::lower($anime->title);
            
            // Calculate similarity
            similar_text($searchTerm, $title, $percent);
            
            // Levenshtein distance
            $distance = levenshtein(
                Str::limit($searchTerm, 60, ''),
                Str::limit($title, 60, '')
            );
            
            // Calculate word overlap
            $searchWords = preg_split('/\s+/', $searchTerm);
            $titleWords = preg_split('/\s+/', $title);
            $commonWords = count(array_intersect($searchWords, $titleWords));
            
            // Combined score
            $score = $percent + ($commonWords * 15) - ($distance * 2);
            
            if ($score > $bestScore && $score > 30) {
                $bestScore = $score;
                $bestMatch = $anime;
            }
        }
        
        return $bestMatch;
    }
    
    /**
     * Calculate relevance score for sorting fuzzy search results
     */
    private function calculateRelevance(string $title, string $search): float
    {
        $titleLower = Str::lower($title);
        $searchLower = Str::lower($search);
        
        $score = 0;
        
        // Exact match bonus
        if (Str::contains($titleLower, $searchLower)) {
            $score += 100;
        }
        
        // Similar text percentage
        similar_text($searchLower, $titleLower, $percent);
        $score += $percent;
        
        // Word match bonus
        $searchWords = preg_split('/\s+/', $searchLower);
        $titleWords = preg_split('/\s+/', $titleLower);
        
        foreach ($searchWords as $word) {
            if (strlen($word) >= 2) {
                // Exact word match
                if (in_array($word, $titleWords)) {
                    $score += 20;
                }
                // Partial word match
                elseif (Str::contains($titleLower, $word)) {
                    $score += 10;
                }
            }
        }
        
        // Levenshtein penalty (lower distance = higher score)
        $distance = levenshtein(
            Str::limit($searchLower, 50, ''),
            Str::limit($titleLower, 50, '')
        );
        $score -= ($distance * 0.5);
        
        return $score;
    }

    /**
     * Display all latest episodes with pagination
     */
    public function latestEpisodes()
    {
        // Get the latest episode number per anime with their latest video server update time
        $latestEpisodesData = \DB::table('episodes')
            ->join('animes', 'episodes.anime_id', '=', 'animes.id')
            ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
            ->where('video_servers.is_active', true)
            ->select(
                'episodes.id as episode_id',
                'animes.id as anime_id',
                'episodes.episode_number',
                \DB::raw('MAX(video_servers.updated_at) as latest_server_update')
            )
            ->groupBy('episodes.id', 'animes.id', 'episodes.episode_number')
            ->orderBy('latest_server_update', 'desc')
            ->get();

        // Filter to get only the latest episode per anime
        $latestPerAnime = [];
        foreach ($latestEpisodesData as $row) {
            // Keep only the first (latest) episode for each anime
            if (!isset($latestPerAnime[$row->anime_id])) {
                $latestPerAnime[$row->anime_id] = $row;
            }
        }

        // Sort by latest_server_update and paginate
        $paginatedData = collect($latestPerAnime)
            ->sortBy('latest_server_update', SORT_REGULAR, true)
            ->values();

        // Manual pagination
        $perPage = 24;
        $currentPage = \Illuminate\Pagination\Paginator::resolveCurrentPage() ?? 1;
        $items = $paginatedData->slice(($currentPage - 1) * $perPage, $perPage);

        $total = $paginatedData->count();
        $pagination = new \Illuminate\Pagination\LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => \Illuminate\Pagination\Paginator::resolveCurrentPath(),
                'query' => \Illuminate\Support\Facades\Request::query(),
                'pageName' => 'page',
            ]
        );

        // Get episode IDs in order
        $episodeIds = $items->pluck('episode_id')->toArray();
        $episodeOrder = array_flip($episodeIds);

        // Load episodes with their anime
        $episodes = Episode::whereIn('id', $episodeIds)
            ->with(['anime.genres', 'videoServers' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->sort(function($a, $b) use ($episodeOrder) {
                return ($episodeOrder[$a->id] ?? 999) <=> ($episodeOrder[$b->id] ?? 999);
            })
            ->values();

        // Create anime objects for each episode (for display purposes)
        $latestEpisodes = $episodes->map(function($episode) {
            $anime = clone $episode->anime;
            $anime->setRelation('episodes', collect([$episode]));
            return $anime;
        });

        return view('latest-episodes', [
            'latestEpisodes' => $latestEpisodes,
            'pagination' => $pagination,
        ]);
    }
}
