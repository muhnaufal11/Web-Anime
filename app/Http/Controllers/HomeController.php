<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\Genre;
use App\Models\WatchHistory;

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
        if (request('search')) {
            $search = request('search');
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
        ]);
    }

    /**
     * Display all latest episodes with pagination
     */
    public function latestEpisodes()
    {
        // Get latest episode per anime with their latest video server update time
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
            ->paginate(24);

        // Get episode IDs in order
        $episodeIds = $latestEpisodesData->pluck('episode_id');
        $episodeOrder = array_flip($episodeIds->toArray());

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
            'pagination' => $latestEpisodesData,
        ]);
    }
}
