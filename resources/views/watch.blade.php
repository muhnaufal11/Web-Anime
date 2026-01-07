@extends('layouts.app')
@section('title', 'Nonton ' . $episode->anime->title . ' Ep ' . $episode->episode_number)
@php
    $episodeDescription = \Illuminate\Support\Str::limit(strip_tags($episode->description ?: $episode->anime->synopsis), 150);
    $poster = $episode->anime->poster_image ? asset('storage/' . $episode->anime->poster_image) : asset('images/placeholder.png');
@endphp
@section('meta_description', $episodeDescription)
@section('canonical', route('watch', ['episode' => $episode->slug]))
@section('og_type', 'video.episode')
@section('og_image', $poster)
@push('structured-data')
<script type="application/ld+json">
{!! json_encode([
    '@context' => 'https://schema.org',
    '@type' => 'Episode',
    'name' => $episode->anime->title . ' Episode ' . $episode->episode_number,
    'description' => $episodeDescription,
    'episodeNumber' => $episode->episode_number,
    'partOfSeries' => [
        '@type' => 'TVSeries',
        'name' => $episode->anime->title,
        'url' => route('detail', ['anime' => $episode->anime->slug]),
    ],
    'url' => route('watch', ['episode' => $episode->slug]),
    'image' => $poster,
    'potentialAction' => [
        '@type' => 'WatchAction',
        'target' => route('watch', ['episode' => $episode->slug]),
    ],
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT) !!}
</script>
@endpush

@section('content')
{{-- Video Ad Overlay with Timer --}}
<x-video-ad-overlay position="video_overlay" page="watch" :timerSeconds="5" />

{{-- Ad Before Video --}}
<x-ad-slot position="video_before" page="watch" />

<div class="max-w-7xl mx-auto px-4 py-4 sm:py-8">
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-4 sm:gap-8">
        <div class="lg:col-span-3">
            {{-- Video Player with Server Selector --}}
            <div class="mb-4 sm:mb-8">
                @livewire('video-player', ['episode' => $episode])
            </div>
            
            {{-- Ad After Video --}}
            <x-ad-slot position="video_after" page="watch" />

            <div class="theme-card rounded-xl sm:rounded-2xl p-4 sm:p-8 border theme-border">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4 mb-4 sm:mb-6">
                    <h1 class="text-xl sm:text-3xl font-black tracking-tight">
                        {{ $episode->anime->title }} <span class="text-red-600">- Ep {{ $episode->episode_number }}</span>
                    </h1>
                    <div class="flex gap-2">
                        @if($prevEpisodeUrl)
                            <a href="{{ $prevEpisodeUrl }}" class="flex-1 sm:flex-none px-4 py-2 theme-elevated border theme-border hover:bg-white/10 text-xs font-bold rounded-lg transition uppercase tracking-wide text-center" title="Episode {{ $prevEpisode->episode_number ?? '' }}">
                                PREV
                            </a>
                        @else
                            <span class="flex-1 sm:flex-none px-4 py-2 theme-elevated border theme-border text-xs font-bold rounded-lg uppercase tracking-wide opacity-50 cursor-not-allowed text-center" aria-disabled="true">
                                PREV
                            </span>
                        @endif

                        @if($nextEpisodeUrl)
                            <a href="{{ $nextEpisodeUrl }}" class="flex-1 sm:flex-none px-4 py-2 theme-elevated border theme-border hover:bg-white/10 text-xs font-bold rounded-lg transition uppercase tracking-wide text-center" title="Episode {{ $nextEpisode->episode_number ?? '' }}">
                                NEXT
                            </a>
                        @else
                            <span class="flex-1 sm:flex-none px-4 py-2 theme-elevated border theme-border text-xs font-bold rounded-lg uppercase tracking-wide opacity-50 cursor-not-allowed text-center" aria-disabled="true">
                                NEXT
                            </span>
                        @endif
                    </div>
                </div>
                <p class="text-gray-400 leading-relaxed italic border-l-4 border-red-600 pl-4 text-sm sm:text-base">{{ $episode->description }}</p>
            </div>

            <!-- Comments Section -->
            <div class="mt-4 sm:mt-8 theme-card rounded-xl sm:rounded-2xl p-4 sm:p-6 border theme-border">
                <div class="flex items-center gap-2 sm:gap-3 mb-4 sm:mb-6">
                    <div class="w-1 sm:w-1.5 h-6 sm:h-8 bg-gradient-to-b from-red-600 to-red-700 rounded-full"></div>
                    <div>
                        <h2 class="text-lg sm:text-2xl font-black uppercase tracking-tight">Komentar</h2>
                        <p class="text-gray-400 text-xs sm:text-sm mt-0.5 sm:mt-1">{{ $comments->total() }} komentar</p>
                    </div>
                </div>

                @auth
                <div class="mb-4 sm:mb-6">
                    <form action="{{ route('comments.store') }}" method="POST" class="space-y-2 sm:space-y-3">
                        @csrf
                        <input type="hidden" name="anime_id" value="{{ $episode->anime_id }}">
                        <input type="hidden" name="episode_id" value="{{ $episode->id }}">
                        <textarea name="comment" rows="3" placeholder="Tulis komentar kamu di sini..." class="w-full px-3 sm:px-4 py-2 sm:py-3 theme-input border-2 theme-border rounded-xl text-sm placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all" required maxlength="1000"></textarea>
                        <div class="flex items-center justify-between">
                            <p class="text-gray-500 text-xs hidden sm:block">Maksimal 1000 karakter</p>
                            <button type="submit" class="w-full sm:w-auto px-4 sm:px-5 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white text-sm font-bold rounded-lg transition-all">Kirim Komentar</button>
                        </div>
                    </form>
                </div>
                @else
                @php
                    $loginUrl = \Illuminate\Support\Facades\Route::has('auth.login')
                        ? route('auth.login')
                        : (\Illuminate\Support\Facades\Route::has('filament.auth.login')
                            ? route('filament.auth.login')
                            : url('/admin/login'));
                @endphp
                <div class="mb-6 bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 text-center">
                    <a href="{{ $loginUrl }}" class="text-yellow-400 font-semibold underline">Login</a> untuk memberikan komentar
                </div>
                @endauth

                <div class="space-y-6">
                    @forelse($comments as $comment)
                        <div class="theme-elevated rounded-xl p-4 border theme-border">
                            <div class="flex gap-3">
                                <div class="w-10 h-10 rounded-full bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white font-black">
                                    {{ strtoupper(substr($comment->user->name, 0, 1)) }}
                                </div>
                                <div class="flex-1">
                                    <div class="flex items-center gap-2 mb-1">
                                        <span class="font-bold text-white">{{ $comment->user->name }}</span>
                                        <span class="text-gray-500 text-xs">{{ $comment->created_at->diffForHumans() }}</span>
                                    </div>
                                    <p class="text-gray-200">{{ $comment->comment }}</p>
                                    <div class="flex items-center gap-4 mt-3">
                                        @auth
                                            <button type="button" onclick="toggleReplyForm({{ $comment->id }})" class="text-red-500 hover:text-red-400 text-xs font-semibold">Balas</button>
                                            @if($comment->user_id === auth()->id())
                                                <form action="{{ route('comments.destroy', $comment) }}" method="POST" class="inline">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" onclick="return confirm('Hapus komentar ini?')" class="text-gray-500 hover:text-red-500 text-xs font-semibold">Hapus</button>
                                                </form>
                                            @endif
                                        @endauth
                                    </div>

                                    @auth
                                    <div id="reply-form-{{ $comment->id }}" class="hidden mt-3">
                                        <form action="{{ route('comments.store') }}" method="POST" class="space-y-2">
                                            @csrf
                                            <input type="hidden" name="anime_id" value="{{ $episode->anime_id }}">
                                            <input type="hidden" name="episode_id" value="{{ $episode->id }}">
                                            <input type="hidden" name="parent_id" value="{{ $comment->id }}">
                                            <textarea name="comment" rows="3" placeholder="Tulis balasan..." class="w-full px-4 py-3 theme-input border-2 theme-border rounded-xl placeholder-gray-500 focus:border-red-600 focus:ring-2 focus:ring-red-600/50 focus:outline-none transition-all" required maxlength="1000"></textarea>
                                            <div class="flex gap-2">
                                                <button type="submit" class="px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-semibold rounded-lg text-xs">Kirim Balasan</button>
                                                <button type="button" onclick="toggleReplyForm({{ $comment->id }})" class="px-4 py-2 bg-white/5 hover:bg-white/10 text-gray-400 hover:text-white font-semibold rounded-lg text-xs">Batal</button>
                                            </div>
                                        </form>
                                    </div>
                                    @endauth
                                </div>
                            </div>

                            @if($comment->replies->count() > 0)
                                <div class="mt-4 ml-12 space-y-3 border-l-2 border-red-600/30 pl-4">
                                    @foreach($comment->replies as $reply)
                                        <div class="flex gap-3">
                                            <div class="w-8 h-8 rounded-full bg-gradient-to-br from-gray-600 to-gray-700 flex items-center justify-center text-white font-bold text-xs">
                                                {{ strtoupper(substr($reply->user->name, 0, 1)) }}
                                            </div>
                                            <div class="flex-1">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="font-bold text-white text-xs">{{ $reply->user->name }}</span>
                                                    <span class="text-gray-500 text-xs">{{ $reply->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-gray-300 text-sm">{{ $reply->comment }}</p>
                                                @auth
                                                    @if($reply->user_id === auth()->id())
                                                        <form action="{{ route('comments.destroy', $reply) }}" method="POST" class="inline mt-1">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" onclick="return confirm('Hapus balasan ini?')" class="text-gray-500 hover:text-red-500 text-xs font-semibold">Hapus</button>
                                                        </form>
                                                    @endif
                                                @endauth
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-12 bg-white/5 border-2 border-dashed border-white/10 rounded-xl">
                            <div class="text-3xl mb-3">💬</div>
                            <p class="text-gray-400 font-semibold">Belum ada komentar</p>
                            <p class="text-gray-500 text-sm mt-1">Jadilah yang pertama berkomentar!</p>
                        </div>
                    @endforelse
                </div>

                @if($comments->hasPages())
                    <div class="mt-6">
                        {{ $comments->links() }}
                    </div>
                @endif
            </div>
        </div>

        <aside class="space-y-6">
            <div class="theme-card rounded-2xl p-6 border theme-border">
                <h3 class="text-lg font-black mb-4 uppercase tracking-widest italic">Daftar Episode</h3>
                <div class="space-y-2 max-h-[500px] overflow-y-auto pr-2 custom-scrollbar">
                    @foreach($animeEpisodes as $ep)
                        @php
                            // Pakai timestamp server aktif terbaru (updated_at > created_at) sebagai referensi, fallback ke created_at episode
                            $latestServerAddedAt = $ep->videoServers->max(function ($server) {
                                return $server->updated_at ?? $server->created_at;
                            });
                            $referenceTime = $latestServerAddedAt ?: $ep->created_at;

                            $minutesSinceAdded = $referenceTime ? now()->diffInMinutes($referenceTime) : null;
                            $isNew = $minutesSinceAdded !== null && $minutesSinceAdded <= (72 * 60); // mark new if within 72 hours

                            if (is_null($minutesSinceAdded)) {
                                $addedLabel = 'Episode tersedia';
                            } elseif ($minutesSinceAdded < 60) {
                                $addedLabel = 'Baru saja';
                            } elseif ($minutesSinceAdded < 1440) {
                                $addedLabel = floor($minutesSinceAdded / 60) . ' jam lalu';
                            } else {
                                $addedLabel = floor($minutesSinceAdded / 1440) . ' hari lalu';
                            }
                        @endphp

                        <a href="{{ route('watch', $ep) }}"
                           @class([
                               'flex items-center gap-4 p-3 rounded-xl transition-all border',
                               'bg-red-600 text-white border-red-500/60 shadow-lg shadow-red-600/30' => $ep->id === $episode->id,
                               'theme-elevated theme-border hover:bg-white/10' => $ep->id !== $episode->id,
                           ])>
                            <div class="w-10 h-10 flex-shrink-0 bg-black/20 rounded-lg flex items-center justify-center font-bold">
                                {{ $ep->episode_number }}
                            </div>
                            <div class="min-w-0">
                                <p class="text-xs font-bold truncate leading-tight uppercase">{{ $ep->title }}</p>
                                <div class="flex items-center gap-2 text-[10px]">
                                    @if($isNew)
                                        <span class="px-2 py-0.5 rounded-full bg-red-600/20 text-red-400 border border-red-500/40 font-black tracking-wide">BARU</span>
                                    @endif
                                    <span class="opacity-60">{{ $addedLabel }}</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
            
            <div class="theme-card rounded-2xl p-4 border theme-border">
                <img src="{{ $episode->anime->poster_image ? asset('storage/' . $episode->anime->poster_image) : asset('images/placeholder.png') }}" 
                     alt="{{ $episode->anime->title }}"
                     class="w-full h-40 object-cover rounded-xl mb-4 shadow-lg bg-gray-800">
                <h4 class="text-sm font-black uppercase">{{ $episode->anime->title }}</h4>
                <div class="flex items-center gap-2 mt-2">
                    <span class="text-yellow-500 font-bold">★ {{ number_format($episode->anime->rating, 1) }}</span>
                    <span class="text-xs text-gray-500 italic">{{ $episode->anime->type }}</span>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
.custom-scrollbar::-webkit-scrollbar { width: 4px; }
.custom-scrollbar::-webkit-scrollbar-thumb { background: #dc2626; border-radius: 10px; }
</style>

<script>
function toggleReplyForm(id) {
    var el = document.getElementById('reply-form-' + id);
    if (el) {
        el.classList.toggle('hidden');
    }
}
</script>

@auth
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Find all video elements (iframe or video tags)
    const videoContainer = document.querySelector('.aspect-video');
    let videoElement = null;
    let iframeElement = null;
    let progressInterval = null;
    let lastSavedProgress = 0;
    
    // For iframe tracking (external servers)
    let watchStartTime = null;
    let totalWatchedSeconds = 0;
    let isWatching = false;
    let pageVisibilityInterval = null;

    // Try to find video element
    function findVideoElement() {
        videoElement = document.querySelector('video');
        iframeElement = document.querySelector('iframe');
        
        if (videoElement) {
            console.log('Direct video player detected');
            setupDirectVideoTracking();
        } else if (iframeElement) {
            console.log('External iframe player detected');
            setupIframeTracking();
        }
    }

    // Direct video tracking (local server)
    function setupDirectVideoTracking() {
        if (!videoElement) return;

        // Save progress every 10 seconds
        progressInterval = setInterval(() => {
            if (videoElement && !videoElement.paused) {
                const currentTime = Math.floor(videoElement.currentTime);
                const duration = Math.floor(videoElement.duration);
                const completed = currentTime >= duration - 30; // Mark completed if within 30s of end

                // Only save if progress changed significantly (at least 5 seconds)
                if (Math.abs(currentTime - lastSavedProgress) >= 5) {
                    saveProgress(currentTime, completed, duration);
                    lastSavedProgress = currentTime;
                }
            }
        }, 10000); // Every 10 seconds

        // Save on video end
        videoElement.addEventListener('ended', function() {
            const duration = Math.floor(videoElement.duration);
            saveProgress(duration, true, duration);
        });

        // Save on page unload
        window.addEventListener('beforeunload', function() {
            if (videoElement && !videoElement.paused) {
                const currentTime = Math.floor(videoElement.currentTime);
                const duration = Math.floor(videoElement.duration);
                const completed = currentTime >= duration - 30;
                saveProgress(currentTime, completed, duration);
            }
        });
    }

    // Iframe tracking for external servers (mp4upload, etc)
    function setupIframeTracking() {
        if (!iframeElement) return;

        console.log('Setting up iframe tracking for external player');
        
        // Assume standard episode duration (24 min anime)
        const estimatedDuration = {{ $episode->duration ?? 1440 }};
        
        // Start tracking when iframe loads
        iframeElement.addEventListener('load', function() {
            console.log('Iframe loaded, starting watch tracking');
            startWatchTracking();
        });

        // Track visibility changes (pause tracking when tab hidden)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                pauseWatchTracking();
            } else {
                resumeWatchTracking();
            }
        });

        // Save progress every 30 seconds
        progressInterval = setInterval(() => {
            if (isWatching) {
                const progressSeconds = Math.min(totalWatchedSeconds, estimatedDuration);
                const completed = progressSeconds >= (estimatedDuration * 0.9); // 90% watched = completed
                
                if (Math.abs(progressSeconds - lastSavedProgress) >= 10) {
                    console.log(`Saving iframe progress: ${progressSeconds}s / ${estimatedDuration}s`);
                    saveProgress(progressSeconds, completed, estimatedDuration);
                    lastSavedProgress = progressSeconds;
                }
            }
        }, 30000); // Every 30 seconds

        // Save on page unload
        window.addEventListener('beforeunload', function() {
            if (isWatching) {
                updateWatchTime();
                const progressSeconds = Math.min(totalWatchedSeconds, estimatedDuration);
                const completed = progressSeconds >= (estimatedDuration * 0.9);
                saveProgress(progressSeconds, completed, estimatedDuration);
            }
        });

        // Start tracking immediately
        startWatchTracking();
    }

    function startWatchTracking() {
        if (isWatching) return;
        
        isWatching = true;
        watchStartTime = Date.now();
        
        // Update watch time every second
        pageVisibilityInterval = setInterval(() => {
            if (isWatching && !document.hidden) {
                updateWatchTime();
            }
        }, 1000);
        
        console.log('Watch tracking started');
    }

    function pauseWatchTracking() {
        if (!isWatching) return;
        updateWatchTime();
        isWatching = false;
        console.log('Watch tracking paused');
    }

    function resumeWatchTracking() {
        if (isWatching) return;
        isWatching = true;
        watchStartTime = Date.now();
        console.log('Watch tracking resumed');
    }

    function updateWatchTime() {
        if (watchStartTime) {
            const elapsed = Math.floor((Date.now() - watchStartTime) / 1000);
            totalWatchedSeconds += elapsed;
            watchStartTime = Date.now();
        }
    }

    function saveProgress(seconds, completed, duration) {
        fetch('{{ route("watch.progress", $episode) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                progress: seconds,
                completed: completed,
                duration: duration || 1440
            })
        }).then(response => {
            if (response.ok) {
                console.log(`Progress saved: ${seconds}s (completed: ${completed})`);
            }
        }).catch(error => console.log('Progress save failed:', error));
    }

    // Initial attempt to find video
    findVideoElement();

    // Retry after 2 seconds if not found (for lazy-loaded videos)
    setTimeout(findVideoElement, 2000);

    // Cleanup on page unload
    window.addEventListener('beforeunload', () => {
        if (progressInterval) {
            clearInterval(progressInterval);
        }
        if (pageVisibilityInterval) {
            clearInterval(pageVisibilityInterval);
        }
    });
});
</script>
@endauth
@endsection