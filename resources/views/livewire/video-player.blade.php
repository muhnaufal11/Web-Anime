{{-- Video Player Component --}}
@php($playerContainerId = 'player-shell-' . $episode->id)
@php($rawEmbed = $selectedServer->embed_url ?? null)
@php($embedSource = $rawEmbed ? \App\Services\VideoEmbedHelper::proxify($rawEmbed) : null)
<div class="w-full space-y-4">
    {{-- Video Player Container --}}
    <div class="relative group" x-data="{ fullscreen: false }">
        <div id="{{ $playerContainerId }}" class="theme-elevated rounded-lg overflow-hidden shadow-2xl aspect-video border theme-border fullscreen-container">
        @if($selectedServer)
            @if($embedSource && str_contains($rawEmbed, '<iframe'))
                {{-- Handle Full Iframe Tags --}}
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
                        {!! $embedSource !!}
                    </div>
                </div>

            @elseif($rawEmbed && str($rawEmbed)->lower()->endsWith('.mp4'))
                {{-- Handle Direct MP4 Links --}}
                <video 
                    src="{{ $embedSource ?? $rawEmbed }}" 
                    class="w-full h-full object-contain" 
                    controls 
                    autoplay>
                </video>

            @elseif($rawEmbed && str($rawEmbed)->lower()->endsWith('.m3u8'))
                {{-- Handle HLS Streaming Links --}}
                <video 
                    id="hls-player-{{ $selectedServer->id }}" 
                    class="w-full h-full object-contain" 
                    controls
                    autoplay>
                </video>
                @push('scripts')
                    <script>
                        (function initHlsPlayer(){
                            const video = document.getElementById('hls-player-{{ $selectedServer->id }}');
                            const src = '{{ $embedSource ?? $rawEmbed }}';
                            if (!video) return;
                            function setup(){
                                if (video.canPlayType('application/vnd.apple.mpegurl')) {
                                    video.src = src;
                                } else if (window.Hls) {
                                    const hls = new Hls({
                                        maxBufferLength: 30,
                                        enableWorker: true,
                                    });
                                    hls.loadSource(src);
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
                        })();
                    </script>
                @endpush

            @elseif($embedSource && str_contains($rawEmbed, 'http'))
                {{-- Handle Standard Embed URL (Iframe) --}}
                <iframe 
                    src="{{ $embedSource }}" 
                    class="w-full h-full border-none" 
                    allow="autoplay; fullscreen; picture-in-picture; encrypted-media; clipboard-write" 
                    allowfullscreen
                    referrerpolicy="no-referrer">
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
                            'px-2 sm:px-3 py-1.5 sm:py-2 rounded-lg text-[10px] sm:text-xs font-bold uppercase tracking-wide transition-all duration-200 truncate',
                            'bg-red-600 hover:bg-red-700 text-white ring-2 ring-red-500' => $selectedServerId === $server->id,
                            'theme-elevated border theme-border hover:bg-white/10 text-gray-500' => $selectedServerId !== $server->id,
                        ])
                    >
                        {{ $server->server_name }}
                    </button>
                @endforeach
            </div>
            @if($selectedServer)
                <div class="mt-2 sm:mt-3 text-[10px] sm:text-xs text-gray-400">
                    Sedang menonton: <span class="text-red-500 font-bold">{{ $selectedServer->server_name }}</span>
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
    </script>
    @endpush
    @endonce
