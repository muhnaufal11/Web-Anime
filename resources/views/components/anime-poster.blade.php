@props([
    'anime',
    'class' => '',
    'showOverlay' => true,
])

@php
    $posterUrl = $anime->poster_image 
        ? asset('storage/' . $anime->poster_image) 
        : asset('images/placeholder.png');
    $shouldBlur = $anime->shouldBlurPoster();
    $isAdult = $anime->isAdultContent();
@endphp

<div class="relative {{ $class }}">
    {{-- Poster Image --}}
    <img 
        src="{{ $posterUrl }}" 
        alt="{{ $anime->title }}"
        {{ $attributes->merge(['class' => $shouldBlur ? 'blur-xl' : '']) }}
        loading="lazy"
    >
    
    {{-- Adult Content Overlay (if should blur) --}}
    @if($shouldBlur && $showOverlay)
        <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/70 text-white p-4 text-center">
            <svg class="w-12 h-12 mb-2 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
            </svg>
            <span class="text-sm font-bold">18+</span>
            <span class="text-xs mt-1">Konten Dewasa</span>
            @guest
                <a href="{{ route('auth.login') }}" class="mt-2 px-3 py-1 bg-red-600 hover:bg-red-700 rounded text-xs transition">
                    Login untuk Lihat
                </a>
            @else
                <span class="mt-2 text-xs text-gray-300">Umur tidak mencukupi</span>
            @endguest
        </div>
    @endif
    
    {{-- 18+ Badge (for adult content that user CAN view) --}}
    @if($isAdult && !$shouldBlur)
        <div class="absolute top-1 right-1 bg-red-600 text-white text-xs font-bold px-1.5 py-0.5 rounded">
            18+
        </div>
    @endif
</div>
