<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        
        // Send Discord notification when NEW video server is created (first server for episode)
        static::created(function (VideoServer $server) {
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
