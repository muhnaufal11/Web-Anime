<?php

namespace App\Http\Controllers;

use App\Models\Anime;
use App\Models\Comment;

class DetailController extends Controller
{
    /**
     * Display the detail page of an anime.
     */
    public function show(Anime $anime)
    {
        // Load only episodes with video servers
        $anime->load(['genres', 'episodes' => fn ($q) => $q->whereHas('videoServers')->orderBy('episode_number', 'asc')]);

        // Load comments (parent only, with replies and user)
        $comments = Comment::where('anime_id', $anime->id)
            ->whereNull('episode_id')
            ->parentOnly()
            ->with(['user', 'replies.user'])
            ->latest()
            ->paginate(10);

        $relatedAnimes = Anime::whereHas('genres', function ($query) use ($anime) {
            $query->whereIn('genre_id', $anime->genres->pluck('id'));
        })
            ->where('id', '!=', $anime->id)
            ->with('genres')
            ->limit(6)
            ->get();

        return view('detail', [
            'anime' => $anime,
            'comments' => $comments,
            'relatedAnimes' => $relatedAnimes,
        ]);
    }
}
