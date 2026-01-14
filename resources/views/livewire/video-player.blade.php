{{-- Video Player Component --}}
@php
    use Illuminate\Support\Facades\URL;
    use Illuminate\Support\Facades\Crypt;
    
    $playerContainerId = 'player-shell-' . $episode->id;
    $rawEmbed = $selectedServer->embed_url ?? null;
    $embedSource = $rawEmbed ? \App\Services\VideoEmbedHelper::proxify($rawEmbed) : null;
    $needsWarning = $episode->anime->needsWarning();
    $isEcchi = $episode->anime->isEcchiContent();
    $isHentai = $episode->anime->isAdultContent();
    
    // Check for extracted direct video URL
    $hasExtractedVideo = isset($extractedVideo) && !empty($extractedVideo['url']);
    $extractedUrl = $hasExtractedVideo ? ($extractedVideo['url'] ?? null) : null;
    $extractedHost = $hasExtractedVideo ? ($extractedVideo['host'] ?? 'Unknown') : null;
@endphp
<div class="w-full space-y-4" x-data="{ showWarning: {{ $needsWarning ? 'true' : 'false' }}, warningAccepted: false }">
    
    {{-- Content Warning Modal --}}
    @if($needsWarning)
    <div x-show="showWarning && !warningAccepted" 
         x-cloak
         class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100">
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-2xl p-8 max-w-md mx-4 border-2 border-red-600/50 shadow-2xl shadow-red-600/20">
            <div class="text-center">
                {{-- Warning Icon --}}
                <div class="w-20 h-20 mx-auto mb-6 bg-gradient-to-br from-red-600 to-red-700 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                
                {{-- Warning Title --}}
                <h3 class="text-2xl font-black text-white mb-3 uppercase tracking-tight">
                    @if($isHentai)
                        ⚠️ Konten Dewasa (18+)
                    @else
                        ⚠️ Peringatan Konten
                    @endif
                </h3>
                
                {{-- Warning Message --}}
                <p class="text-gray-300 mb-6 leading-relaxed">
                    @if($isHentai)
                        Anime ini mengandung <span class="text-red-500 font-bold">konten dewasa eksplisit</span> yang hanya boleh ditonton oleh pengguna berusia <span class="text-red-500 font-bold">18 tahun ke atas</span>.
                    @else
                        Anime ini mengandung <span class="text-yellow-500 font-bold">adegan ecchi/fanservice</span> yang mungkin tidak cocok untuk semua penonton.
                    @endif
                </p>
                
                {{-- Genre Badge --}}
                <div class="mb-6">
                    @if($isHentai)
                        <span class="inline-block px-4 py-2 bg-red-600/20 border border-red-600 text-red-500 rounded-full text-sm font-bold">
                            🔞 HENTAI - 18+
                        </span>
                    @else
                        <span class="inline-block px-4 py-2 bg-yellow-600/20 border border-yellow-600 text-yellow-500 rounded-full text-sm font-bold">
                            🔥 ECCHI
                        </span>
                    @endif
                </div>
                
                {{-- Buttons --}}
                <div class="flex flex-col sm:flex-row gap-3">
                    <a href="{{ route('home') }}" 
                       class="flex-1 px-6 py-3 bg-gray-700 hover:bg-gray-600 text-white font-bold rounded-xl transition-colors text-center">
                        ← Kembali
                    </a>
                    <button @click="showWarning = false; warningAccepted = true" 
                            class="flex-1 px-6 py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all shadow-lg shadow-red-600/30">
                        Saya Mengerti, Lanjutkan
                    </button>
                </div>
                
                {{-- Disclaimer --}}
                <p class="text-xs text-gray-500 mt-4">
                    Dengan melanjutkan, Anda menyatakan telah memahami jenis konten ini.
                </p>
            </div>
        </div>
    </div>
    @endif

    {{-- Anti-theft Protection Styles --}}
    <style>
        .fullscreen-container::after {
            content: '{{ config('app.name', 'NipNime') }}';
            position: absolute;
            bottom: 20px;
            right: 20px;
            color: rgba(255, 255, 255, 0.3);
            font-size: 14px;
            font-weight: bold;
            pointer-events: none;
            z-index: 9999;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.5);
        }
        
        video::-webkit-media-controls-download-button {
            display: none !important;
        }
        
        video::-webkit-media-controls-enclosure {
            overflow: hidden;
        }
        
        video::-internal-media-controls-download-button {
            display: none !important;
        }
    </style>
    
    {{-- Video Player Container --}}
    <div class="relative group" x-data="{ fullscreen: false }">
        <div id="{{ $playerContainerId }}" 
             class="theme-elevated rounded-lg overflow-hidden shadow-2xl aspect-video border theme-border fullscreen-container"
             oncontextmenu="return false;" 
             onselectstart="return false;" 
             ondragstart="return false;">
        @if($selectedServer)
            @if($hasExtractedVideo && $extractedUrl)
                {{-- Handle Extracted Direct Video URL (Ad-Free) with Fallback --}}
                @php
                    $signedExtractedUrl = URL::temporarySignedRoute(
                        'stream.extracted',
                        now()->addMinutes(60),
                        ['token' => Crypt::encryptString($extractedUrl)]
                    );
                    // Direct URL (bypasses proxy, some hosts work better this way)
                    $directExtractedUrl = URL::temporarySignedRoute(
                        'stream.extracted',
                        now()->addMinutes(60),
                        ['token' => Crypt::encryptString($extractedUrl), 'direct' => 1]
                    );
                    $fallbackIframeUrl = route('player.proxy', Crypt::encryptString($selectedServer->id));
                    // Raw direct URL for final fallback
                    $rawDirectUrl = $extractedUrl;
                @endphp
                <div class="w-full h-full relative bg-black" id="video-container-{{ $selectedServer->id }}">
                    {{-- Extracted Video Player --}}
                    <div id="extracted-wrapper-{{ $selectedServer->id }}" class="w-full h-full">
                        <div class="absolute top-2 right-2 z-10 flex items-center gap-2" id="extracted-badge-{{ $selectedServer->id }}">
                            <span class="px-2 py-1 bg-green-600/90 text-white text-xs font-bold rounded-lg shadow-lg flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                                Bebas Iklan
                            </span>
                            <span class="px-2 py-1 bg-gray-800/90 text-gray-300 text-xs rounded-lg shadow-lg">
                                {{ $extractedHost }}
                            </span>
                        </div>
                        <video 
                            id="extracted-player-{{ $selectedServer->id }}"
                            class="w-full h-full object-contain" 
                            controls 
                            autoplay
                            controlslist="nodownload"
                            data-proxy-url="{{ $signedExtractedUrl }}"
                            data-direct-url="{{ $directExtractedUrl }}"
                            data-raw-url="{{ $rawDirectUrl }}"
                            oncontextmenu="return false;">
                            <source src="{{ $signedExtractedUrl }}" type="video/mp4">
                        </video>
                    </div>
                    
                    {{-- Fallback Iframe (hidden initially) - No sandbox for video playback --}}
                    <div id="fallback-wrapper-{{ $selectedServer->id }}" class="w-full h-full hidden">
                        <div class="absolute top-2 right-2 z-10">
                            <span class="px-2 py-1 bg-orange-600/90 text-white text-xs font-bold rounded-lg shadow-lg">
                                Mode Iframe
                            </span>
                        </div>
                        <iframe 
                            id="fallback-iframe-{{ $selectedServer->id }}"
                            data-src="{{ $fallbackIframeUrl }}"
                            allowfullscreen
                            allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
                            referrerpolicy="no-referrer"
                            class="w-full h-full border-0">
                        </iframe>
                    </div>
                </div>
                
                <script>
                (function() {
                    const serverId = '{{ $selectedServer->id }}';
                    const video = document.getElementById('extracted-player-' + serverId);
                    const extractedWrapper = document.getElementById('extracted-wrapper-' + serverId);
                    const fallbackWrapper = document.getElementById('fallback-wrapper-' + serverId);
                    const fallbackIframe = document.getElementById('fallback-iframe-' + serverId);
                    const badge = document.getElementById('extracted-badge-' + serverId);
                    
                    if (video) {
                        let errorHandled = false;
                        let currentFallbackLevel = 0; // 0=proxy, 1=direct, 2=raw, 3=iframe
                        let hasAnyProgress = false;
                        let playStarted = false;
                        
                        // Get all URL options
                        const proxyUrl = video.dataset.proxyUrl;
                        const directUrl = video.dataset.directUrl;
                        const rawUrl = video.dataset.rawUrl;
                        
                        const updateBadge = (text, colorClass, icon) => {
                            if (!badge) return;
                            const badgeEl = badge.querySelector('span:first-child');
                            if (badgeEl) {
                                badgeEl.className = `px-2 py-1 ${colorClass} text-white text-xs font-bold rounded-lg shadow-lg flex items-center gap-1`;
                                badgeEl.innerHTML = icon + ' ' + text;
                            }
                        };
                        
                        const switchToIframe = (reason) => {
                            if (errorHandled) return;
                            errorHandled = true;
                            
                            console.log('Switching to iframe fallback:', reason);
                            extractedWrapper.classList.add('hidden');
                            fallbackWrapper.classList.remove('hidden');
                            
                            // Load iframe src
                            if (fallbackIframe && fallbackIframe.dataset.src) {
                                fallbackIframe.src = fallbackIframe.dataset.src;
                            }
                        };
                        
                        const tryNextFallback = (reason) => {
                            if (errorHandled || playStarted) return;
                            
                            currentFallbackLevel++;
                            console.log(`Trying fallback level ${currentFallbackLevel}:`, reason);
                            
                            const source = video.querySelector('source');
                            
                            if (currentFallbackLevel === 1 && directUrl) {
                                // Try direct URL (server redirect mode)
                                console.log('Trying direct URL mode...');
                                updateBadge('Mencoba Direct...', 'bg-yellow-600/90', '<svg class="w-3 h-3 animate-spin" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4V2A10 10 0 0 0 2 12h2a8 8 0 0 1 8-8Z"/></svg>');
                                if (source) {
                                    source.src = directUrl;
                                    video.load();
                                    video.play().catch(() => {});
                                }
                                hasAnyProgress = false;
                                // Set timeout for this attempt
                                setTimeout(() => {
                                    if (!playStarted && !errorHandled && currentFallbackLevel === 1) {
                                        tryNextFallback('Direct URL timeout');
                                    }
                                }, 15000);
                            } else if (currentFallbackLevel === 2 && rawUrl) {
                                // Try raw direct URL (may have CORS issues but some hosts allow it)
                                console.log('Trying raw direct URL...');
                                updateBadge('Mencoba Raw...', 'bg-orange-600/90', '<svg class="w-3 h-3 animate-spin" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4V2A10 10 0 0 0 2 12h2a8 8 0 0 1 8-8Z"/></svg>');
                                if (source) {
                                    source.src = rawUrl;
                                    video.load();
                                    video.play().catch(() => {});
                                }
                                hasAnyProgress = false;
                                // Set timeout for this attempt
                                setTimeout(() => {
                                    if (!playStarted && !errorHandled && currentFallbackLevel === 2) {
                                        tryNextFallback('Raw URL timeout');
                                    }
                                }, 10000);
                            } else {
                                // Final fallback: iframe
                                switchToIframe(reason);
                            }
                        };
                        
                        // Track progress
                        video.addEventListener('loadstart', () => { console.log('Video loadstart'); });
                        video.addEventListener('progress', () => { hasAnyProgress = true; });
                        video.addEventListener('loadedmetadata', () => { 
                            hasAnyProgress = true; 
                            console.log('Video metadata loaded');
                        });
                        video.addEventListener('canplay', () => { hasAnyProgress = true; });
                        video.addEventListener('canplaythrough', () => { hasAnyProgress = true; });
                        
                        // Success events
                        video.addEventListener('playing', () => { 
                            playStarted = true;
                            console.log('Video playing successfully at level:', currentFallbackLevel);
                            updateBadge('Bebas Iklan', 'bg-green-600/90', '<svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>');
                        });
                        video.addEventListener('loadeddata', () => { 
                            if (!playStarted) {
                                playStarted = true;
                                console.log('Video data loaded');
                            }
                        });
                        
                        // Handle video error
                        video.addEventListener('error', (e) => {
                            console.log('Video error at level', currentFallbackLevel, e);
                            if (!playStarted) {
                                tryNextFallback('Video error');
                            }
                        });
                        
                        // Handle source error
                        const source = video.querySelector('source');
                        if (source) {
                            source.addEventListener('error', (e) => {
                                console.log('Source error at level', currentFallbackLevel, e);
                                if (!playStarted) {
                                    tryNextFallback('Source error');
                                }
                            });
                        }
                        
                        // Stall detection
                        let stallCount = 0;
                        video.addEventListener('stalled', () => {
                            stallCount++;
                            console.log('Video stalled, count:', stallCount);
                            if (stallCount >= 3 && !playStarted) {
                                tryNextFallback('Too many stalls');
                            }
                        });
                        
                        // Initial timeout for proxy URL (shorter - 15 seconds)
                        setTimeout(() => {
                            if (!playStarted && !errorHandled && currentFallbackLevel === 0) {
                                if (!hasAnyProgress && video.readyState < 1) {
                                    console.log('Proxy timeout - no progress');
                                    tryNextFallback('Proxy timeout');
                                } else if (video.readyState < 3) {
                                    console.log('Proxy loading slowly, waiting...');
                                    setTimeout(() => {
                                        if (!playStarted && !errorHandled && currentFallbackLevel === 0) {
                                            tryNextFallback('Proxy extended timeout');
                                        }
                                    }, 15000);
                                }
                            }
                        }, 15000);
                        
                        // User can manually switch by double-clicking the video area
                        video.addEventListener('dblclick', () => {
                            if (!playStarted && !errorHandled) {
                                switchToIframe('User requested fallback');
                            }
                        });
                    }
                })();
                </script>
            @elseif($embedSource && str_contains($rawEmbed, '<iframe'))
                {{-- Handle Full Iframe Tags (proxied through internal page) --}}
                <div class="w-full h-full relative">
                    <style>
                        .video-wrapper iframe { 
                            width: 100% !important; 
                            height: 100% !important; 
                            position: absolute; 
                            top: 0; 
                            left: 0; 
                            border: none;
                        }
                    </style>
                    <div class="video-wrapper w-full h-full">
                        <iframe 
                            src="{{ route('player.proxy', Crypt::encryptString($selectedServer->id)) }}"
                            allowfullscreen
                            allow="autoplay; fullscreen; picture-in-picture; encrypted-media"
                            referrerpolicy="no-referrer"
                            sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-presentation allow-top-navigation allow-top-navigation-by-user-activation"
                            class="w-full h-full">
                        </iframe>
                    </div>
                </div>

            @elseif($rawEmbed && (str($rawEmbed)->lower()->endsWith('.mp4') || str($rawEmbed)->lower()->endsWith('.webm')))
                {{-- Handle Direct MP4/WebM Links --}}
                @php
                    $signedStreamUrl = URL::temporarySignedRoute(
                        'stream.proxy',
                        now()->addMinutes(30),
                        ['token' => Crypt::encryptString($selectedServer->id)]
                    );
                    $videoType = str($rawEmbed)->lower()->endsWith('.webm') ? 'video/webm' : 'video/mp4';
                @endphp
                <video 
                    id="video-player-{{ $selectedServer->id }}"
                    src="{{ $signedStreamUrl }}"
                    class="w-full h-full object-contain" 
                    controls 
                    autoplay
                    controlslist="nodownload"
                    oncontextmenu="return false;">
                    <source src="{{ $signedStreamUrl }}" type="{{ $videoType }}">
                    Browser tidak mendukung video tag.
                </video>
                <script>
                    document.getElementById('video-player-{{ $selectedServer->id }}')?.addEventListener('contextmenu', e => e.preventDefault());
                </script>

            @elseif($rawEmbed && str($rawEmbed)->lower()->endsWith('.mkv'))
                {{-- Handle MKV Files - Direct HTML5 video with subtitle support --}}
                @php
                    $signedStreamUrl = URL::temporarySignedRoute(
                        'stream.proxy',
                        now()->addMinutes(30),
                        ['token' => Crypt::encryptString($selectedServer->id)]
                    );
                    $subtitleApiUrl = route('video.subtitle', ['token' => Crypt::encryptString($selectedServer->id)]);
                @endphp
                <div id="mkv-container-{{ $selectedServer->id }}" class="w-full h-full relative" style="background: #000;">
                    <video 
                        id="mkv-player-{{ $selectedServer->id }}"
                        src="{{ $signedStreamUrl }}"
                        data-subtitle-api="{{ $subtitleApiUrl }}"
                        class="w-full h-full object-contain" 
                        controls
                        autoplay
                        controlslist="nodownload"
                        oncontextmenu="return false;">
                        Browser tidak mendukung video tag.
                    </video>
                </div>
                @push('head')
                    <!-- SubtitlesOctopus for ASS subtitle rendering -->
                    <script src="/js/subtitles/subtitles-octopus.js"></script>
                @endpush
                @push('scripts')
                    <script>
                        (function initMkvPlayer(){
                            const video = document.getElementById('mkv-player-{{ $selectedServer->id }}');
                            if (!video) return;
                            
                            const subtitleApi = video.dataset.subtitleApi;
                            video.addEventListener('contextmenu', e => e.preventDefault());
                            
                            console.log('Direct video player detected');
                            
                            // Setup subtitles when video is ready
                            function setupSubtitles() {
                                if (!subtitleApi) return;
                                
                                console.log('Fetching subtitles from:', subtitleApi);
                                
                                fetch(subtitleApi)
                                    .then(res => res.json())
                                    .then(data => {
                                        console.log('Subtitle API response:', data);
                                        
                                        if (data.subtitle && data.format === 'vtt') {
                                            // Use native VTT track
                                            const blob = new Blob([data.subtitle], { type: 'text/vtt' });
                                            const url = URL.createObjectURL(blob);
                                            
                                            const track = document.createElement('track');
                                            track.kind = 'subtitles';
                                            track.label = 'Indonesia';
                                            track.srclang = 'id';
                                            track.src = url;
                                            track.default = true;
                                            
                                            video.appendChild(track);
                                            
                                            // Enable the track
                                            video.textTracks[0].mode = 'showing';
                                            console.log('VTT subtitle track added');
                                        } else if (data.subtitle && data.format === 'ass') {
                                            // Use SubtitlesOctopus for ASS format with proper styling
                                            if (window.SubtitlesOctopus) {
                                                try {
                                                    console.log('ASS subtitle content preview:', data.subtitle.substring(0, 500));
                                                    
                                                    window.subtitlesOctopusInstance = new SubtitlesOctopus({
                                                        video: video,
                                                        subContent: data.subtitle,
                                                        workerUrl: '/js/subtitles/subtitles-octopus-worker.js',
                                                        legacyWorkerUrl: '/js/subtitles/subtitles-octopus-worker.js',
                                                        fonts: ['/js/subtitles/default.woff2'],
                                                        fallbackFont: '/js/subtitles/default.woff2',
                                                        lazyFileLoading: false,
                                                        renderMode: 'wasm-blend',
                                                        targetFps: 24,
                                                        lossyRender: false,
                                                        prescaleFactor: 1.0,
                                                        prescaleHeightLimit: 1080,
                                                        maxRenderHeight: 0,
                                                        dropAllAnimations: false,
                                                        libassMemoryLimit: 0,
                                                        libassGlyphLimit: 0,
                                                        debug: true,
                                                        onReady: function() {
                                                            console.log('SubtitlesOctopus ready and rendering');
                                                        },
                                                        onError: function(e, url) {
                                                            console.error('SubtitlesOctopus error:', e, url);
                                                        }
                                                    });
                                                    console.log('ASS subtitle instance created');
                                                } catch (e) {
                                                    console.warn('SubtitlesOctopus failed:', e);
                                                    // Fallback: convert ASS to simple text
                                                    const simpleText = data.subtitle.replace(/\{[^}]*\}/g, '');
                                                    console.log('ASS content (simplified):', simpleText.substring(0, 500));
                                                }
                                            } else {
                                                console.log('SubtitlesOctopus not loaded, ASS subtitles unavailable');
                                            }
                                        } else {
                                            console.log('No subtitle data available');
                                        }
                                    })
                                    .catch(e => console.error('Subtitle fetch failed:', e));
                            }
                            
                            // Load subtitles after video metadata is loaded
                            video.addEventListener('loadedmetadata', function() {
                                console.log('Video metadata loaded');
                                setupSubtitles();
                            });
                            
                            // Auto play
                            video.play().catch(e => console.log('Auto-play blocked:', e));
                        })();
                    </script>
                @endpush

            @elseif($rawEmbed && (str($rawEmbed)->lower()->endsWith('.flv') || str($rawEmbed)->lower()->endsWith('.ts')))
                {{-- Handle FLV and TS Files using mpegts.js --}}
                @php
                    $signedStreamUrl = URL::temporarySignedRoute(
                        'stream.proxy',
                        now()->addMinutes(30),
                        ['token' => Crypt::encryptString($selectedServer->id)]
                    );
                    $videoType = str($rawEmbed)->lower()->endsWith('.flv') ? 'flv' : 'mpegts';
                @endphp
                <video 
                    id="mpegts-player-{{ $selectedServer->id }}"
                    data-src="{{ $signedStreamUrl }}"
                    data-type="{{ $videoType }}"
                    class="w-full h-full object-contain" 
                    controls
                    autoplay
                    controlslist="nodownload"
                    oncontextmenu="return false;">
                    Browser tidak mendukung video tag.
                </video>
                @push('scripts')
                    <script>
                        (function initMpegtsPlayer(){
                            const video = document.getElementById('mpegts-player-{{ $selectedServer->id }}');
                            if (!video) return;
                            
                            const videoSrc = video.dataset.src;
                            const videoType = video.dataset.type;
                            video.addEventListener('contextmenu', e => e.preventDefault());
                            
                            function setupMpegtsPlayer() {
                                if (window.mpegts && mpegts.isSupported()) {
                                    const player = mpegts.createPlayer({
                                        type: videoType,
                                        url: videoSrc,
                                        isLive: false,
                                    }, {
                                        enableWorker: true,
                                        enableStashBuffer: true,
                                    });
                                    
                                    player.attachMediaElement(video);
                                    player.load();
                                    
                                    player.on(mpegts.Events.MEDIA_INFO, function() {
                                        video.play().catch(e => console.log('Auto-play blocked:', e));
                                    });
                                    
                                    video._mpegtsPlayer = player;
                                } else {
                                    console.warn('mpegts.js not supported');
                                }
                            }
                            
                            if (!window.mpegts) {
                                const script = document.createElement('script');
                                script.src = 'https://cdn.jsdelivr.net/npm/mpegts.js@1.7.3/dist/mpegts.min.js';
                                script.onload = setupMpegtsPlayer;
                                document.head.appendChild(script);
                            } else {
                                setupMpegtsPlayer();
                            }
                            
                            window.addEventListener('beforeunload', function() {
                                if (video._mpegtsPlayer) {
                                    video._mpegtsPlayer.destroy();
                                }
                            });
                        })();
                    </script>
                @endpush

            @elseif($rawEmbed && str($rawEmbed)->lower()->endsWith('.m3u8'))
                {{-- Handle HLS Streaming Links --}}
                <video 
                    id="hls-player-{{ $selectedServer->id }}" 
                    data-server="{{ Crypt::encryptString($selectedServer->id) }}"
                    class="w-full h-full object-contain" 
                    controls
                    autoplay
                    controlslist="nodownload"
                    oncontextmenu="return false;">
                </video>
                @push('scripts')
                    <script>
                        (function initHlsPlayer(){
                            const video = document.getElementById('hls-player-{{ $selectedServer->id }}');
                            if (!video) return;
                            
                            const serverId = video.dataset.server;
                            video.addEventListener('contextmenu', e => e.preventDefault());
                            
                            // Fetch video URL from API
                            fetch('{{ route('video.source') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                },
                                body: JSON.stringify({ server: serverId })
                            })
                            .then(response => response.json())
                            .then(data => {
                                if(data.url) {
                                    function setup(){
                                        if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                            video.src = data.url;
                                        } else if (window.Hls) {
                                            const hls = new Hls({
                                                maxBufferLength: 30,
                                                enableWorker: true,
                                            });
                                            hls.loadSource(data.url);
                                            hls.attachMedia(video);
                                        }
                                    }
                                    if (!window.Hls) {
                                        var s=document.createElement('script');
                                        s.src='https://cdn.jsdelivr.net/npm/hls.js@latest';
                                        s.onload=setup;
                                        document.head.appendChild(s);
                                    } else {
                                        setup();
                                    }
                                }
                            })
                            .catch(err => console.error('Failed to load HLS'));
                        })();
                    </script>
                @endpush

            @elseif($embedSource && str_contains($rawEmbed, 'http'))
                {{-- Handle Standard Embed URL (Iframe) --}}
                <iframe 
                    src="{{ route('player.proxy', Crypt::encryptString($selectedServer->id)) }}"
                    class="w-full h-full border-none" 
                    allow="autoplay; fullscreen; picture-in-picture; encrypted-media; clipboard-write" 
                    allowfullscreen
                    referrerpolicy="no-referrer"
                    sandbox="allow-same-origin allow-scripts allow-popups allow-forms allow-presentation allow-top-navigation allow-top-navigation-by-user-activation">
                </iframe>
            @else
                {{-- Handle Error/Invalid URL --}}
                <div class="w-full h-full flex flex-col items-center justify-center text-gray-500 theme-elevated p-6 border-t border theme-border">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-12 w-12 mb-2 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <p class="font-semibold text-sm text-center">Tautan video tidak valid atau tidak didukung.</p>
                    <p class="text-[10px] mt-2 opacity-50 break-all max-w-xs text-center">{{ $selectedServer->embed_url }}</p>
                </div>
            @endif
        @else
            {{-- Placeholder when no server selected --}}
            <div class="w-full h-full flex items-center justify-center text-gray-400 theme-elevated">
                <div class="text-center">
                    <div class="animate-pulse inline-block p-4 bg-slate-800 rounded-full mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z" />
                        </svg>
                    </div>
                    <p class="text-sm font-medium tracking-wide">Pilih server untuk memulai streaming</p>
                </div>
            </div>
        @endif
        </div>
        
        {{-- Custom Fullscreen Button with Keyboard Shortcut --}}
        <div class="absolute top-3 right-3 group/fs z-50">
            <button type="button"
                    class="relative flex items-center gap-2 px-3 py-2 text-xs font-bold uppercase tracking-wider rounded-lg bg-black/70 backdrop-blur-sm border border-white/20 text-white shadow-lg hover:bg-black/90 hover:border-red-500/60 hover:scale-105 transition-all duration-200 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-red-500"
                    onclick="togglePlayerFullscreen('{{ $playerContainerId }}')"
                    aria-label="Toggle fullscreen (Press F)"
                    title="Fullscreen (F)">
                <svg class="w-4 h-4 transition-transform duration-200" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path class="enter-fs-icon" d="M8 3H5a2 2 0 0 0-2 2v3m18 0V5a2 2 0 0 0-2-2h-3m0 18h3a2 2 0 0 0 2-2v-3M3 16v3a2 2 0 0 0 2 2h3" />
                    <path class="exit-fs-icon hidden" d="M8 3v3a2 2 0 0 1-2 2H3m18 0h-3a2 2 0 0 1-2-2V3m0 18v-3a2 2 0 0 1 2-2h3M3 16h3a2 2 0 0 1 2 2v3" />
                </svg>
                <span class="hidden sm:inline text-[11px]">Fullscreen</span>
                <kbd class="hidden md:inline-block ml-1 px-1.5 py-0.5 text-[9px] font-mono bg-white/10 rounded border border-white/20">F</kbd>
            </button>
            
            {{-- Tooltip --}}
            <div class="absolute right-0 top-full mt-2 px-3 py-2 bg-black/90 backdrop-blur-sm text-white text-xs rounded-lg shadow-xl border border-white/10 opacity-0 invisible group-hover/fs:opacity-100 group-hover/fs:visible transition-all duration-200 whitespace-nowrap pointer-events-none">
                <p class="font-bold">Tekan F untuk fullscreen</p>
                <p class="text-gray-400 text-[10px] mt-0.5">Atau klik tombol ini</p>
            </div>
        </div>
    </div>

    {{-- Server List --}}
    @if(count($episode->videoServers) > 0)
        <div class="theme-card rounded-lg p-3 sm:p-4 border theme-border">
            <div class="flex items-center gap-2 mb-2 sm:mb-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12l4-4m-4 4l4 4" />
                </svg>
                <p class="text-gray-300 font-semibold text-xs sm:text-sm">Pilih Server:</p>
            </div>
            <div class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 lg:grid-cols-6 gap-1.5 sm:gap-2">
                @foreach($episode->videoServers as $server)
                    <button
                        wire:click="selectServer({{ $server->id }})"
                        @class([
                            'px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg text-[10px] sm:text-xs font-bold uppercase tracking-wide transition-all duration-200 truncate relative',
                            'bg-red-600 hover:bg-red-700 text-white ring-2 ring-red-500' => $selectedServerId === $server->id,
                            'theme-elevated border theme-border hover:bg-white/10 text-gray-500' => $selectedServerId !== $server->id,
                        ])
                    >
                        @if($server->is_default)
                            <span class="absolute -top-1 -right-1 w-3 h-3 bg-yellow-500 rounded-full flex items-center justify-center text-[6px] text-black font-black">★</span>
                        @endif
                        {{ $server->server_name }}
                    </button>
                @endforeach
            </div>
            @if($selectedServer)
                <div class="mt-2 sm:mt-3 text-[10px] sm:text-xs text-gray-400">
                    Sedang menonton: <span class="text-red-500 font-bold">{{ $selectedServer->server_name }}</span>
                    @if($selectedServer->is_default)
                        <span class="ml-1 text-yellow-500">★ Default</span>
                    @endif
                </div>
            @endif
        </div>
    @endif
