<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\WatchHistory;
use App\Models\Comment;

class WatchController extends Controller
{
    /**
     * Display the watch page for an episode.
     */
    public function show(Episode $episode)
    {
        // Load episode with active video servers
        $episode = Episode::where('id', $episode->id)->with([
            'anime.genres',
            'videoServers' => function($q) {
                $q->where('is_active', true);
            }
        ])->firstOrFail();

        // Track watch history for logged in users
        if (auth()->check()) {
            WatchHistory::updateOrCreate(
                [
                    'user_id' => auth()->id(),
                    'episode_id' => $episode->id,
                ],
                [
                    'anime_id' => $episode->anime_id,
                    'last_watched_at' => now(),
                    'progress' => 0,
                    'completed' => false,
                ]
            );
        }

        // Load only episodes of this anime that have active video servers for the sidebar list
        $animeEpisodes = $episode->anime->episodes()
            ->whereHas('videoServers', function ($q) {
                $q->where('is_active', true);
            })
            ->with(['videoServers' => function ($q) {
                $q->where('is_active', true)->orderBy('updated_at', 'desc')->orderBy('created_at', 'desc');
            }])
            ->orderBy('episode_number', 'asc')
            ->get();

        // Pakai urutan yang sama dengan daftar episode (aktif server) untuk prev/next
        $navEpisodes = $animeEpisodes->values();
        $currentIndex = $navEpisodes->search(fn ($ep) => $ep->id === $episode->id);

        // Jika episode sekarang tidak punya server aktif (edge case), fallback ke full daftar
        if ($currentIndex === false) {
            $navEpisodes = $episode->anime->episodes()
                ->orderBy('episode_number', 'asc')
                ->get(['id', 'slug', 'episode_number'])
                ->values();
            $currentIndex = $navEpisodes->search(fn ($ep) => $ep->id === $episode->id);
        }

        $prevEpisode = ($currentIndex !== false && $currentIndex > 0)
            ? $navEpisodes[$currentIndex - 1]
            : null;
        $nextEpisode = ($currentIndex !== false && $currentIndex < $navEpisodes->count() - 1)
            ? $navEpisodes[$currentIndex + 1]
            : null;

        $prevEpisodeUrl = $prevEpisode ? route('watch', ['episode' => $prevEpisode->slug]) : null;
        $nextEpisodeUrl = $nextEpisode ? route('watch', ['episode' => $nextEpisode->slug]) : null;

        // Load comments for this episode (parent only, with replies)
        $comments = Comment::where('anime_id', $episode->anime_id)
            ->where('episode_id', $episode->id)
            ->parentOnly()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(10);

        return view('watch', [
            'episode' => $episode,
            'animeEpisodes' => $animeEpisodes,
            'comments' => $comments,
            'prevEpisode' => $prevEpisode,
            'nextEpisode' => $nextEpisode,
            'prevEpisodeUrl' => $prevEpisodeUrl,
            'nextEpisodeUrl' => $nextEpisodeUrl,
        ]);
    }

    /**
     * Update watch progress via AJAX
     */
    public function updateProgress(Episode $episode)
    {
        if (!auth()->check()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $progress = request('progress', 0);
        $completed = request('completed', false);
        $duration = request('duration', 1440); // Default 24 minutes in seconds

        WatchHistory::updateOrCreate(
            [
                'user_id' => auth()->id(),
                'episode_id' => $episode->id,
            ],
            [
                'anime_id' => $episode->anime_id,
                'progress' => $progress,
                'duration' => $duration,
                'completed' => $completed,
                'last_watched_at' => now(),
            ]
        );

        return response()->json(['success' => true]);
    }

    /**
     * Display full watch history with pagination
     */
    public function history()
    {
        if (!auth()->check()) {
            return redirect()->route('auth.login');
        }

        $watchHistory = WatchHistory::where('user_id', auth()->id())
            ->with(['episode.anime.genres', 'anime.genres'])
            ->orderBy('last_watched_at', 'desc')
            ->paginate(24);

        return view('watch-history', [
            'watchHistory' => $watchHistory,
        ]);
    }
}
