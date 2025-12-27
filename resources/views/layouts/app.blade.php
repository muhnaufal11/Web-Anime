<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - nipnime</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-[#0f1115] text-gray-300 font-['Inter'] overflow-x-hidden">
    
    <nav class="bg-[#0f1115]/95 backdrop-blur-md border-b border-white/5 sticky top-0 z-50 shadow-xl shadow-black/40">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 md:h-20">
                
                <div class="flex items-center gap-3 md:gap-8">
                    <a href="{{ route('home') }}" class="group flex items-center gap-2 hover:scale-105 transition-transform shrink-0">
                        <div class="w-8 h-8 md:w-10 md:h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-lg md:rounded-xl flex items-center justify-center shadow-lg shadow-red-600/20 group-hover:shadow-red-600/40 transition-all">
                            <svg class="w-5 h-5 md:w-6 md:h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-lg sm:text-2xl md:text-3xl font-black text-white tracking-tighter font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </a>
                    
                    <div class="hidden lg:flex items-center space-x-1 text-[11px] xl:text-xs font-bold uppercase tracking-widest">
                        <a href="{{ route('home') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('home') ? 'text-red-500 bg-white/10' : '' }}">🏠 Home</a>
                        <a href="{{ route('search') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') ? 'text-red-500 bg-white/10' : '' }}">📺 Daftar Anime</a>
                        <a href="{{ route('schedule') }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500 bg-white/10' : '' }}">📅 Jadwal</a>
                        <a href="{{ route('search', ['type' => 'Movie']) }}" class="px-3 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition">🎬 Movie</a>
                    </div>
                </div>

                <div class="flex items-center gap-1 sm:gap-3 md:gap-6">
                    <form action="{{ route('search') }}" method="GET" class="hidden md:block relative group">
                        <input type="text" name="search" placeholder="Cari anime..." 
                               class="w-40 lg:w-64 xl:w-80 bg-[#1a1d24] border border-white/10 text-white rounded-full px-4 py-2 text-xs focus:border-red-600 focus:ring-4 focus:ring-red-600/10 transition-all">
                        <button type="submit" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                        </button>
                    </form>

                    <button id="mobileSearchBtn" class="md:hidden p-2 text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                    </button>

                    <div class="flex items-center gap-2">
                        @auth
                            <div class="relative" id="profileDropdown">
                                <button id="profileButton" class="w-8 h-8 md:w-10 md:h-10 rounded-full bg-red-600 flex items-center justify-center text-white text-xs md:text-sm font-black border-2 border-red-600/20 overflow-hidden">
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" class="w-full h-full object-cover">
                                    @else
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    @endif
                                </button>
                                <div id="profileMenu" class="absolute right-0 mt-3 w-56 bg-[#1a1d24] rounded-xl shadow-2xl opacity-0 invisible transition-all border border-white/10 z-50">
                                    <div class="p-4 border-b border-white/5">
                                        <p class="text-[10px] font-bold text-gray-500 uppercase">Akun Saya</p>
                                        <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                                    </div>
                                    <a href="{{ route('profile.show') }}" class="block px-4 py-3 text-sm hover:bg-white/5 transition">👤 Profil</a>
                                    <form action="{{ route('auth.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-3 text-sm text-red-500 hover:bg-white/5 transition">🚪 Logout</button>
                                    </form>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('auth.login') }}" class="text-[10px] md:text-xs font-bold uppercase p-2 hover:text-red-500 transition">Masuk</a>
                            <a href="{{ route('auth.register') }}" class="text-[10px] md:text-xs font-bold uppercase px-3 py-2 md:px-4 md:py-2.5 bg-red-600 text-white rounded-lg transition-all">Daftar</a>
                        @endauth
                    </div>

                    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                    <div class="hidden sm:flex items-center border-l border-white/10 pl-4 ml-1">
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="holidayToggle" class="sr-only peer" onchange="toggleHolidayEffect()">
                            <div class="w-8 h-4 bg-gray-700 rounded-full peer-checked:bg-red-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-4"></div>
                        </label>
                    </div>
                    @endif

                    <button id="mobileMenuBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                        <svg id="burgerIcon" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <div id="mobileSearchBar" class="hidden md:hidden px-4 pb-4 bg-[#0f1115]">
            <form action="{{ route('search') }}" method="GET">
                <input type="text" name="search" placeholder="Cari anime..." class="w-full bg-[#1a1d24] border border-white/10 text-white rounded-xl px-4 py-3 text-sm focus:border-red-600">
            </form>
        </div>

        <div id="mobileMenu" class="lg:hidden bg-[#0f1115] border-t border-white/5 transition-all duration-500 ease-in-out max-h-0 opacity-0 overflow-hidden pointer-events-none transform -translate-y-4">
            <div class="p-4 space-y-1">
                <a href="{{ route('home') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('home') ? 'bg-red-600 text-white' : 'text-gray-400' }}">🏠 HOME</a>
                <a href="{{ route('search') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('search') ? 'bg-red-600 text-white' : 'text-gray-400' }}">📺 ANIME</a>
                <a href="{{ route('schedule') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold {{ request()->routeIs('schedule') ? 'bg-red-600 text-white' : 'text-gray-400' }}">📅 JADWAL</a>
                <a href="{{ route('search', ['type' => 'Movie']) }}" class="flex items-center gap-3 px-4 py-3 rounded-xl text-sm font-bold text-gray-400">🎬 MOVIE</a>
                
                @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                <div class="pt-4 border-t border-white/5 mt-4">
                    <div class="flex items-center justify-between px-4 py-4 bg-white/5 rounded-xl">
                        <span class="text-[10px] font-bold uppercase text-gray-400">✨ Efek Visual</span>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="holidayToggleMobile" class="sr-only peer" onchange="toggleHolidayEffect()">
                            <div class="w-11 h-6 bg-gray-700 rounded-full peer-checked:bg-red-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:after:translate-x-full"></div>
                        </label>
                    </div>
                </div>
                @endif
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pt-2 pb-10 md:pt-4 min-h-[60vh]">
        @yield('content')
    </main>

    <footer class="bg-gradient-to-t from-black via-[#0a0c10] to-[#0f1115] border-t border-white/5 py-12 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <div class="flex flex-col items-center gap-4 mb-8">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 bg-red-600 rounded-lg flex items-center justify-center text-white font-bold">N</div>
                    <span class="text-xl font-black text-white font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                </div>
                <p class="text-gray-500 text-xs sm:text-sm max-w-md">Platform streaming anime subtitle Indonesia terlengkap dan terupdate setiap hari.</p>
            </div>
            <div class="border-t border-white/5 pt-8">
                <p class="text-gray-600 text-[10px] uppercase tracking-widest">© 2025 nipnime — Made for the community</p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileBtn = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const burgerIcon = document.getElementById('burgerIcon');
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            const mobileSearchBar = document.getElementById('mobileSearchBar');

            // Toggle Profile
            if (profileBtn) {
                profileBtn.onclick = (e) => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('opacity-0');
                    profileMenu.classList.toggle('invisible');
                };
                window.onclick = () => profileMenu.classList.add('opacity-0', 'invisible');
            }

            // Toggle Menu Mobile (Slide Down Mulus)
            if (mobileMenuBtn) {
                mobileMenuBtn.onclick = () => {
                    const isClosed = mobileMenu.classList.contains('max-h-0');
                    if (isClosed) {
                        mobileMenu.classList.remove('max-h-0', 'opacity-0', 'pointer-events-none', '-translate-y-4');
                        mobileMenu.classList.add('max-h-[500px]', 'opacity-100', 'pointer-events-auto', 'translate-y-0');
                        burgerIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
                    } else {
                        mobileMenu.classList.add('max-h-0', 'opacity-0', 'pointer-events-none', '-translate-y-4');
                        mobileMenu.classList.remove('max-h-[500px]', 'opacity-100', 'pointer-events-auto', 'translate-y-0');
                        burgerIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>';
                    }
                    if (mobileSearchBar) mobileSearchBar.classList.add('hidden');
                };
            }

            // Toggle Search Mobile
            if (mobileSearchBtn) {
                mobileSearchBtn.onclick = () => {
                    mobileSearchBar.classList.toggle('hidden');
                    if (!mobileSearchBar.classList.contains('hidden')) {
                        mobileSearchBar.querySelector('input').focus();
                        // Tutup menu jika search dibuka
                        mobileMenu.classList.add('max-h-0', 'opacity-0', 'pointer-events-none', '-translate-y-4');
                    }
                };
            }
        });
    </script>

    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
        <div id="holiday-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 40;"></div>
        @if($holidaySettings['christmas']) <script src="https://unpkg.com/magic-snowflakes/dist/snowflakes.min.js"></script> @endif
        @if($holidaySettings['new_year']) <script src="https://unpkg.com/fireworks-js@2.x/dist/index.umd.js"></script> @endif
        <script>
            function startEffect() {
                const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
                if(document.getElementById('holidayToggle')) document.getElementById('holidayToggle').checked = !isDisabled;
                if(document.getElementById('holidayToggleMobile')) document.getElementById('holidayToggleMobile').checked = !isDisabled;
                if (isDisabled) return;
                const container = document.getElementById('holiday-container');
                @if($holidaySettings['christmas']) new Snowflakes({ color: '#ffffff', container: container });
                @elseif($holidaySettings['new_year']) new Fireworks.default(container, { intensity: 15 }).start(); @endif
            }
            function toggleHolidayEffect() {
                localStorage.setItem('nipnime_effects_disabled', localStorage.getItem('nipnime_effects_disabled') === 'true' ? 'false' : 'true');
                location.reload(); 
            }
            document.addEventListener('DOMContentLoaded', startEffect);
        </script>
    @endif

    <style>
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        main { animation: fadeInUp 0.4s ease-out forwards; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: #0f1115; }
        ::-webkit-scrollbar-thumb { background: #dc2626; border-radius: 10px; }
        /* Transisi Mobile Menu */
        #mobileMenu { transition: max-height 0.5s ease, opacity 0.5s ease, transform 0.5s ease; }
    </style>
</body>
</html>