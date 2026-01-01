{{-- Video Player Component --}}
<div class="w-full space-y-4">
    {{-- Video Player Container --}}
    <div class="theme-elevated rounded-lg overflow-hidden shadow-2xl aspect-video border theme-border">
        @if($selectedServer)
            @if(str_contains($selectedServer->embed_url, '<iframe'))
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
                        {!! $selectedServer->embed_url !!}
                    </div>
                </div>

            @elseif(str($selectedServer->embed_url)->lower()->endsWith('.mp4'))
                {{-- Handle Direct MP4 Links --}}
                <video 
                    src="{{ $selectedServer->embed_url }}" 
                    class="w-full h-full object-contain" 
                    controls 
                    autoplay>
                </video>

            @elseif(str($selectedServer->embed_url)->lower()->endsWith('.m3u8'))
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
                            const src = '{{ $selectedServer->embed_url }}';
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

            @elseif(str_contains($selectedServer->embed_url, 'http'))
                {{-- Handle Standard Embed URL (Iframe) --}}
                <iframe 
                    src="{{ $selectedServer->embed_url }}" 
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
