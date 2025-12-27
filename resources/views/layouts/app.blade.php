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
    
    <!-- Navbar -->
    <nav class="bg-[#0f1115]/90 backdrop-blur-xl border-b border-white/5 sticky top-0 z-50 shadow-xl shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                <!-- Logo & Brand -->
                <div class="flex items-center gap-4 lg:gap-10">
                    <a href="{{ route('home') }}" class="group flex items-center gap-2 sm:gap-3 hover:scale-105 transition-transform">
                        <div class="w-9 h-9 sm:w-11 sm:h-11 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg shadow-red-600/30 group-hover:shadow-red-600/50 transition-all">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-xl sm:text-3xl font-black text-white tracking-tighter font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </a>
                    
                    <!-- Navigation Links - Desktop -->
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

                <!-- Search & Auth -->
                <div class="flex items-center gap-2 sm:gap-4 lg:gap-6">
                    <!-- Search Bar - Desktop -->
                    <form action="{{ route('search') }}" method="GET" class="hidden lg:block relative group">
                        <input type="text" name="search" placeholder="Cari anime..." 
                               class="w-72 bg-[#1a1d24] border-2 border-white/10 text-white rounded-full px-5 py-2.5 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500">
                        <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                    </form>

                    <!-- Search Icon - Mobile -->
                    <button id="mobileSearchBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>

                    <!-- Auth Links -->
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
                                    <!-- Dropdown Menu -->
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

                        <!-- Mobile Menu Button -->
                        <button id="mobileMenuBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Mobile Search Bar -->
        <div id="mobileSearchBar" class="hidden lg:hidden px-4 pb-4">
            <form action="{{ route('search') }}" method="GET" class="relative">
                <input type="text" name="search" placeholder="Cari anime..." 
                       class="w-full bg-[#1a1d24] border-2 border-white/10 text-white rounded-full px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600">
                <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>
        </div>

        <!-- Mobile Menu -->
        <div id="mobileMenu" class="hidden lg:hidden border-t border-white/10">
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
                <a href="{{ route('search', ['type' => 'Movie']) }}" class="block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider text-gray-300 hover:bg-white/10 transition">
                    🎬 Movie
                </a>
                @auth
                <a href="{{ route('request.index') }}" class="block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider {{ request()->routeIs('request.*') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }} transition">
                    📝 Request
                </a>
                @endauth
            </div>
        </div>
    </nav>

    <!-- Dropdown & Mobile Menu Toggle Script -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileButton = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const profileDropdown = document.getElementById('profileDropdown');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            const mobileSearchBar = document.getElementById('mobileSearchBar');

            // Profile dropdown toggle
            if (profileButton && profileMenu) {
                profileButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    profileMenu.classList.toggle('opacity-0');
                    profileMenu.classList.toggle('invisible');
                });

                document.addEventListener('click', function(e) {
                    if (profileDropdown && !profileDropdown.contains(e.target)) {
                        profileMenu.classList.add('opacity-0', 'invisible');
                    }
                });

                const profileLink = profileMenu.querySelector('a');
                if (profileLink) {
                    profileLink.addEventListener('click', function() {
                        profileMenu.classList.add('opacity-0', 'invisible');
                    });
                }
            }

            // Mobile menu toggle
            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    mobileMenu.classList.toggle('hidden');
                    // Close search bar when opening menu
                    if (mobileSearchBar && !mobileMenu.classList.contains('hidden')) {
                        mobileSearchBar.classList.add('hidden');
                    }
                });
            }

            // Mobile search toggle
            if (mobileSearchBtn && mobileSearchBar) {
                mobileSearchBtn.addEventListener('click', function() {
                    mobileSearchBar.classList.toggle('hidden');
                    // Close menu when opening search
                    if (mobileMenu && !mobileSearchBar.classList.contains('hidden')) {
                        mobileMenu.classList.add('hidden');
                    }
                    // Focus on input
                    if (!mobileSearchBar.classList.contains('hidden')) {
                        mobileSearchBar.querySelector('input').focus();
                    }
                });
            }
        });
    </script>

    <!-- Main Content -->
    <main>@yield('content')</main>

    <!-- Footer -->
    <footer class="bg-gradient-to-t from-[#000000] via-[#0a0c10] to-[#1a1d24] border-t border-white/10 py-10 sm:py-16 mt-10 sm:mt-20">
        <div class="max-w-7xl mx-auto px-4">
            <!-- Footer Content -->
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-10 mb-8 sm:mb-12">
                <!-- Branding -->
                <div class="col-span-2 sm:col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <div class="w-8 h-8 sm:w-10 sm:h-10 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center">
                            <svg class="w-5 h-5 sm:w-6 sm:h-6 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                        </div>
                        <span class="text-xl sm:text-2xl font-black text-white font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </div>
                    <p class="text-gray-400 text-xs sm:text-sm leading-relaxed">Platform streaming anime terlengkap dengan subtitle Indonesia berkualitas tinggi.</p>
                </div>

                <!-- Quick Links -->
                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-3 sm:mb-4 text-sm sm:text-base">Navigasi</h4>
                    <ul class="space-y-1.5 sm:space-y-2 text-gray-400 text-xs sm:text-sm">
                        <li><a href="{{ route('home') }}" class="hover:text-red-500 transition">Home</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-red-500 transition">Daftar Anime</a></li>
                        <li><a href="{{ route('schedule') }}" class="hover:text-red-500 transition">Jadwal Tayang</a></li>
                        <li><a href="{{ route('search', ['type' => 'Movie']) }}" class="hover:text-red-500 transition">Movie</a></li>
                    </ul>
                </div>

                <!-- Legal -->
                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-3 sm:mb-4 text-sm sm:text-base">Legal</h4>
                    <ul class="space-y-1.5 sm:space-y-2 text-gray-400 text-xs sm:text-sm">
                        <li><a href="{{ route('dmca') }}" class="hover:text-red-500 transition">DMCA</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-red-500 transition">Privacy Policy</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-red-500 transition">Terms of Service</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-red-500 transition">Contact Us</a></li>
                    </ul>
                </div>

                <!-- Social -->
                <div class="col-span-2 sm:col-span-1">
                    <h4 class="text-white font-black uppercase tracking-wider mb-3 sm:mb-4 text-sm sm:text-base">Ikuti Kami</h4>
                    <div class="flex gap-2 sm:gap-3">
                        <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-white/10 hover:bg-red-600 rounded-lg flex items-center justify-center text-white transition-all">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-white/10 hover:bg-red-600 rounded-lg flex items-center justify-center text-white transition-all">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23 3a10.9 10.9 0 01-3.14 1.53 4.48 4.48 0 00-7.86 3v1A10.66 10.66 0 013 4s-4 9 5 13a11.64 11.64 0 01-7 2c9 5 20 0 20-11.5a4.5 4.5 0 00-.08-.83A7.72 7.72 0 0023 3z"/></svg>
                        </a>
                        <a href="#" class="w-9 h-9 sm:w-10 sm:h-10 bg-white/10 hover:bg-red-600 rounded-lg flex items-center justify-center text-white transition-all">
                            <svg class="w-4 h-4 sm:w-5 sm:h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zM12 0C8.741 0 8.333.014 7.053.072 2.695.272.273 2.69.073 7.052.014 8.333 0 8.741 0 12c0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98C8.333 23.986 8.741 24 12 24c3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98C15.668.014 15.259 0 12 0zm0 5.838a6.162 6.162 0 100 12.324 6.162 6.162 0 000-12.324zM12 16a4 4 0 110-8 4 4 0 010 8zm6.406-11.845a1.44 1.44 0 100 2.881 1.44 1.44 0 000-2.881z"/></svg>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Divider -->
            <div class="border-t border-white/10 pt-6 sm:pt-8">
                <p class="text-gray-500 text-center text-xs sm:text-sm">
                    © 2024 nipnime. All rights reserved. | Made with ❤️ for anime fans
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

    <style>
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        main {
            animation: fadeInUp 0.5s ease-out;
        }

        /* Smooth scroll behavior */
        html {
            scroll-behavior: smooth;
        }

        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #0f1115;
        }

        ::-webkit-scrollbar-thumb {
            background: #dc2626;
            border-radius: 4px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #b91c1c;
        }
    </style>
  {{-- BLOK KONTROL EFEK HOLIDAY (NATAL & TAHUN BARU) --}}
