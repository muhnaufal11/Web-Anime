<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Anime;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'anime_id' => 'required|exists:animes,id',
            'episode_id' => 'nullable|exists:episodes,id',
            'parent_id' => 'nullable|exists:comments,id',
            'comment' => 'required|string|max:1000',
        ]);

        $comment = Comment::create([
            'user_id' => auth()->id(),
            'anime_id' => $validated['anime_id'],
            'episode_id' => $validated['episode_id'] ?? null,
            'parent_id' => $validated['parent_id'] ?? null,
            'comment' => $validated['comment'],
        ]);

        return back()->with('success', 'Komentar berhasil ditambahkan!');
    }

    public function destroy(Comment $comment)
    {
        // Only owner can delete
        if ($comment->user_id !== auth()->id()) {
            abort(403);
        }

        $comment->delete();

        return back()->with('success', 'Komentar berhasil dihapus!');
    }
}
