<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth" data-theme="dark">
<head>
    @php
        $pageTitle = trim($__env->yieldContent('title'));
        $fullTitle = $pageTitle ? $pageTitle . ' - nipnime' : 'nipnime';
        $metaDescription = trim($__env->yieldContent('meta_description', 'Nonton anime subtitle Indonesia dengan koleksi episode terbaru setiap hari di nipnime.'));
        $canonicalUrl = trim($__env->yieldContent('canonical', url()->current()));
    @endphp

    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $fullTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">

    <link rel="canonical" href="{{ $canonicalUrl }}">
    @hasSection('prev_url')
        <link rel="prev" href="@yield('prev_url')">
    @endif
    @hasSection('next_url')
        <link rel="next" href="@yield('next_url')">
    @endif

    <meta property="og:site_name" content="nipnime">
    <meta property="og:title" content="{{ trim($__env->yieldContent('og_title', $fullTitle)) }}">
    <meta property="og:description" content="{{ $metaDescription }}">
    <meta property="og:url" content="{{ $canonicalUrl }}">
    <meta property="og:type" content="@yield('og_type', 'website')">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ trim($__env->yieldContent('twitter_title', $fullTitle)) }}">
    <meta name="twitter:description" content="{{ $metaDescription }}">
    @hasSection('twitter_image')
        <meta name="twitter:image" content="@yield('twitter_image')">
    @elseif(View::hasSection('og_image'))
        <meta name="twitter:image" content="@yield('og_image')">
    @endif
    
    <link rel="icon" type="image/png" href="{{ asset('images/logo.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('images/logo.png') }}">

    <!-- Google AdSense -->
    <script async src="https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js?client=ca-pub-3820756796736021"
         crossorigin="anonymous"></script>

    <script>
        (() => {
            const stored = localStorage.getItem('nipnime_theme');
            const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
            const theme = stored || (prefersDark ? 'dark' : 'light');
            const root = document.documentElement;
            root.dataset.theme = theme;
            root.classList.toggle('theme-dark', theme === 'dark');
            root.classList.toggle('theme-light', theme === 'light');
        })();
    </script>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Montserrat:wght@800;900&display=swap" rel="stylesheet">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('head')
    @stack('structured-data')

    <!-- Google Analytics (Replace YOUR_GA_ID with your actual ID) -->
    @if(config('app.ga_measurement_id'))
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ config('app.ga_measurement_id') }}"></script>
    <script>
      window.dataLayer = window.dataLayer || [];
      function gtag(){dataLayer.push(arguments);}
      gtag('js', new Date());
      gtag('config', '{{ config('app.ga_measurement_id') }}', {
        'page_path': window.location.pathname,
        'send_page_view': true
      });
    </script>
    @endif

    <!-- Additional SEO Headers -->
    <meta name="robots" content="index, follow, max-snippet:-1, max-image-preview:large, max-video-preview:-1">
    <link rel="alternate" hreflang="id" href="{{ url()->current() }}">
    <meta property="og:locale" content="id_ID">
    
    <!-- Ad Styles -->
    <style>
        .ad-container { text-align: center; overflow: hidden; }
        .ad-unit[data-hide-mobile] { display: none; }
        @media (min-width: 768px) {
            .ad-unit[data-hide-mobile] { display: block; }
            .ad-unit[data-hide-desktop] { display: none; }
        }
    </style>
