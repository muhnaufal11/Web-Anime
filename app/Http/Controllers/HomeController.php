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
     */
    public function search()
    {
        $query = Anime::query();

        // Fix: Wrap OR conditions in a closure to avoid breaking other filters
        $rawSearch = request('search');
        if ($rawSearch) {
            $search = $rawSearch;
            $query->where(function($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('synopsis', 'like', "%{$search}%");
            });
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

        $animes = $query->with('genres', 'episodes')
            ->orderBy('updated_at', 'desc')
            ->paginate(12)
            ->appends(request()->except('page'));

        // Fuzzy suggestion jika hasil kosong
        $suggestions = collect();
        if ($animes->isEmpty() && $rawSearch) {
            $needle = Str::lower(trim($rawSearch));

            $candidates = Anime::select('id', 'title', 'slug', 'poster_image', 'type', 'release_year', 'rating')
                ->limit(500) // batasi untuk performa
                ->get();

            $scored = $candidates->map(function ($anime) use ($needle) {
                $title = Str::lower($anime->title);

                // Similarity percentage
                similar_text($needle, $title, $percent);

                // Levenshtein distance (cap length to avoid large cost)
                $distance = levenshtein(
                    Str::limit($needle, 60, ''),
                    Str::limit($title, 60, '')
                );

                return [
                    'anime' => $anime,
                    'percent' => $percent,
                    'distance' => $distance,
                ];
            })
            // Filter: cukup mirip atau jarak dekat
            ->filter(function ($item) {
                return $item['percent'] >= 45 || $item['distance'] <= 6;
            })
            // Urutkan: similarity tinggi dulu, lalu distance rendah
            ->sortBy([
                ['percent', 'desc'],
                ['distance', 'asc'],
            ])
            ->take(6)
            ->values();

            $suggestions = $scored->pluck('anime');
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
        ]);
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