</div>
    @once
    @push('scripts')
    <script>
    // Global fullscreen functions
    window.togglePlayerFullscreen = function(containerId) {
        const container = document.getElementById(containerId);
        if (!container) {
            console.error('Container not found:', containerId);
            return;
        }
        
        const fullscreenElement = document.fullscreenElement || 
                                 document.webkitFullscreenElement || 
                                 document.msFullscreenElement || 
                                 document.mozFullScreenElement;
        
        if (fullscreenElement) {
            // Exit fullscreen
            if (document.exitFullscreen) {
                document.exitFullscreen();
            } else if (document.webkitExitFullscreen) {
                document.webkitExitFullscreen();
            } else if (document.msExitFullscreen) {
                document.msExitFullscreen();
            } else if (document.mozCancelFullScreen) {
                document.mozCancelFullScreen();
            }
        } else {
            // Enter fullscreen
            if (container.requestFullscreen) {
                container.requestFullscreen();
            } else if (container.webkitRequestFullscreen) {
                container.webkitRequestFullscreen();
            } else if (container.msRequestFullscreen) {
                container.msRequestFullscreen();
            } else if (container.mozRequestFullScreen) {
                container.mozRequestFullScreen();
            }
        }
    };

    // Initialize fullscreen handlers
    function initFullscreenControls() {
        // Keyboard shortcut: Press F to toggle fullscreen
        document.addEventListener('keydown', function(event) {
            if (event.key.toLowerCase() === 'f' && !event.ctrlKey && !event.altKey && !event.metaKey) {
                const activeElement = document.activeElement;
                const isTyping = activeElement && (
                    activeElement.tagName === 'INPUT' || 
                    activeElement.tagName === 'TEXTAREA' || 
                    activeElement.isContentEditable
                );
                
                if (!isTyping) {
                    event.preventDefault();
                    const container = document.querySelector('.fullscreen-container');
                    if (container) {
                        window.togglePlayerFullscreen(container.id);
                    }
                }
            }
            
            // ESC alternative handler
            if (event.key === 'Escape') {
                const fullscreenElement = document.fullscreenElement || 
                                         document.webkitFullscreenElement || 
                                         document.msFullscreenElement || 
                                         document.mozFullScreenElement;
                if (fullscreenElement) {
                    updateFullscreenUI();
                }
            }
        });

        // Update UI when fullscreen changes
        function updateFullscreenUI() {
            const fullscreenElement = document.fullscreenElement || 
                                     document.webkitFullscreenElement || 
                                     document.msFullscreenElement || 
                                     document.mozFullScreenElement;
            
            const isFullscreen = !!fullscreenElement;
            
            // Update all fullscreen buttons
            document.querySelectorAll('.fullscreen-container').forEach(container => {
                const button = container.parentElement.querySelector('button[onclick*="togglePlayerFullscreen"]');
                if (button) {
                    const enterIcon = button.querySelector('.enter-fs-icon');
                    const exitIcon = button.querySelector('.exit-fs-icon');
                    
                    if (isFullscreen && fullscreenElement.id === container.id) {
                        button.classList.add('ring-2', 'ring-red-500', 'bg-red-600/80', 'border-red-500');
                        if (enterIcon) enterIcon.classList.add('hidden');
                        if (exitIcon) exitIcon.classList.remove('hidden');
                    } else {
                        button.classList.remove('ring-2', 'ring-red-500', 'bg-red-600/80', 'border-red-500');
                        if (enterIcon) enterIcon.classList.remove('hidden');
                        if (exitIcon) exitIcon.classList.add('hidden');
                    }
                }
            });
        }

        // Listen to fullscreen change events
        document.addEventListener('fullscreenchange', updateFullscreenUI);
        document.addEventListener('webkitfullscreenchange', updateFullscreenUI);
        document.addEventListener('msfullscreenchange', updateFullscreenUI);
        document.addEventListener('mozfullscreenchange', updateFullscreenUI);
        
        // Initial UI update
        updateFullscreenUI();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFullscreenControls);
    } else {
        initFullscreenControls();
    }
    
    // Protect video source - disable right click on video only
    document.addEventListener('contextmenu', function(e) {
        if (e.target.tagName === 'VIDEO' || e.target.tagName === 'IFRAME') {
            e.preventDefault();
            return false;
        }
    });
    </script>
    @endpush
    @endonce
