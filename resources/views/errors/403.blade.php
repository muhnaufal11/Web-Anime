@extends('layouts.app')
@section('title', '403 - Akses Ditolak')

@section('content')
<div class="min-h-screen bg-gradient-to-b from-[#0f1115] via-[#0f1115] to-[#1a1d24] flex items-center justify-center px-4">
    <div class="max-w-2xl w-full">
        <!-- Error Card -->
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-12 border border-red-600/30 shadow-2xl shadow-red-600/20 text-center">
            <!-- Icon -->
            <div class="mb-8">
                <div class="w-32 h-32 mx-auto bg-gradient-to-br from-red-600/20 to-red-700/20 rounded-full flex items-center justify-center border-4 border-red-600/30">
                    <svg class="w-16 h-16 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
            </div>

            <!-- Error Code -->
            <h1 class="text-8xl font-black text-red-500 mb-4 tracking-tighter">403</h1>
            
            <!-- Title -->
            <h2 class="text-3xl font-black text-white mb-4 uppercase tracking-wider">Akses Ditolak</h2>
            
            <!-- Message -->
            <p class="text-gray-400 text-lg mb-8 leading-relaxed">
                Maaf, halaman ini hanya dapat diakses oleh <span class="text-red-500 font-bold">Administrator</span>.<br>
                Anda tidak memiliki izin untuk mengakses halaman ini.
            </p>

            <!-- Divider -->
            <div class="flex items-center gap-4 mb-8">
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-red-600/30 to-transparent"></div>
                <span class="text-red-500 text-sm font-bold uppercase">🔒 Restricted Area</span>
                <div class="flex-1 h-px bg-gradient-to-r from-transparent via-red-600/30 to-transparent"></div>
            </div>

            <!-- Info Box -->
            <div class="bg-red-950/30 border border-red-600/20 rounded-xl p-6 mb-8">
                <p class="text-gray-400 text-sm mb-3">
                    <span class="text-red-400 font-bold">ℹ️ Informasi:</span>
                </p>
                <ul class="text-gray-400 text-sm space-y-2 text-left max-w-md mx-auto">
                    <li class="flex items-start gap-2">
                        <span class="text-red-500 mt-0.5">•</span>
                        <span>Halaman admin hanya untuk pengelola website</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-500 mt-0.5">•</span>
                        <span>Jika Anda merasa ini adalah kesalahan, hubungi administrator</span>
                    </li>
                    <li class="flex items-start gap-2">
                        <span class="text-red-500 mt-0.5">•</span>
                        <span>User biasa dapat menikmati menonton anime di halaman utama</span>
                    </li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="flex flex-col sm:flex-row gap-4 justify-center">
                <a href="{{ route('home') }}" class="px-8 py-4 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all shadow-lg shadow-red-600/30 hover:shadow-xl hover:shadow-red-600/40 uppercase tracking-wider">
                    🏠 Kembali ke Home
                </a>
                
                @auth
                    <a href="{{ route('profile.show') }}" class="px-8 py-4 bg-white/5 hover:bg-white/10 border-2 border-white/10 hover:border-red-600/50 text-white font-black rounded-xl transition-all uppercase tracking-wider">
                        👤 Profil Saya
                    </a>
                @else
                    <a href="{{ route('auth.login') }}" class="px-8 py-4 bg-white/5 hover:bg-white/10 border-2 border-white/10 hover:border-red-600/50 text-white font-black rounded-xl transition-all uppercase tracking-wider">
                        🔐 Login
                    </a>
                @endauth
            </div>

            <!-- Additional Info -->
            <p class="text-gray-600 text-xs mt-8">
                Error Code: 403 FORBIDDEN | Admin Access Only
            </p>
        </div>

        <!-- Fun Animation -->
        <div class="mt-8 text-center">
            <p class="text-gray-600 text-sm animate-pulse">
                💡 Tip: Nikmati ribuan anime gratis di halaman utama!
            </p>
        </div>
    </div>
</div>
@endsection
