<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\WatchHistory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;

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

        // Cache featured animes for 5 minutes
        $featuredAnimes = Cache::remember('home_featured', 300, function () {
            return Anime::where('featured', true)
                ->with('genres', 'episodes')
                ->limit(5)
                ->get();
        });

        // Cache latest episodes for 2 minutes (updates frequently)
        $latestEpisodes = Cache::remember('home_latest_episodes', 120, function () {
            // Latest episodes: show ONLY the latest episode per anime (no duplicates)
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

            // Create anime objects for each episode
            return $episodes->map(function($episode) {
                $anime = clone $episode->anime;
                $anime->setRelation('episodes', collect([$episode]));
                return $anime;
            });
        });

        // Cache popular animes for 10 minutes
        $popularAnimes = Cache::remember('home_popular', 600, function () {
            return Anime::with('genres')
                ->orderBy('rating', 'desc')
                ->limit(10)
                ->get();
        });

        // Cache genres for 1 hour
        $genres = Cache::remember('home_genres', 3600, function () {
            return Genre::all();
        });

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
                    // Method 1: LIKE dengan setiap kata (substring match)
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 2) {
                            $q->orWhere('title', 'like', "%{$word}%");
                        }
                    }
                    
                    // Method 2: SOUNDEX matching (untuk typo fonetik)
                    // Cocok untuk kata-kata yang terdengar mirip
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 3) {
                            $q->orWhereRaw('SOUNDEX(title) = SOUNDEX(?)', [$word]);
                        }
                    }
                    
                    // Method 3: Partial match - potong huruf terakhir (toleransi 1-2 huruf typo)
                    foreach ($searchWords as $word) {
                        if (strlen($word) >= 4) {
                            $partial = substr($word, 0, -1); // Buang 1 huruf terakhir
                            $q->orWhere('title', 'like', "%{$partial}%");
                        }
                        if (strlen($word) >= 5) {
                            $partial = substr($word, 0, -2); // Buang 2 huruf terakhir
                            $q->orWhere('title', 'like', "%{$partial}%");
                        }
                        if (strlen($word) >= 6) {
                            $partial = substr($word, 0, 3); // Minimal 3 huruf pertama
                            $q->orWhere('title', 'like', "%{$partial}%");
                        }
                    }
                    
                    // Method 4: Gabungan kata tanpa spasi
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
     * Menggunakan scoring yang lebih sophisticated untuk typo tolerance
     */
    private function findBestMatch(string $searchTerm): ?Anime
    {
        $candidates = Anime::select(['id', 'title', 'slug', 'poster_image', 'type', 'release_year', 'rating'])
            ->limit(2000)
            ->get();
        
        $searchLower = Str::lower($searchTerm);
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($candidates as $anime) {
            $titleLower = Str::lower($anime->title);
            
            // Priority 1: Exact substring match anywhere in title
            if (Str::contains($titleLower, $searchLower)) {
                $bestMatch = $anime;
                $bestScore = 10000;
                break;
            }
            
            // Priority 2: Check if major words in search are present in title
            // Split both search dan title into words
            $searchWords = array_filter(preg_split('/\s+/', $searchLower));
            $titleWords = array_filter(preg_split('/\s+/', $titleLower));
            
            // Count berapa banyak words dari search yang cocok di title
            $matchedCount = 0;
            foreach ($searchWords as $sWord) {
                if (strlen($sWord) < 2) continue;
                
                foreach ($titleWords as $tWord) {
                    // Exact word match
                    if ($sWord === $tWord) {
                        $matchedCount++;
                        break;
                    }
                    // Word prefix match (e.g., "game" starts with "game")
                    elseif (Str::startsWith($tWord, $sWord) && strlen($sWord) >= 2) {
                        $matchedCount++;
                        break;
                    }
                    // Close match via Levenshtein (e.g., "liff" vs "life")
                    elseif (levenshtein($sWord, $tWord) <= 2 && strlen($sWord) >= 3) {
                        $matchedCount++;
                        break;
                    }
                }
            }
            
            // If tidak semua kata cocok, skip anime ini
            if ($matchedCount < count($searchWords)) {
                continue;
            }
            
            // Priority 3: Calculate similarity percentage
            // Gunakan similar_text untuk perhitungan akhir
            similar_text($searchLower, $titleLower, $percent);
            
            // Jika % similarity tinggi dan semua words matched, ini candidate terbaik
            if ($percent > $bestScore) {
                $bestScore = $percent;
                $bestMatch = $anime;
            }
        }
        
        // CRITICAL: Hanya return jika similarity >= 65%
        if ($bestMatch && $bestScore >= 65) {
            return $bestMatch;
        }
        
        return null;
    }
    
    /**
     * Calculate relevance score for sorting fuzzy search results
     * Scoring yang lebih akurat untuk menampilkan hasil terbaik
     */
    private function calculateRelevance(string $title, string $search): float
    {
        $titleLower = Str::lower($title);
        $searchLower = Str::lower($search);
        
        $score = 0;
        
        // 1. Prefix match
        if (Str::startsWith($titleLower, $searchLower)) {
            $score += 300;
        }
        
        // 2. Exact substring match
        if (Str::contains($titleLower, $searchLower)) {
            $score += 200;
        }
        
        // 3. Similar text percentage
        similar_text($searchLower, $titleLower, $percent);
        $score += ($percent * 2);
        
        // 4. Word match bonus
        $searchWords = preg_split('/\s+/', $searchLower);
        $titleWords = preg_split('/\s+/', $titleLower);
        
        // Exact word matches
        $matchedWords = 0;
        foreach ($searchWords as $word) {
            if (strlen($word) >= 2) {
                if (in_array($word, $titleWords)) {
                    $score += 50;
                    $matchedWords++;
                }
                // Partial word match
                elseif (Str::contains($titleLower, $word)) {
                    $score += 20;
                }
            }
        }
        
        // 5. Levenshtein distance penalty (lower distance = higher score)
        $distance = levenshtein($searchLower, $titleLower);
        
        if ($distance <= 2) {
            $score += (100 - ($distance * 30));
        } elseif ($distance <= 4) {
            $score += (50 - ($distance * 10));
        } elseif ($distance <= 8) {
            $score -= ($distance * 0.5);
        }
        
        // 6. SOUNDEX matching
        foreach ($searchWords as $word) {
            if (strlen($word) >= 3) {
                foreach ($titleWords as $titleWord) {
                    if (soundex($word) === soundex($titleWord)) {
                        $score += 30;
                    }
                }
            }
        }
        
        // 7. Bonus untuk match at beginning
        foreach ($searchWords as $word) {
            if (strlen($word) >= 2 && Str::startsWith($titleLower, $word)) {
                $score += 40;
            }
        }
        
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

    /**
     * Get real-time search suggestions for autocomplete
     * Used by AJAX requests from search input
     */
    public function searchSuggestions()
    {
        $query = request('q', '');
        
        if (strlen($query) < 2) {
            return response()->json(['suggestions' => []]);
        }

        $query = trim($query);
        $queryLower = Str::lower($query);
        $queryWords = array_filter(preg_split('/\s+/', $queryLower));

        if (empty($queryWords)) {
            return response()->json(['suggestions' => []]);
        }

        try {
            // Use database LIKE queries - more efficient
            $candidates = Anime::select(['id', 'title', 'slug', 'poster_image', 'type', 'release_year', 'rating']);
            
            // Method 1: Exact substring match (highest priority)
            $candidates->where(function ($q) use ($queryLower) {
                $q->whereRaw('LOWER(title) LIKE ?', ["%{$queryLower}%"]);
            });

            // Method 2: Match each word individually (ALL words must match)
            foreach ($queryWords as $word) {
                if (strlen($word) >= 2) {
                    $candidates->where(function ($q) use ($word) {
                        $q->whereRaw('LOWER(title) LIKE ?', ["%{$word}%"]);
                    });
                }
            }

            $candidates = $candidates->limit(100)->get();

            $results = [];
            
            foreach ($candidates as $anime) {
                $titleLower = Str::lower($anime->title);
                
                // Score based on similarity
                $score = similar_text($queryLower, $titleLower);
                
                // Bonus for prefix match
                if (Str::startsWith($titleLower, $queryLower)) {
                    $score += 50;
                }
                
                $results[] = [
                    'anime' => $anime,
                    'score' => $score,
                ];
            }

            // Sort by score
            usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
            
            // Take top 8
            $results = array_slice($results, 0, 8);

            $suggestions = array_map(function ($item) {
                $anime = $item['anime'];
                return [
                    'id' => $anime->id,
                    'title' => $anime->title,
                    'slug' => $anime->slug,
                    'poster_image' => $anime->poster_image ? asset('storage/' . $anime->poster_image) : asset('images/placeholder.png'),
                    'type' => $anime->type,
                    'release_year' => $anime->release_year,
                    'rating' => number_format($anime->rating, 1),
                    'url' => route('detail', ['anime' => $anime->slug]),
                ];
            }, $results);

            return response()->json([
                'suggestions' => $suggestions,
                'count' => count($suggestions),
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'suggestions' => [],
            ], 500);
        }
    }
}
