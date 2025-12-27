<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title') - nipnime</title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="antialiased bg-[#0f1115] text-gray-300 font-['Inter']">
    
    <nav class="bg-[#0f1115]/90 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50 shadow-xl shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <div class="flex items-center gap-4 lg:gap-10">
                    <a href="{{ route('home') }}" class="group flex items-center gap-2 sm:gap-3 hover:scale-105 transition-transform">
                        <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg shadow-red-600/30 group-hover:shadow-red-600/50 transition-all">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-xl sm:text-3xl font-black text-white tracking-tighter font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </a>
                    
                    <div class="hidden lg:flex items-center space-x-1 text-sm font-bold uppercase tracking-widest">
                        <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('home') ? 'text-red-500 bg-white/10' : '' }}">
                            🏠 Home
                        </a>
                        <a href="{{ route('search') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') ? 'text-red-500 bg-white/10' : '' }}">
                            📺 Daftar Anime
                        </a>
                        <a href="{{ route('schedule') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500 bg-white/10' : '' }}">
                            📅 Jadwal
                        </a>
                        <a href="{{ route('search', ['type' => 'Movie']) }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition">
                            🎬 Movie
                        </a>
                        @auth
                        <a href="{{ route('request.index') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('request.*') ? 'text-red-500 bg-white/10' : '' }}">
                            📝 Request
                        </a>
                        @endauth
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4 lg:gap-6">
                    <form action="{{ route('search') }}" method="GET" class="hidden lg:block relative group">
                        <input type="text" name="search" placeholder="Cari anime..." 
                               class="w-48 xl:w-72 bg-[#1a1d24] border-2 border-white/10 text-white rounded-full px-5 py-2.5 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500">
                        <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </form>

                    <div class="flex items-center gap-2 sm:gap-4">
                        @auth
                            <div class="flex items-center gap-2 sm:gap-3">
                                <span class="text-sm font-bold text-gray-300 hidden md:inline">{{ Auth::user()->name }}</span>
                                <div class="relative" id="profileDropdown">
                                    <button id="profileButton" class="w-9 h-9 sm:w-11 sm:h-11 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white text-sm sm:text-base font-black hover:shadow-lg hover:shadow-red-600/40 transition-all uppercase border-2 border-red-600/50">
                                        @if(Auth::user()->avatar)
                                            <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                                                 alt="{{ Auth::user()->name }}"
                                                 class="w-full h-full object-cover">
                                        @else
                                            {{ substr(Auth::user()->name, 0, 1) }}
                                        @endif
                                    </button>
                                    <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-[#1a1d24] rounded-xl shadow-xl opacity-0 invisible transition-all duration-300 border border-white/10 z-50">
                                        <div class="p-3 border-b border-white/10 flex items-center gap-3">
                                            <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white font-black flex-shrink-0">
                                                @if(Auth::user()->avatar)
                                                    <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                                                         alt="{{ Auth::user()->name }}"
                                                         class="w-full h-full object-cover">
                                                @else
                                                    {{ substr(Auth::user()->name, 0, 1) }}
                                                @endif
                                            </div>
                                            <div class="overflow-hidden">
                                                <p class="text-sm font-bold text-white truncate">{{ Auth::user()->name }}</p>
                                                <p class="text-xs text-gray-500 truncate">{{ Auth::user()->email }}</p>
                                            </div>
                                        </div>
                                        <a href="{{ route('profile.show') }}" class="block w-full text-left px-4 py-3 hover:bg-white/5 text-sm font-bold transition text-gray-300 hover:text-red-500">
                                            👤 PROFIL
                                        </a>
                                        <form action="{{ route('auth.logout') }}" method="POST">
                                            @csrf
                                            <button type="submit" class="w-full text-left px-4 py-3 text-red-500 hover:bg-white/5 text-sm font-bold transition">
                                                🚪 LOGOUT
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <a href="{{ route('auth.login') }}" class="text-xs sm:text-sm font-bold hover:text-red-500 transition px-2 sm:px-4 py-2 rounded-lg hover:bg-white/10 uppercase tracking-wider">
                                🔐 <span class="hidden sm:inline">Masuk</span>
                            </a>
                            <a href="{{ route('auth.register') }}" class="text-xs sm:text-sm font-bold px-3 sm:px-4 py-2 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white rounded-lg transition-all shadow-lg shadow-red-600/30 uppercase tracking-wider">
                                <span class="hidden sm:inline">✓ Daftar</span>
                                <span class="sm:hidden">Daftar</span>
                            </a>
                        @endauth

                        {{-- SAKLAR EFEK (DESKTOP) --}}
                        @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                        <div class="hidden sm:flex items-center gap-2 border-l border-white/10 pl-4 ml-2">
                            <label class="relative inline-flex items-center cursor-pointer select-none">
                                <input type="checkbox" id="holidayToggle" class="sr-only peer" onchange="toggleHolidayEffect()">
                                <div class="w-10 h-5 bg-gray-700 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-red-600 shadow-inner shadow-black/50 transition-colors"></div>
                                <span class="ml-2 text-[10px] font-black text-gray-400 uppercase tracking-widest peer-checked:text-red-500 transition-colors">Efek</span>
                            </label>
                        </div>
                        @endif

                        <button id="mobileMenuBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="mobileMenu" class="hidden lg:hidden border-t border-white/10 bg-[#0f1115]">
            <div class="px-4 py-4 space-y-2">
                <a href="{{ route('home') }}" class="block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider {{ request()->routeIs('home') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} transition">
                    🏠 Home
                </a>
                <a href="{{ route('search') }}" class="block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider {{ request()->routeIs('search') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} transition">
                    📺 Daftar Anime
                </a>
                <a href="{{ route('schedule') }}" class="block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider {{ request()->routeIs('schedule') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} transition">
                    📅 Jadwal
                </a>

                {{-- SAKLAR EFEK (MOBILE) --}}
                @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                <div class="flex items-center justify-between px-4 py-3 rounded-xl text-gray-300 bg-white/5 border border-white/5">
                    <span class="text-sm font-bold uppercase tracking-wider">✨ Efek Visual</span>
                    <label class="relative inline-flex items-center cursor-pointer select-none">
                        <input type="checkbox" id="holidayToggleMobile" class="sr-only peer" onchange="toggleHolidayEffect()">
                        <div class="w-11 h-6 bg-gray-700 rounded-full peer peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-red-600 shadow-inner shadow-black/50 transition-colors"></div>
                    </label>
                </div>
                @endif
            </div>
        </div>
    </nav>

    <main>@yield('content')</main>

    <footer class="bg-gradient-to-t from-[#000000] via-[#0a0c10] to-[#1a1d24] border-t border-white/10 py-10 sm:py-16 mt-10 sm:mt-20">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p class="text-gray-500 text-xs sm:text-sm italic">
                © 2024 nipnime. All rights reserved. | Made with ❤️ for anime fans
            </p>
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

    <script>
        // Navbar Controls
        document.addEventListener('DOMContentLoaded', function() {
            const profileButton = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const profileDropdown = document.getElementById('profileDropdown');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');

            if (profileButton && profileMenu) {
                profileButton.addEventListener('click', e => {
                    e.stopPropagation();
                    profileMenu.classList.toggle('opacity-0');
                    profileMenu.classList.toggle('invisible');
                });
                document.addEventListener('click', e => {
                    if (profileDropdown && !profileDropdown.contains(e.target)) {
                        profileMenu.classList.add('opacity-0', 'invisible');
                    }
                });
            }

            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', () => mobileMenu.classList.toggle('hidden'));
            }
        });
    </script>

    {{-- KONTEN EFEK LIBURAN --}}
    @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
        <div id="holiday-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 99998;"></div>

        @if($holidaySettings['christmas'])
            <script src="https://unpkg.com/magic-snowflakes/dist/snowflakes.min.js"></script>
        @endif
        @if($holidaySettings['new_year'])
            <script src="https://unpkg.com/fireworks-js@2.x/dist/index.umd.js"></script>
        @endif

        <script>
            let holidayInstance = null;
            
            function startEffect() {
                const container = document.getElementById('holiday-container');
                if(!container) return;

                const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
                
                // Sync status saklar dengan localStorage
                const toggleDesktop = document.getElementById('holidayToggle');
                const toggleMobile = document.getElementById('holidayToggleMobile');
                if(toggleDesktop) toggleDesktop.checked = !isDisabled;
                if(toggleMobile) toggleMobile.checked = !isDisabled;

                if (isDisabled) return;

                @if($holidaySettings['christmas'])
                    holidayInstance = new Snowflakes({ color: '#ffffff', container: container });
                @elseif($holidaySettings['new_year'])
                    holidayInstance = new Fireworks.default(container, { intensity: 20, autoresize: true });
                    holidayInstance.start();
                @endif
            }

            function toggleHolidayEffect() {
                const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
                localStorage.setItem('nipnime_effects_disabled', isDisabled ? 'false' : 'true');
                location.reload(); 
            }

            document.addEventListener('DOMContentLoaded', startEffect);
        </script>
    @endif

    <style>
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #0f1115; }
        ::-webkit-scrollbar-thumb { background: #dc2626; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #b91c1c; }
    </style>
</body>
</html>