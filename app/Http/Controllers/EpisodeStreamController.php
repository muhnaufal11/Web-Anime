<?php

namespace App\Http\Controllers;

use App\Models\Episode;
use App\Models\Anime;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class EpisodeStreamController extends Controller
{
    /**
     * Stream latest episodes updates via Server-Sent Events (SSE)
     * Auto-updates ketika ada episode baru tanpa perlu toggle
     */
    public function stream()
    {
        return response()->stream(function () {
            // Get initial timestamp for change detection
            $lastUpdate = Cache::get('latest_episode_update_time', now()->timestamp);
            $checkInterval = 3; // Check every 3 seconds
            $heartbeatCount = 0;
            
            // Keep connection alive for 30 minutes
            $maxTime = time() + (30 * 60);
            
            while (time() < $maxTime) {
                // Check if there's a new update
                $currentUpdate = Cache::get('latest_episode_update_time', now()->timestamp);
                
                // Send update if timestamp changed (new episode added)
                if ($currentUpdate > $lastUpdate) {
                    echo "data: " . json_encode([
                        'type' => 'episodes_updated',
                        'timestamp' => $currentUpdate,
                        'message' => 'Episode baru tersedia!',
                    ]) . "\n\n";
                    
                    $lastUpdate = $currentUpdate;
                    
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                
                // Send heartbeat every 15 seconds to keep connection alive
                $heartbeatCount++;
                if ($heartbeatCount % 5 === 0) {
                    echo ": heartbeat\n\n";
                    if (ob_get_level() > 0) {
                        ob_flush();
                    }
                    flush();
                }
                
                sleep($checkInterval);
            }
        }, Response::HTTP_OK, [
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Content-Type' => 'text/event-stream',
            'X-Accel-Buffering' => 'no',
            'Connection' => 'keep-alive',
            'Pragma' => 'no-cache',
            'Expires' => '0',
        ]);
    }

    /**
     * Get latest episodes HTML for homepage section
     */
    public function getLatest()
    {
        // Get the latest episode per anime (same logic as HomeController)
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
            ->get();

        // Filter to get only the latest episode per anime
        $latestPerAnime = [];
        foreach ($latestEpisodesData as $row) {
            if (!isset($latestPerAnime[$row->anime_id])) {
                $latestPerAnime[$row->anime_id] = $row;
            }
        }

        // Get first 12 for homepage
        $episodeIds = collect($latestPerAnime)->take(12)->pluck('episode_id')->toArray();
        $episodeOrder = array_flip($episodeIds);

        $episodes = Episode::whereIn('id', $episodeIds)
            ->with(['anime.genres', 'videoServers' => fn($q) => $q->where('is_active', true)])
            ->get()
            ->sort(function($a, $b) use ($episodeOrder) {
                return ($episodeOrder[$a->id] ?? 999) <=> ($episodeOrder[$b->id] ?? 999);
            })
            ->values();

        // Build HTML
        $html = '';
        foreach ($episodes as $episode) {
            $anime = $episode->anime;
            $shouldBlur = $anime->shouldBlurPoster();
            $posterUrl = $anime->getThumbnailUrl('200x300');
            $watchUrl = $shouldBlur ? '#' : route('watch', $episode);
            $isNew = $episode->updated_at > now()->subHours(24) || $episode->created_at > now()->subHours(24);
            $onclickAttr = $shouldBlur ? 'onclick="event.preventDefault(); alert(\'Konten 18+ - Anda harus login dan berusia minimal 18 tahun untuk mengakses.\')"' : '';
            
            $blurStyle = $shouldBlur ? 'filter: blur(20px); transform: scale(1.1);' : '';
            $adultOverlay = $shouldBlur ? '
                <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/50 text-white z-10">
                    <span class="text-3xl font-black text-red-500">18+</span>
                    <span class="text-xs mt-1">Konten Dewasa</span>
                </div>' : '';
            
            $newBadge = $isNew ? '
                <div class="bg-gradient-to-r from-yellow-500 to-orange-500 text-[10px] font-black px-2.5 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider animate-pulse">
                    🆕 NEW
                </div>' : '';
            
            $episodeId = $episode->id;
            $html .= <<<HTML
            <a href="{$watchUrl}" class="group block episode-card" data-episode-id="{$episodeId}" {$onclickAttr}>
                <div class="relative bg-[#1a1d24] rounded-2xl overflow-hidden border border-white/10 group-hover:border-red-600/50 transition-all duration-300 shadow-lg">
                    <div class="relative aspect-[3/4] overflow-hidden">
                        <img src="{$posterUrl}" 
                             alt="{$anime->title}"
                             loading="lazy"
                             decoding="async"
                             width="200"
                             height="300"
                             class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110 bg-gray-800"
                             style="{$blurStyle}">
                        
                        {$adultOverlay}
                        
                        <div class="absolute inset-0 bg-gradient-to-t from-black/90 via-black/40 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                        
                        <div class="absolute top-3 left-3 flex items-center gap-2">
                            <div class="bg-gradient-to-r from-red-600 to-red-700 text-[10px] font-black px-3 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wider">
                                EP {$episode->episode_number}
                            </div>
                            {$newBadge}
                        </div>
                        <div class="absolute top-3 right-3 bg-black/60 backdrop-blur-md text-[10px] font-bold px-3 py-1.5 rounded-lg border border-white/10 text-white">
                            {$anime->type}
                        </div>

                        <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-all duration-300 transform scale-75 group-hover:scale-100">
                            <div class="w-16 h-16 bg-gradient-to-br from-red-600 to-red-700 rounded-full flex items-center justify-center shadow-xl shadow-red-600/50">
                                <svg class="w-8 h-8 text-white ml-1" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                                </svg>
                            </div>
                        </div>
                    </div>
                    
                    <div class="p-4 bg-gradient-to-b from-[#1a1d24] to-[#0f1115]">
                        <h3 class="text-white font-bold text-sm line-clamp-2 group-hover:text-red-500 transition-colors min-h-[2.5rem]">{$anime->title}</h3>
                        <div class="flex items-center justify-between mt-3 pt-3 border-t border-white/10">
                            <span class="text-[10px] text-gray-500 font-semibold italic">Sub Indo</span>
                            <span class="text-[10px] text-yellow-500 font-black">★ {$anime->rating}</span>
                        </div>
                    </div>
                </div>
            </a>
            HTML;
        }

        // Get episode IDs for comparison
        $episodeIdList = $episodes->pluck('id')->toArray();

        return response()->json([
            'html' => $html,
            'count' => count($episodes),
            'episode_ids' => $episodeIdList,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    /**
     * Trigger update notification (called when episode is created/updated)
     */
    public static function triggerUpdate()
    {
        Cache::put('latest_episode_update_time', now()->timestamp, 3600);
        // Also clear the home cache
        Cache::forget('home_latest_episodes');
        Cache::forget('latest_episodes_hash');
    }
}