@if(isset($holidaySettings) && ($holidaySettings['christmas'] || $holidaySettings['new_year']))
    
    {{-- 1. Tombol Toggle untuk User --}}
    <div class="fixed bottom-5 left-5 z-[10000]">
        <button id="toggle-holiday-effect" onclick="toggleHolidayEffect()" class="flex items-center gap-2 px-4 py-2 bg-black/50 backdrop-blur-md border border-white/20 text-white text-[10px] sm:text-xs font-black rounded-full hover:bg-red-600 transition-all shadow-lg uppercase tracking-wider">
            <span id="toggle-icon">✨</span>
            <span id="toggle-text">Matikan Efek</span>
        </button>
    </div>

    {{-- 2. Container Efek --}}
    <div id="holiday-container" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 9999;"></div>

    {{-- 3. Import Library yang Dibutuhkan --}}
    @if($holidaySettings['christmas'])
        <script src="https://unpkg.com/magic-snowflakes/dist/snowflakes.min.js"></script>
    @endif
    @if($holidaySettings['new_year'])
        <script src="https://unpkg.com/fireworks-js@2.x/dist/index.umd.js"></script>
    @endif

    {{-- 4. Logika JavaScript --}}
    <script>
        let holidayInstance = null;
        const container = document.getElementById('holiday-container');
        const toggleText = document.getElementById('toggle-text');

        function startEffect() {
            const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
            
            if (isDisabled) {
                if(toggleText) toggleText.innerText = "Aktifkan Efek";
                return;
            }

            @if($holidaySettings['christmas'])
                holidayInstance = new Snowflakes({ color: '#ffffff', container: container });
            @elseif($holidaySettings['new_year'])
                holidayInstance = new Fireworks.default(container, { intensity: 30, autoresize: true });
                holidayInstance.start();
            @endif

            if(toggleText) toggleText.innerText = "Matikan Efek";
        }

        function toggleHolidayEffect() {
            const isDisabled = localStorage.getItem('nipnime_effects_disabled') === 'true';
            
            if (isDisabled) {
                localStorage.setItem('nipnime_effects_disabled', 'false');
                location.reload(); 
            } else {
                localStorage.setItem('nipnime_effects_disabled', 'true');
                if (holidayInstance) {
                    @if($holidaySettings['christmas'])
                        holidayInstance.destroy();
                    @else
                        holidayInstance.stop();
                    @endif
                }
                container.innerHTML = '';
                if(toggleText) toggleText.innerText = "Aktifkan Efek";
            }
        }

        document.addEventListener('DOMContentLoaded', startEffect);
    </script>
@endif
{{-- Akhir Blok Holiday --}}
</body>
</html>