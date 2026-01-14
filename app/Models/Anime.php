<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Anime extends Model
{
    // Genre yang di-blur dan diblokir total (18+ strict)
    public const ADULT_GENRES = ['hentai'];
    
    // Genre yang perlu warning tapi tidak di-blur
    public const WARNING_GENRES = ['ecchi'];
    
    protected $fillable = [
        'title',
        'slug',
        'synopsis',
        'poster_image',
        'type',
        'status',
        'release_year',
        'rating',
        'featured',
    ];

    protected $casts = [
        'release_year' => 'integer',
        'rating' => 'float',
        'featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the genres that belong to this anime.
     */
    public function genres(): BelongsToMany
    {
        return $this->belongsToMany(Genre::class, 'anime_genre');
    }

    /**
     * Get the episodes for this anime.
     */
    public function episodes(): HasMany
    {
        return $this->hasMany(Episode::class)->orderBy('episode_number', 'asc');
    }

    /**
     * Get the schedule for this anime.
     */
    public function schedule(): HasOne
    {
        return $this->hasOne(Schedule::class);
    }

    /**
     * Boot the model.
     */
    protected static function boot()
    {
        parent::boot();
        
        static::deleting(function ($anime) {
            $anime->episodes()->delete();
            $anime->genres()->detach();
        });
    }

    /**
     * Get the route key name.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Check if this anime is adult content (18+)
     */
    public function isAdultContent(): bool
    {
        return $this->genres()
            ->whereIn('slug', self::ADULT_GENRES)
            ->exists();
    }

    /**
     * Check if current user can view this anime content
     * Returns true if:
     * - Anime is not adult content, OR
     * - User is logged in AND 18+
     */
    public function canUserView(): bool
    {
        // Not adult content = anyone can view
        if (!$this->isAdultContent()) {
            return true;
        }

        // Adult content - check user age
        $user = auth()->user();
        
        // Not logged in = cannot view adult content
        if (!$user) {
            return false;
        }

        // Check if user is 18+
        return $user->isAdult();
    }

    /**
     * Check if poster should be blurred for current user
     */
    public function shouldBlurPoster(): bool
    {
        return $this->isAdultContent() && !$this->canUserView();
    }

    /**
     * Check if this anime has ecchi content (needs warning but not blocked)
     */
    public function isEcchiContent(): bool
    {
        return $this->genres()
            ->whereIn('slug', self::WARNING_GENRES)
            ->exists();
    }

    /**
     * Check if anime needs a warning before playing
     * (Ecchi content or adult content that user CAN view)
     */
    public function needsWarning(): bool
    {
        // Ecchi always needs warning
        if ($this->isEcchiContent()) {
            return true;
        }
        
        // Adult content that user can view also needs warning
        if ($this->isAdultContent() && $this->canUserView()) {
            return true;
        }
        
        return false;
    }

    /**
     * Get optimized thumbnail URL for poster
     * @param string $size Format: widthxheight (e.g., '200x300')
     */
    public function getThumbnailUrl(string $size = '200x300'): string
    {
        if (!$this->poster_image) {
            return asset('images/placeholder.png');
        }
        
        return url("/img/{$size}/{$this->poster_image}");
    }

    /**
     * Get original poster URL
     */
    public function getPosterUrl(): string
    {
        if (!$this->poster_image) {
            return asset('images/placeholder.png');
        }
        
        return asset('storage/' . $this->poster_image);
    }
}
