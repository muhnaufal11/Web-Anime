<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class DiscordNotificationService
{
    protected string $webhookUrl;

    public function __construct()
    {
        $this->webhookUrl = config('services.discord.webhook_url', '');
    }

    /**
     * Send notification when new episode is uploaded
     */
    public function notifyNewEpisode($episode): bool
    {
        if (empty($this->webhookUrl)) {
            return false;
        }

        try {
            $anime = $episode->anime;
            $creator = $episode->creator;
            
            // Build poster URL
            $posterUrl = $anime->poster_image 
                ? url('storage/' . $anime->poster_image)
                : url('images/placeholder.png');
            
            // Episode URL
            $episodeUrl = route('watch', ['episode' => $episode->slug]);
            $animeUrl = route('detail', ['anime' => $anime->slug]);
            
            // Build Discord embed
            $embed = [
                'title' => "🎬 Episode Baru: {$anime->title}",
                'description' => "**Episode {$episode->episode_number}** telah ditambahkan!\n\n" .
                    ($episode->title ? "_{$episode->title}_\n\n" : '') .
                    "📺 [Tonton Sekarang]({$episodeUrl})",
                'url' => $episodeUrl,
                'color' => 0xFF6B6B, // Red color
                'thumbnail' => [
                    'url' => $posterUrl,
                ],
                'fields' => [
                    [
                        'name' => '🎭 Anime',
                        'value' => "[{$anime->title}]({$animeUrl})",
                        'inline' => true,
                    ],
                    [
                        'name' => '📝 Episode',
                        'value' => $episode->episode_number,
                        'inline' => true,
                    ],
                    [
                        'name' => '👤 Uploader',
                        'value' => $creator->name ?? 'System',
                        'inline' => true,
                    ],
                ],
                'footer' => [
                    'text' => 'NipNime • Nonton Anime Sub Indo',
                    'icon_url' => url('images/logo.png'),
                ],
                'timestamp' => now()->toIso8601String(),
            ];

            // Add genres if available
            if ($anime->genres && $anime->genres->count() > 0) {
                $genreNames = $anime->genres->pluck('name')->take(5)->join(', ');
                $embed['fields'][] = [
                    'name' => '🏷️ Genre',
                    'value' => $genreNames,
                    'inline' => false,
                ];
            }

            $response = Http::post($this->webhookUrl, [
                'username' => 'NipNime Bot',
                'avatar_url' => url('images/logo.png'),
                'embeds' => [$embed],
            ]);

            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord notification failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send custom notification
     */
    public function sendMessage(string $content, array $embeds = []): bool
    {
        if (empty($this->webhookUrl)) {
            return false;
        }

        try {
            $payload = [
                'username' => 'NipNime Bot',
                'avatar_url' => url('images/logo.png'),
                'content' => $content,
            ];

            if (!empty($embeds)) {
                $payload['embeds'] = $embeds;
            }

            $response = Http::post($this->webhookUrl, $payload);
            return $response->successful();
        } catch (\Exception $e) {
            Log::error('Discord notification failed: ' . $e->getMessage());
            return false;
        }
    }
}