</head>
<body class="antialiased theme-body font-['Inter']">
    
    <!-- Header Top Ad -->
    <x-ad-slot position="header_top" class="py-2 bg-[#0a0c0f]" />
    
    <nav class="theme-surface backdrop-blur-xl border-b theme-border sticky top-0 z-50 shadow-xl shadow-black/20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16 sm:h-20">
                
                <div class="flex items-center gap-4 lg:gap-12">
                    
                    <a href="{{ route('home') }}" class="group flex items-center gap-2 transition-transform hover:scale-105">
                        <img src="{{ asset('images/logo.png') }}" 
                             alt="NipNime Logo" 
                             class="h-10 sm:h-12 w-auto object-contain drop-shadow-[0_0_15px_rgba(220,38,38,0.6)] transition-all duration-300 group-hover:drop-shadow-[0_0_20px_rgba(220,38,38,0.9)]">
                        
                        </a>
                    
                    <div class="hidden lg:flex items-center space-x-2 text-sm font-bold uppercase tracking-widest">
                        <a href="{{ route('home') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('home') ? 'text-red-500 bg-white/10' : '' }}">
                            🏠 {{ __('app.nav.home') }}
                        </a>
                        <a href="{{ route('search') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') && request('type') !== 'Movie' ? 'text-red-500 bg-white/10' : '' }}">
                            📺 {{ __('app.nav.anime_list') }}
                        </a>
                        <a href="{{ route('schedule') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500 bg-white/10' : '' }}">
                            📅 {{ __('app.nav.schedule') }}
                        </a>
                        <a href="{{ route('search', ['type' => 'Movie']) }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('search') && request('type') === 'Movie' ? 'text-red-500 bg-white/10' : '' }}">
                            🎬 {{ __('app.nav.movie') }}
                        </a>
                        @auth
                        <a href="{{ route('request.index') }}" class="px-4 py-2 rounded-lg hover:bg-white/10 hover:text-red-500 transition {{ request()->routeIs('request.*') ? 'text-red-500 bg-white/10' : '' }}">
                            📝 {{ __('app.nav.request') }}
                        </a>
                        @endauth
                    </div>
                </div>

                <div class="flex items-center gap-2 sm:gap-4 lg:gap-6">
                    
                    <form action="{{ route('search') }}" method="GET" class="hidden lg:block relative group">
                        <input type="text" name="search" id="navSearchInput" placeholder="{{ __('app.nav.search') }}" 
                               class="w-64 xl:w-72 theme-input border-2 theme-border rounded-full px-5 py-2.5 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500"
                               autocomplete="off">
                        <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                            </svg>
                        </button>
                        
                        <!-- Navbar autocomplete dropdown -->
                        <div id="navSearchSuggestions" class="absolute top-full left-0 mt-2 w-96 bg-[#1a1d24] border-2 border-white/10 rounded-xl shadow-2xl z-50 hidden max-h-80 overflow-y-auto">
                            <!-- Suggestions akan diisi via JavaScript -->
                        </div>
                    </form>

                    <button id="mobileSearchBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                    </button>

                    <div class="flex items-center gap-2 sm:gap-4">
                        <!-- Settings Dropdown (Language + Theme + Auth) -->
                        <div class="relative" id="settingsDropdown">
                            <button id="settingsButton" class="flex items-center gap-2 px-3 py-2 rounded-lg border theme-border theme-elevated hover:scale-105 transition-all text-sm font-bold">
                                @php $currentLocale = app()->getLocale(); @endphp
                                <span>{{ config('app.available_locales')[$currentLocale]['flag'] }}</span>
                                <span id="settingsThemeIcon">🌙</span>
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            
                            <div id="settingsMenu" class="absolute right-0 mt-2 w-48 theme-card rounded-xl shadow-xl opacity-0 invisible transition-all duration-300 border theme-border z-50">
                                @guest
                                <!-- Auth Section (Guest) -->
                                <a href="{{ route('auth.login') }}" class="block px-4 py-3 hover:bg-white/5 text-sm font-bold transition text-gray-300 hover:text-red-500">
                                    🔐 {{ __('app.common.login') }}
                                </a>
                                <a href="{{ route('auth.register') }}" class="block px-4 py-3 hover:bg-white/5 text-sm font-bold transition text-red-500 hover:text-red-400">
                                    ✓ {{ __('app.common.register') }}
                                </a>
                                <div class="border-t border-white/10"></div>
                                @endguest
                                
                                <!-- Language Section -->
                                <div class="px-3 py-2">
                                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">{{ __('app.nav.language') }}</p>
                                </div>
                                @foreach(config('app.available_locales') as $code => $lang)
                                    <a href="{{ route('language.switch', $code) }}" 
                                       class="block px-4 py-2 hover:bg-white/5 text-sm font-bold transition {{ $code === $currentLocale ? 'text-red-500 bg-white/5' : 'text-gray-300 hover:text-red-500' }}">
                                        <span>{{ $lang['flag'] }}</span> {{ $lang['name'] }}
                                    </a>
                                @endforeach
                                
                                <!-- Theme Section -->
                                <div class="px-3 py-2 border-t border-white/10">
                                    <p class="text-xs text-gray-500 font-bold uppercase tracking-wider">{{ __('app.nav.theme') }}</p>
                                </div>
                                <button id="themeToggle" class="w-full text-left px-4 py-2 hover:bg-white/5 text-sm font-bold transition text-gray-300 hover:text-red-500 flex items-center gap-2">
                                    <span id="themeToggleIcon">🌙</span>
                                    <span id="themeToggleLabel">{{ __('app.common.dark') }}</span>
                                </button>
                            </div>
                        </div>

                        @auth
                            <div class="relative" id="profileDropdown">
                                <button id="profileButton" class="w-9 h-9 sm:w-10 sm:h-10 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white text-sm font-black hover:shadow-lg hover:shadow-red-600/40 transition-all uppercase border-2 border-red-600/50">
                                    @if(Auth::user()->avatar)
                                        <img src="{{ asset('storage/' . Auth::user()->avatar) }}" 
                                             alt="{{ Auth::user()->name }}"
                                             class="w-full h-full object-cover">
                                    @else
                                        {{ substr(Auth::user()->name, 0, 1) }}
                                    @endif
                                </button>
                                
                                <div id="profileMenu" class="absolute right-0 mt-2 w-48 theme-card rounded-xl shadow-xl opacity-0 invisible transition-all duration-300 border theme-border z-50">
                                    <div class="p-3 border-b border-white/10 flex items-center gap-3">
                                        <div class="w-10 h-10 rounded-full overflow-hidden bg-gradient-to-br from-red-600 to-red-700 flex items-center justify-center text-white font-black flex-shrink-0">
                                            @if(Auth::user()->avatar)
                                                <img src="{{ asset('storage/' . Auth::user()->avatar) }}" alt="{{ Auth::user()->name }}" class="w-full h-full object-cover">
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
                                        👤 {{ strtoupper(__('app.common.profile')) }}
                                    </a>
                                    <form action="{{ route('auth.logout') }}" method="POST">
                                        @csrf
                                        <button type="submit" class="w-full text-left px-4 py-3 text-red-500 hover:bg-white/5 text-sm font-bold transition">
                                            🚪 {{ strtoupper(__('app.common.logout')) }}
                                        </button>
                                    </form>
                                </div>
                            </div>
                        @endauth

                        @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                        <div class="hidden lg:flex items-center border-l border-white/10 pl-4 ml-2">
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" id="holidayToggle" class="sr-only peer" onchange="toggleHolidayEffect()">
                                <div class="w-8 h-4 bg-gray-700 rounded-full peer-checked:bg-red-600 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-3 after:w-3 after:transition-all peer-checked:after:translate-x-4"></div>
                            </label>
                        </div>
                        @endif

                        <button id="mobileMenuBtn" class="lg:hidden p-2 text-gray-400 hover:text-white transition">
                            <svg id="burgerIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                            </svg>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <div id="mobileSearchBar" class="hidden lg:hidden absolute top-full left-0 w-full px-4 pb-4 theme-surface shadow-lg z-40">
            <form action="{{ route('search') }}" method="GET" class="relative">
                <input type="text" name="search" placeholder="Cari anime..." 
                       class="w-full theme-input border-2 theme-border rounded-full px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600">
                <button type="submit" class="absolute right-4 top-1/2 transform -translate-y-1/2 text-gray-500 hover:text-red-500 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </button>
            </form>
        </div>

        <div id="mobileMenu" class="absolute top-full left-0 w-full theme-surface border-t theme-border shadow-2xl z-40 transition-all duration-500 ease-in-out max-h-0 opacity-0 overflow-hidden pointer-events-none lg:hidden">
            <div class="px-4 py-4 space-y-2">
                <a href="{{ route('home') }}" style="transition-delay: 0ms;" 
                   class="mobile-item block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-500 opacity-0 -translate-y-4 {{ request()->routeIs('home') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }}">
                    🏠 {{ __('app.nav.home') }}
                </a>
                <a href="{{ route('search') }}" style="transition-delay: 100ms;"
                   class="mobile-item block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-500 opacity-0 -translate-y-4 {{ request()->routeIs('search') && request('type') !== 'Movie' ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }}">
                    📺 {{ __('app.nav.anime_list') }}
                </a>
                <a href="{{ route('schedule') }}" style="transition-delay: 200ms;"
                   class="mobile-item block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-500 opacity-0 -translate-y-4 {{ request()->routeIs('schedule') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }}">
                    📅 {{ __('app.nav.schedule') }}
                </a>
                <a href="{{ route('search', ['type' => 'Movie']) }}" style="transition-delay: 300ms;"
                   class="mobile-item block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-500 opacity-0 -translate-y-4 {{ request()->routeIs('search') && request('type') === 'Movie' ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }}">
                    🎬 {{ __('app.nav.movie') }}
                </a>
                @auth
                <a href="{{ route('request.index') }}" style="transition-delay: 400ms;"
                   class="mobile-item block px-4 py-3 rounded-xl text-sm font-bold uppercase tracking-wider transition-all duration-500 opacity-0 -translate-y-4 {{ request()->routeIs('request.*') ? 'bg-red-600 text-white' : 'text-gray-300 hover:bg-white/10' }}">
                    📝 {{ __('app.nav.request') }}
                </a>
                @endauth
                
                @if(isset($holidaySettings) && (($holidaySettings['christmas'] ?? false) || ($holidaySettings['new_year'] ?? false)))
                <div style="transition-delay: 500ms;" class="mobile-item pt-4 border-t border-white/10 mt-2 transition-all duration-500 opacity-0 -translate-y-4">
                    <div class="flex items-center justify-between px-4 py-2 bg-white/5 rounded-xl">
                        <span class="text-xs font-bold uppercase text-gray-400">✨ Efek Visual</span>
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

    <!-- Header Bottom Ad -->
    <x-ad-slot position="header_bottom" class="py-4 bg-[#0a0c0f] border-b border-white/5" />

    <main class="relative z-0">@yield('content')</main>

    <!-- Footer Ad -->
    <x-ad-slot position="footer" class="py-6 bg-[#0a0c0f] border-t border-white/10" />

    <footer class="gradient-bg border-t theme-border py-10 sm:py-16 mt-10 sm:mt-20">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-4 gap-6 sm:gap-10 mb-8 sm:mb-12">
                <div class="col-span-2 sm:col-span-2 md:col-span-1">
                    <div class="flex items-center gap-2 mb-4">
                        <img src="{{ asset('images/logo.png') }}" 
                             alt="NipNime Logo" 
                             class="w-auto h-12 sm:h-16 object-contain drop-shadow-[0_0_8px_rgba(220,38,38,0.4)]">
                        <span class="text-xl sm:text-2xl font-black text-white font-['Montserrat'] uppercase"><span class="text-red-600">nip</span>nime</span>
                    </div>
                    <p class="text-gray-400 text-xs sm:text-sm leading-relaxed">{{ __('app.footer.description') }}</p>
                </div>

                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-3 sm:mb-4 text-sm sm:text-base">{{ __('app.footer.navigation') }}</h4>
                    <ul class="space-y-1.5 sm:space-y-2 text-gray-400 text-xs sm:text-sm">
                        <li><a href="{{ route('home') }}" class="hover:text-red-500 transition">{{ __('app.nav.home') }}</a></li>
                        <li><a href="{{ route('search') }}" class="hover:text-red-500 transition {{ request()->routeIs('search') && request('type') !== 'Movie' ? 'text-red-500' : '' }}">{{ __('app.nav.anime_list') }}</a></li>
                        <li><a href="{{ route('schedule') }}" class="hover:text-red-500 transition {{ request()->routeIs('schedule') ? 'text-red-500' : '' }}">{{ __('app.nav.schedule') }}</a></li>
                        <li><a href="{{ route('search', ['type' => 'Movie']) }}" class="hover:text-red-500 transition {{ request()->routeIs('search') && request('type') === 'Movie' ? 'text-red-500' : '' }}">{{ __('app.nav.movie') }}</a></li>
                    </ul>
                </div>

                <div>
                    <h4 class="text-white font-black uppercase tracking-wider mb-3 sm:mb-4 text-sm sm:text-base">{{ __('app.footer.legal') }}</h4>
                    <ul class="space-y-1.5 sm:space-y-2 text-gray-400 text-xs sm:text-sm">
                        <li><a href="{{ route('dmca') }}" class="hover:text-red-500 transition">{{ __('app.footer.dmca') }}</a></li>
                        <li><a href="{{ route('privacy') }}" class="hover:text-red-500 transition">{{ __('app.footer.privacy') }}</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-red-500 transition">{{ __('app.footer.terms') }}</a></li>
                        <li><a href="{{ route('contact') }}" class="hover:text-red-500 transition">{{ __('app.footer.contact') }}</a></li>
                    </ul>
                </div>

                <div class="col-span-2 sm:col-span-1">
                    <h4 class="text-white font-black uppercase tracking-wider mb-3 sm:mb-4 text-sm sm:text-base">{{ __('app.footer.follow_us') }}</h4>
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

            <div class="border-t border-white/10 pt-6 sm:pt-8 text-center">
                <p class="text-gray-500 text-xs sm:text-sm">
                    © 2025 nipnime. {{ __('app.footer.rights') }} | {{ __('app.footer.made_with') }} <span class="text-red-600">❤</span> {{ __('app.footer.for_fans') }}
                </p>
            </div>
        </div>
    </footer>

    @livewireScripts
    @livewire('notifications')

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const profileButton = document.getElementById('profileButton');
            const profileMenu = document.getElementById('profileMenu');
            const profileDropdown = document.getElementById('profileDropdown');
            const mobileMenuBtn = document.getElementById('mobileMenuBtn');
            const mobileMenu = document.getElementById('mobileMenu');
            const burgerIcon = document.getElementById('burgerIcon');
            const mobileSearchBtn = document.getElementById('mobileSearchBtn');
            const mobileSearchBar = document.getElementById('mobileSearchBar');
            const themeToggle = document.getElementById('themeToggle');
            const themeToggleIcon = document.getElementById('themeToggleIcon');
            const themeToggleLabel = document.getElementById('themeToggleLabel');

            const updateThemeVisual = (theme) => {
                if (themeToggleIcon) themeToggleIcon.textContent = theme === 'dark' ? '🌙' : '☀️';
                if (themeToggleLabel) themeToggleLabel.textContent = theme === 'dark' ? 'Gelap' : 'Terang';
                if (themeToggle) themeToggle.setAttribute('aria-pressed', theme === 'light' ? 'true' : 'false');
            };

            const applyTheme = (theme) => {
                const root = document.documentElement;
                root.dataset.theme = theme;
                root.classList.toggle('theme-dark', theme === 'dark');
                root.classList.toggle('theme-light', theme === 'light');
                localStorage.setItem('nipnime_theme', theme);
                updateThemeVisual(theme);
            };

            const initTheme = () => {
                const stored = localStorage.getItem('nipnime_theme');
                const prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                const theme = stored || (prefersDark ? 'dark' : 'light');
                applyTheme(theme);
            };

            initTheme();
            if (themeToggle) {
                themeToggle.addEventListener('click', () => {
                    const nextTheme = document.documentElement.dataset.theme === 'dark' ? 'light' : 'dark';
                    applyTheme(nextTheme);
                });
            }

            // Profile dropdown logic
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
            }

            // Settings dropdown logic (Language + Theme)
            const settingsButton = document.getElementById('settingsButton');
            const settingsMenu = document.getElementById('settingsMenu');
            const settingsDropdown = document.getElementById('settingsDropdown');

            if (settingsButton && settingsMenu) {
                settingsButton.addEventListener('click', function(e) {
                    e.stopPropagation();
                    settingsMenu.classList.toggle('opacity-0');
                    settingsMenu.classList.toggle('invisible');
                });
                document.addEventListener('click', function(e) {
                    if (settingsDropdown && !settingsDropdown.contains(e.target)) {
                        settingsMenu.classList.add('opacity-0', 'invisible');
                    }
                });
            }

            // Logic Animasi Menu Mobile (Disempurnakan dengan Scroll)
            if (mobileMenuBtn && mobileMenu) {
                mobileMenuBtn.addEventListener('click', function() {
                    const isClosed = mobileMenu.classList.contains('max-h-0');
                    const items = mobileMenu.querySelectorAll('.mobile-item');

                    if (isClosed) {
                        // 1. Buka Container dengan Tinggi Maksimal yang Responsif (85vh)
                        mobileMenu.classList.remove('max-h-0', 'opacity-0', 'pointer-events-none');
                        mobileMenu.classList.add('max-h-[85vh]', 'opacity-100', 'pointer-events-auto', 'overflow-y-auto'); // Tambah overflow-y-auto
                        burgerIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>';
                        
                        // 2. Animasi Item muncul satu per satu
                        items.forEach(item => {
                            item.classList.remove('opacity-0', '-translate-y-4');
                            item.classList.add('opacity-100', 'translate-y-0');
                        });

                        // Tutup search bar jika terbuka
                        if (mobileSearchBar) mobileSearchBar.classList.add('hidden');
                    } else {
                        // 1. Reset Item (Hilang dulu)
                        items.forEach(item => {
                            item.classList.add('opacity-0', '-translate-y-4');
                            item.classList.remove('opacity-100', 'translate-y-0');
                        });

                        // 2. Tutup Container
                        mobileMenu.classList.add('max-h-0', 'opacity-0', 'pointer-events-none');
                        mobileMenu.classList.remove('max-h-[85vh]', 'opacity-100', 'pointer-events-auto', 'overflow-y-auto'); // Hapus overflow-y-auto
                        burgerIcon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>';
                    }
                });
            }

            // Search Toggle Logic
            if (mobileSearchBtn && mobileSearchBar) {
                mobileSearchBtn.addEventListener('click', function() {
                    mobileSearchBar.classList.toggle('hidden');
                    if (!mobileSearchBar.classList.contains('hidden')) {
                        mobileSearchBar.querySelector('input').focus();
                        // Tutup menu jika search dibuka
                        if (mobileMenu && !mobileMenu.classList.contains('max-h-0')) {
                            mobileMenuBtn.click(); // Trigger click untuk menutup menu dengan animasi
                        }
                    }
                });
            }

            // Navbar autocomplete for search
            const navSearchInput = document.getElementById('navSearchInput');
            const navSearchSuggestions = document.getElementById('navSearchSuggestions');
            
            if (navSearchInput && navSearchSuggestions) {
                let debounceTimer = null;

                navSearchInput.addEventListener('input', function() {
                    clearTimeout(debounceTimer);
                    const query = this.value.trim();
                    
                    if (query.length < 2) {
                        navSearchSuggestions.classList.add('hidden');
                        return;
                    }

                    debounceTimer = setTimeout(() => {
                        fetch(`{{ route('search.suggestions') }}?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(data => {
                                navSearchSuggestions.innerHTML = '';
                                
                                if (data.suggestions.length === 0) {
                                    const empty = document.createElement('div');
                                    empty.className = 'p-3 text-center text-gray-400 text-xs';
                                    empty.textContent = 'Tidak ada hasil';
                                    navSearchSuggestions.appendChild(empty);
                                } else {
                                    data.suggestions.forEach(anime => {
                                        const item = document.createElement('a');
                                        item.href = anime.url;
                                        item.className = 'flex items-center gap-2 p-2 hover:bg-white/5 border-b border-white/10 last:border-b-0 transition-all';
                                        item.innerHTML = `
                                            <img src="${anime.poster_image}" alt="${escapeHtml(anime.title)}" class="w-10 h-14 rounded object-cover">
                                            <div class="flex-1 min-w-0">
                                                <p class="text-white text-xs font-bold truncate">${escapeHtml(anime.title)}</p>
                                                <p class="text-gray-500 text-[10px]">${anime.release_year} • ${anime.type}</p>
                                            </div>
                                        `;
                                        navSearchSuggestions.appendChild(item);
                                    });
                                }
                                navSearchSuggestions.classList.remove('hidden');
                            })
                            .catch(error => console.error('Error:', error));
                    }, 300);
                });

                navSearchInput.addEventListener('focus', function() {
                    if (this.value.trim().length >= 2) {
                        navSearchSuggestions.classList.remove('hidden');
                    }
                });

                document.addEventListener('click', function(e) {
                    if (e.target !== navSearchInput && !navSearchSuggestions.contains(e.target)) {
                        navSearchSuggestions.classList.add('hidden');
                    }
                });

                function escapeHtml(text) {
                    const map = {
                        '&': '&amp;',
                        '<': '&lt;',
                        '>': '&gt;',
                        '"': '&quot;',
                        "'": '&#039;'
                    };
                    return text.replace(/[&<>"']/g, m => map[m]);
                }
            }
        });
    </script>

    @stack('scripts')

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
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        main { animation: fadeInUp 0.5s ease-out; }
        html { scroll-behavior: smooth; }
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: var(--scrollbar-track); }
        ::-webkit-scrollbar-thumb { background: var(--scrollbar-thumb); border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: var(--scrollbar-thumb-hover); }
    </style>
</body>
</html>