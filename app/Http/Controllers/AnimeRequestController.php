<?php

namespace App\Http\Controllers;

use App\Models\AnimeRequest;
use App\Models\Anime;
use Illuminate\Http\Request;

class AnimeRequestController extends Controller
{
    /**
     * Display list of requests
     */
    public function index()
    {
        $requests = AnimeRequest::with(['user', 'anime'])
            ->orderByRaw("CASE WHEN status = 'pending' THEN 0 ELSE 1 END")
            ->orderBy('upvotes', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        
        // Get anime list for "add episodes" dropdown
        $animes = Anime::orderBy('title')->get(['id', 'title']);
        
        return view('request', compact('requests', 'animes'));
    }

    /**
     * Store new request
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'mal_url' => 'nullable|url|max:500',
            'reason' => 'nullable|string|max:1000',
            'type' => 'required|in:new_anime,add_episodes',
            'anime_id' => 'nullable|exists:animes,id',
        ]);

        // Extract MAL ID from URL
        $malId = null;
        if (!empty($validated['mal_url'])) {
            if (preg_match('/myanimelist\.net\/anime\/(\d+)/', $validated['mal_url'], $matches)) {
                $malId = (int) $matches[1];
            }
        }

        // Check for duplicate pending requests
        $existing = AnimeRequest::where('status', 'pending')
            ->where(function ($q) use ($validated, $malId) {
                $q->where('title', 'like', $validated['title']);
                if ($malId) {
                    $q->orWhere('mal_id', $malId);
                }
            })
            ->first();

        if ($existing) {
            // Auto-upvote existing request instead
            if (auth()->check() && !$existing->hasVoted(auth()->user())) {
                $existing->toggleVote(auth()->user());
                $message = 'Request serupa sudah ada! Vote kamu ditambahkan.';
                
                if ($request->ajax() || $request->wantsJson()) {
                    return response()->json(['success' => true, 'message' => $message]);
                }
                return back()->with('success', $message);
            }
            $message = 'Request serupa sudah ada dalam antrian.';
            
            if ($request->ajax() || $request->wantsJson()) {
                return response()->json(['success' => true, 'message' => $message]);
            }
            return back()->with('info', $message);
        }

        $animeRequest = AnimeRequest::create([
            'user_id' => auth()->id(),
            'title' => $validated['title'],
            'mal_url' => $validated['mal_url'] ?? null,
            'mal_id' => $malId,
            'reason' => $validated['reason'] ?? null,
            'type' => $validated['type'],
            'anime_id' => $validated['type'] === 'add_episodes' ? $validated['anime_id'] : null,
            'status' => 'pending',
            'upvotes' => auth()->check() ? 1 : 0,
        ]);

        // Auto-upvote own request
        if (auth()->check()) {
            $animeRequest->voters()->attach(auth()->id());
        }

        $message = 'Request anime berhasil dikirim! Admin akan meninjau.';
        
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json(['success' => true, 'message' => $message]);
        }
        return back()->with('success', $message);
    }

    /**
     * Upvote/downvote request
     */
    public function vote(AnimeRequest $animeRequest)
    {
        if (!auth()->check()) {
            return back()->with('error', 'Login untuk vote request.');
        }

        $voted = $animeRequest->toggleVote(auth()->user());
        
        return back()->with('success', $voted ? 'Vote ditambahkan!' : 'Vote dihapus.');
    }
}
