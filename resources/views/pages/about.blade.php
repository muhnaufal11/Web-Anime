@extends('layouts.app')
@section('title', 'Tentang Kami - nipnime')
@section('meta_description', 'Tentang nipnime - Platform streaming anime sub Indonesia terlengkap dengan koleksi anime terbaru dan terpopuler.')

@section('content')
<div class="max-w-5xl mx-auto px-4 py-12">
    <!-- Hero Section -->
    <div class="text-center mb-16">
        <h1 class="text-5xl font-black text-white mb-6">
            Tentang <span class="text-red-500">nipnime</span>
        </h1>
        <p class="text-xl text-gray-400 max-w-2xl mx-auto">
            Platform streaming anime sub Indonesia yang menyediakan pengalaman menonton terbaik untuk para pecinta anime.
        </p>
    </div>

    <!-- Main Content -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-16">
        <!-- Mission Card -->
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-2xl p-8 border border-white/10 hover:border-red-600/50 transition-all">
            <div class="w-16 h-16 bg-red-600/20 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-3">Misi Kami</h3>
            <p class="text-gray-400">
                Menyediakan akses mudah dan nyaman untuk menonton anime favorit dengan subtitle Indonesia berkualitas tinggi.
            </p>
        </div>

        <!-- Vision Card -->
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-2xl p-8 border border-white/10 hover:border-red-600/50 transition-all">
            <div class="w-16 h-16 bg-blue-600/20 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-3">Visi Kami</h3>
            <p class="text-gray-400">
                Menjadi platform streaming anime terdepan di Indonesia dengan konten terlengkap dan komunitas yang aktif.
            </p>
        </div>

        <!-- Values Card -->
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-2xl p-8 border border-white/10 hover:border-red-600/50 transition-all">
            <div class="w-16 h-16 bg-green-600/20 rounded-2xl flex items-center justify-center mb-6">
                <svg class="w-8 h-8 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>
                </svg>
            </div>
            <h3 class="text-xl font-bold text-white mb-3">Nilai Kami</h3>
            <p class="text-gray-400">
                Kualitas, kecepatan, dan kepuasan pengguna adalah prioritas utama dalam setiap layanan yang kami berikan.
            </p>
        </div>
    </div>

    <!-- About Description -->
    <div class="bg-[#1a1d24] rounded-2xl p-8 lg:p-12 border border-white/5 mb-16">
        <h2 class="text-3xl font-bold text-white mb-6">Siapa Kami?</h2>
        <div class="prose prose-invert max-w-none space-y-4 text-gray-300">
            <p class="text-lg">
                <strong class="text-red-500">nipnime</strong> adalah platform streaming anime yang didirikan dengan tujuan memberikan pengalaman menonton anime terbaik untuk komunitas pecinta anime di Indonesia. Kami memahami bahwa anime bukan hanya sekedar hiburan, tetapi juga bagian penting dari budaya pop yang menginspirasi jutaan orang.
            </p>
            <p>
                Sejak awal, kami berkomitmen untuk menyediakan koleksi anime yang lengkap dengan berbagai genre mulai dari action, romance, comedy, hingga slice of life. Semua anime tersedia dengan subtitle Indonesia yang berkualitas untuk memastikan pengalaman menonton yang menyenangkan.
            </p>
            <p>
                Tim kami terdiri dari para penggemar anime yang berdedikasi, yang bekerja keras untuk memastikan website selalu update dengan episode terbaru dan berjalan dengan lancar. Kami juga aktif mendengarkan masukan dari komunitas untuk terus meningkatkan layanan kami.
            </p>
        </div>
    </div>

    <!-- Features Section -->
    <div class="mb-16">
        <h2 class="text-3xl font-bold text-white text-center mb-10">Mengapa Memilih nipnime?</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div class="flex items-start gap-4 bg-[#1a1d24]/50 rounded-xl p-6 border border-white/5">
                <div class="w-12 h-12 bg-red-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-red-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Streaming Berkualitas Tinggi</h3>
                    <p class="text-gray-400 text-sm">Video berkualitas HD dengan loading cepat dan minim buffering untuk pengalaman menonton yang nyaman.</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-[#1a1d24]/50 rounded-xl p-6 border border-white/5">
                <div class="w-12 h-12 bg-blue-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Update Cepat</h3>
                    <p class="text-gray-400 text-sm">Episode terbaru diupdate dengan cepat setelah rilis, sehingga Anda tidak ketinggalan cerita.</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-[#1a1d24]/50 rounded-xl p-6 border border-white/5">
                <div class="w-12 h-12 bg-green-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-green-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Subtitle Indonesia</h3>
                    <p class="text-gray-400 text-sm">Semua anime dilengkapi dengan subtitle Indonesia yang akurat dan mudah dibaca.</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-[#1a1d24]/50 rounded-xl p-6 border border-white/5">
                <div class="w-12 h-12 bg-purple-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-purple-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M5 4a1 1 0 00-2 0v7.268a2 2 0 000 3.464V16a1 1 0 102 0v-1.268a2 2 0 000-3.464V4zM11 4a1 1 0 10-2 0v1.268a2 2 0 000 3.464V16a1 1 0 102 0V8.732a2 2 0 000-3.464V4zM16 3a1 1 0 011 1v7.268a2 2 0 010 3.464V16a1 1 0 11-2 0v-1.268a2 2 0 010-3.464V4a1 1 0 011-1z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Koleksi Lengkap</h3>
                    <p class="text-gray-400 text-sm">Ribuan judul anime dari berbagai genre tersedia untuk ditonton kapan saja.</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-[#1a1d24]/50 rounded-xl p-6 border border-white/5">
                <div class="w-12 h-12 bg-yellow-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-yellow-500" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M3 5a2 2 0 012-2h10a2 2 0 012 2v8a2 2 0 01-2 2h-2.22l.123.489.804.321a1 1 0 01-.606 1.905l-1.214-.486a1 1 0 01-.606-.39L10 15.585l-1.28 1.254a1 1 0 01-.606.39l-1.213.486a1 1 0 01-.606-1.905l.804-.32.122-.49H5a2 2 0 01-2-2V5zm5.771 7H5V5h10v7H8.771z" clip-rule="evenodd"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Multi-Device</h3>
                    <p class="text-gray-400 text-sm">Akses nipnime dari berbagai perangkat - desktop, tablet, atau smartphone.</p>
                </div>
            </div>

            <div class="flex items-start gap-4 bg-[#1a1d24]/50 rounded-xl p-6 border border-white/5">
                <div class="w-12 h-12 bg-pink-600/20 rounded-xl flex items-center justify-center flex-shrink-0">
                    <svg class="w-6 h-6 text-pink-500" fill="currentColor" viewBox="0 0 20 20">
                        <path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-white mb-2">Komunitas Aktif</h3>
                    <p class="text-gray-400 text-sm">Bergabung dengan komunitas penggemar anime untuk berdiskusi dan berbagi rekomendasi.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Stats Section -->
    <div class="bg-gradient-to-r from-red-600/20 to-red-700/20 rounded-2xl p-8 border border-red-600/30 mb-16">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">
            <div>
                <div class="text-4xl font-black text-red-500 mb-2">1000+</div>
                <p class="text-gray-400">Judul Anime</p>
            </div>
            <div>
                <div class="text-4xl font-black text-red-500 mb-2">10000+</div>
                <p class="text-gray-400">Episode</p>
            </div>
            <div>
                <div class="text-4xl font-black text-red-500 mb-2">50000+</div>
                <p class="text-gray-400">Pengguna</p>
            </div>
            <div>
                <div class="text-4xl font-black text-red-500 mb-2">24/7</div>
                <p class="text-gray-400">Online</p>
            </div>
        </div>
    </div>

    <!-- Contact CTA -->
    <div class="text-center bg-[#1a1d24] rounded-2xl p-8 lg:p-12 border border-white/5">
        <h2 class="text-2xl font-bold text-white mb-4">Ada Pertanyaan?</h2>
        <p class="text-gray-400 mb-6 max-w-lg mx-auto">
            Kami selalu siap membantu! Jangan ragu untuk menghubungi kami jika ada pertanyaan, saran, atau masukan.
        </p>
        <a href="{{ route('contact') }}" class="inline-flex items-center gap-2 px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all transform hover:scale-105 shadow-lg shadow-red-600/30">
            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z"/>
                <path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z"/>
            </svg>
            Hubungi Kami
        </a>
    </div>
</div>
@endsection
