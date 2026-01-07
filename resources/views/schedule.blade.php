@extends('layouts.app')
@section('title', 'Jadwal Tayang Anime')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-[#1a1d24]">
    <!-- Header Section -->
    <div class="max-w-7xl mx-auto px-4 py-8">
        <div class="mb-10">
            <h1 class="text-4xl md:text-5xl font-black text-white mb-2 uppercase tracking-tighter">
                📅 {{ __('app.schedule.title') }}
            </h1>
            <p class="text-gray-400 text-lg">{{ __('app.schedule.subtitle') }}</p>
        </div>
    </div>

    <!-- Schedule Content -->
    <div class="max-w-7xl mx-auto px-4 pb-20">
        <!-- Day Tabs -->
        <div class="mb-8">
            <div class="flex overflow-x-auto gap-3 pb-4 scrollbar-hide">
                @php
                    $dayNames = [
                        'Monday' => __('app.days.monday'),
                        'Tuesday' => __('app.days.tuesday'),
                        'Wednesday' => __('app.days.wednesday'),
                        'Thursday' => __('app.days.thursday'),
                        'Friday' => __('app.days.friday'),
                        'Saturday' => __('app.days.saturday'),
                        'Sunday' => __('app.days.sunday'),
                    ];
                @endphp
                @foreach($days as $day)
                    <button 
                        onclick="showDay('{{ $day }}')"
                        id="tab-{{ $day }}"
                        class="day-tab px-6 py-3 rounded-xl font-bold text-sm uppercase tracking-wider transition-all duration-300 whitespace-nowrap flex-shrink-0 {{ $currentDay === $day ? 'bg-gradient-to-r from-red-600 to-red-700 text-white shadow-lg shadow-red-600/30' : 'bg-[#1a1d24] text-gray-400 hover:text-white border border-white/10' }}">
                        {{ $dayNames[$day] }}
                        @if($currentDay === $day)
                            <span class="ml-2 text-xs">⭐</span>
                        @endif
                    </button>
                @endforeach
            </div>
        </div>

        <!-- Schedule Content by Day -->
        @foreach($days as $day)
            <div id="day-{{ $day }}" class="day-content {{ $currentDay !== $day ? 'hidden' : '' }}">
                @if($schedulesByDay[$day]->count() > 0)
                    <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-6">
                        @foreach($schedulesByDay[$day] as $schedule)
                            @php $shouldBlurSchedule = $schedule->anime->shouldBlurPoster(); @endphp
                            <div class="bg-[#1a1d24] rounded-2xl border border-white/10 overflow-hidden group hover:border-red-600/50 transition-all duration-300 hover:shadow-xl hover:shadow-red-600/20 flex flex-col h-full">
                                <!-- Poster -->
                                <div class="relative aspect-[3/4] overflow-hidden bg-gray-800">
                                    <a href="{{ $shouldBlurSchedule ? '#' : route('detail', $schedule->anime) }}" class="block w-full h-full" @if($shouldBlurSchedule) onclick="event.preventDefault(); alert('Konten 18+ - Anda harus login dan berusia minimal 18 tahun untuk mengakses.')" @endif>
                                        <img src="{{ $schedule->anime->poster_image ? asset('storage/' . $schedule->anime->poster_image) : asset('images/placeholder.png') }}" 
                                             alt="{{ $schedule->anime->title }}"
                                             class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
                                             style="{{ $shouldBlurSchedule ? 'filter: blur(20px); transform: scale(1.1);' : '' }}">
                                        
                                        @if($shouldBlurSchedule)
                                        <!-- Adult Content Overlay -->
                                        <div class="absolute inset-0 flex flex-col items-center justify-center bg-black/50 text-white z-10">
                                            <span class="text-3xl font-black text-red-500">18+</span>
                                            <span class="text-xs mt-1">Konten Dewasa</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Overlay -->
                                        <div class="absolute inset-0 bg-gradient-to-t from-black/95 via-black/50 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
                                    </a>
                                    
                                    <!-- Type Badge -->
                                    <div class="absolute top-3 right-3 bg-gradient-to-r from-red-600 to-red-700 text-xs font-black px-3 py-1.5 rounded-lg shadow-lg text-white uppercase tracking-wide">
                                        {{ $schedule->anime->type }}
                                    </div>

                                    <!-- Rating -->
                                    <div class="absolute top-3 left-3 bg-black/60 backdrop-blur-sm px-3 py-1.5 rounded-lg border border-yellow-500/50">
                                        <span class="text-yellow-400 font-black text-sm">★ {{ number_format($schedule->anime->rating, 1) }}</span>
                                    </div>
                                </div>

                                <!-- Info -->
                                <div class="flex-1 p-5 flex flex-col">
                                    <!-- Title -->
                                    <a href="{{ $shouldBlurSchedule ? '#' : route('detail', $schedule->anime) }}" class="text-lg font-black text-white hover:text-red-500 transition-colors duration-300 mb-3 line-clamp-2" @if($shouldBlurSchedule) onclick="event.preventDefault(); alert('Konten 18+ - Anda harus login dan berusia minimal 18 tahun untuk mengakses.')" @endif>
                                        {{ $schedule->anime->title }}
                                    </a>
                                    
                                    <!-- Genres -->
                                    <div class="flex flex-wrap gap-2 mb-4">
                                        @foreach($schedule->anime->genres->take(2) as $genre)
                                            <span class="text-xs bg-white/5 border border-white/10 text-gray-400 px-2 py-1 rounded-md">
                                                {{ $genre->name }}
                                            </span>
                                        @endforeach
                                    </div>

                                    <!-- Schedule Info Compact -->
                                    <div class="space-y-3 mb-4 flex-1">
                                        <!-- Broadcast Time -->
                                        <div class="flex items-center gap-2 text-sm">
                                            <div class="w-8 h-8 bg-red-600/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-xs uppercase font-bold">Jam Tayang</p>
                                                <p class="text-white font-bold">
                                                    @if($schedule->broadcast_time)
                                                        @php
                                                            $time = is_string($schedule->broadcast_time) ? $schedule->broadcast_time : $schedule->broadcast_time->format('H:i');
                                                        @endphp
                                                        {{ $time }} WIB
                                                    @else
                                                        <span class="text-gray-500 text-sm">TBA</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Next Episode Date -->
                                        <div class="flex items-center gap-2 text-sm">
                                            <div class="w-8 h-8 bg-blue-600/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-xs uppercase font-bold">Episode Berikutnya</p>
                                                <p class="text-white font-bold">
                                                    @if($schedule->next_episode_date)
                                                        {{ $schedule->next_episode_date->format('d M Y') }}
                                                    @else
                                                        <span class="text-gray-500 text-sm">TBA</span>
                                                    @endif
                                                </p>
                                            </div>
                                        </div>

                                        <!-- Status -->
                                        <div class="flex items-center gap-2 text-sm">
                                            <div class="w-8 h-8 bg-green-600/20 rounded-lg flex items-center justify-center flex-shrink-0">
                                                <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-gray-500 text-xs uppercase font-bold">Status</p>
                                                <p class="text-white font-bold">{{ $schedule->anime->status }}</p>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Notes -->
                                    @if($schedule->notes)
                                        <div class="bg-yellow-500/10 border border-yellow-500/20 rounded-lg p-3 mb-4">
                                            <p class="text-yellow-500 text-xs">
                                                <span class="font-bold">ℹ️ Catatan:</span> {{ $schedule->notes }}
                                            </p>
                                        </div>
                                    @endif

                                    <!-- Action Buttons -->
                                    <div class="flex gap-2">
                                        <a href="{{ route('detail', $schedule->anime) }}" class="flex-1 px-4 py-2.5 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold text-sm rounded-xl transition-all duration-300 shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 text-center uppercase tracking-wider">
                                            Lihat Detail
                                        </a>
                                        @if($schedule->anime->episodes->count() > 0)
                                            <a href="{{ route('watch', $schedule->anime->episodes->first()) }}" class="px-4 py-2.5 bg-white/10 hover:bg-white/20 text-white font-bold text-sm rounded-xl transition-all duration-300 border border-white/20 uppercase tracking-wider">
                                                ▶ Tonton
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- Empty State -->
                    <div class="text-center py-20">
                        <div class="inline-block mb-6">
                            <div class="w-24 h-24 bg-gradient-to-br from-gray-600/20 to-gray-700/20 rounded-full flex items-center justify-center">
                                <svg class="w-12 h-12 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                            </div>
                        </div>
                        <h3 class="text-2xl font-black text-white mb-2">Belum Ada Jadwal</h3>
                        <p class="text-gray-400 mb-6 max-w-md mx-auto">Tidak ada anime yang dijadwalkan tayang di hari {{ $dayNames[$day] }}</p>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<script>
function showDay(day) {
    // Hide all day contents
    document.querySelectorAll('.day-content').forEach(content => {
        content.classList.add('hidden');
    });
    
    // Remove active class from all tabs
    document.querySelectorAll('.day-tab').forEach(tab => {
        tab.classList.remove('bg-gradient-to-r', 'from-red-600', 'to-red-700', 'text-white', 'shadow-lg', 'shadow-red-600/30');
        tab.classList.add('bg-[#1a1d24]', 'text-gray-400', 'border', 'border-white/10');
    });
    
    // Show selected day content
    document.getElementById('day-' + day).classList.remove('hidden');
    
    // Add active class to selected tab
    const activeTab = document.getElementById('tab-' + day);
    activeTab.classList.remove('bg-[#1a1d24]', 'text-gray-400', 'border', 'border-white/10');
    activeTab.classList.add('bg-gradient-to-r', 'from-red-600', 'to-red-700', 'text-white', 'shadow-lg', 'shadow-red-600/30');
}

// Auto scroll to current day tab on load
document.addEventListener('DOMContentLoaded', function() {
    const currentDayTab = document.querySelector('.day-tab.bg-gradient-to-r');
    if (currentDayTab) {
        currentDayTab.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
    }
});
</script>

<style>
    .scrollbar-hide::-webkit-scrollbar {
        display: none;
    }
    .scrollbar-hide {
        -ms-overflow-style: none;
        scrollbar-width: none;
    }
</style>
@endsection
