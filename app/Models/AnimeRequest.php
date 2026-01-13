<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AnimeRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'title',
        'mal_url',
        'mal_id',
        'reason',
        'type',
        'anime_id',
        'status',
        'admin_notes',
        'processed_at',
        'processed_by',
        'upvotes',
    ];

    protected $casts = [
        'processed_at' => 'datetime',
        'mal_id' => 'integer',
        'upvotes' => 'integer',
    ];

    /**
     * User yang submit request
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Anime terkait (jika request episode)
     */
    public function anime()
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * Admin yang memproses
     */
    public function processor()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    /**
     * Users yang upvote request ini
     */
    public function voters()
    {
        return $this->belongsToMany(User::class, 'anime_request_votes')
            ->withTimestamps();
    }

    /**
     * Scope untuk pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope untuk approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Check if user has voted
     */
    public function hasVoted(?User $user): bool
    {
        if (!$user) return false;
        return $this->voters()->where('user_id', $user->id)->exists();
    }

    /**
     * Toggle vote
     */
    public function toggleVote(User $user): bool
    {
        if ($this->hasVoted($user)) {
            $this->voters()->detach($user->id);
            $this->decrement('upvotes');
            return false;
        } else {
            $this->voters()->attach($user->id);
            $this->increment('upvotes');
            return true;
        }
    }

    /**
     * Get status badge color
     */
    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            'pending' => 'warning',
            'approved' => 'info',
            'rejected' => 'danger',
            'completed' => 'success',
            default => 'secondary',
        };
    }

    /**
     * Get type label
     */
    public function getTypeLabelAttribute(): string
    {
        return match($this->type) {
            'new_anime' => 'Anime Baru',
            'add_episodes' => 'Tambah Episode',
            default => $this->type,
        };
    }
}
