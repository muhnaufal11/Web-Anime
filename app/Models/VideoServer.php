<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

class VideoServer extends Model
{
    protected $table = 'video_servers';

    protected $fillable = [
        'episode_id',
        'server_name',
        'embed_url',
        'is_active',
        'is_default',
        'source',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'source' => 'string',
    ];

    protected static function boot()
    {
        parent::boot();
        
        // Invalidate episodes cache when video server is created/updated
        static::created(function (VideoServer $server) {
            Cache::forget('latest_episodes_hash');
            Cache::forget('home_latest_episodes');
            // Trigger SSE update for realtime
            \App\Http\Controllers\EpisodeStreamController::triggerUpdate();
            
            try {
                $episode = $server->episode;
                // Only notify if this is the first video server for this episode
                $serverCount = VideoServer::where('episode_id', $episode->id)->count();
                if ($serverCount === 1) {
                    $discord = app(\App\Services\DiscordNotificationService::class);
                    $discord->notifyNewEpisode($episode);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Discord notification error: ' . $e->getMessage());
            }
        });
        
        static::updated(function (VideoServer $server) {
            Cache::forget('latest_episodes_hash');
            Cache::forget('home_latest_episodes');
            // Trigger SSE update for realtime
            \App\Http\Controllers\EpisodeStreamController::triggerUpdate();
        });
        
        static::deleted(function (VideoServer $server) {
            Cache::forget('latest_episodes_hash');
            Cache::forget('home_latest_episodes');
        });
        
        // Fix storage permissions when video server is saved
        static::saved(function (VideoServer $server) {
            Episode::fixStoragePermissions();
        });
    }

    /**
     * Get the episode that this video server belongs to.
     */
    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
