@extends('layouts.app')
@section('title', 'Daftar - nipnime')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-[#0f1115] via-[#0f1115] to-[#1a1d24] flex items-center justify-center px-4 py-12">
    <div class="max-w-md w-full">
        <!-- Card Container -->
        <div class="bg-gradient-to-br from-[#1a1d24] to-[#0f1115] rounded-3xl p-8 border-2 border-white/10 shadow-2xl">
            <!-- Header -->
            <div class="text-center mb-8">
                <div class="inline-block mb-4">
                    <div class="w-12 h-12 bg-gradient-to-br from-red-600 to-red-700 rounded-xl flex items-center justify-center shadow-lg shadow-red-600/30">
                        <svg class="w-7 h-7 text-white" fill="currentColor" viewBox="0 0 20 20"><path d="M10 18a8 8 0 100-16 8 8 0 000 16zM9.555 7.168A1 1 0 008 8v4a1 1 0 001.555.832l3-2a1 1 0 000-1.664l-3-2z"/></svg>
                    </div>
                </div>
                <h1 class="text-3xl font-black text-white mb-2">Bergabung dengan <span class="text-red-500">nipnime</span></h1>
                <p class="text-gray-400 text-sm">Buat akun baru dan mulai menonton</p>
            </div>

            <!-- Alerts -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-500/20 border-l-4 border-red-500 rounded-lg">
                    <p class="text-red-400 text-xs font-semibold">
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </p>
                </div>
            @endif

            <!-- Register Form -->
            <form action="{{ route('auth.register') }}" method="POST" class="space-y-4">
                @csrf

                <!-- Name Input -->
                <div class="group">
                    <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 block group-focus-within:text-red-500 transition">👤 Nama Lengkap</label>
                    <input 
                        type="text" 
                        name="name" 
                        value="{{ old('name') }}"
                        placeholder="Nama Anda"
                        required
                        class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500"
                    >
                </div>

                <!-- Email Input -->
                <div class="group">
                    <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 block group-focus-within:text-red-500 transition">📧 Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ old('email') }}"
                        placeholder="nama@email.com"
                        required
                        class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500"
                    >
                </div>

                <!-- Password Input -->
                <div class="group">
                    <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 block group-focus-within:text-red-500 transition">🔐 Password (min. 8 karakter)</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Buat password kuat"
                        required
                        class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500"
                    >
                    <p class="text-[10px] text-gray-500 mt-1">Gunakan kombinasi huruf, angka, dan simbol</p>
                </div>

                <!-- Confirm Password Input -->
                <div class="group">
                    <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 block group-focus-within:text-red-500 transition">✓ Konfirmasi Password</label>
                    <input 
                        type="password" 
                        name="password_confirmation" 
                        placeholder="Ulangi password"
                        required
                        class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500"
                    >
                </div>

                <!-- Terms Agreement -->
                <label class="flex items-start gap-3 cursor-pointer group mt-6 mb-4">
                    <input 
                        type="checkbox" 
                        name="agree_terms" 
                        required
                        class="w-4 h-4 rounded bg-[#0f1115] border-2 border-white/10 checked:bg-red-600 checked:border-red-600 cursor-pointer mt-1 flex-shrink-0"
                    >
                    <span class="text-xs text-gray-400 group-hover:text-gray-300 transition">
                        Saya setuju dengan 
                        <a href="{{ route('terms') }}" class="text-red-500 hover:text-red-400">Terms of Service</a> dan 
                        <a href="{{ route('privacy') }}" class="text-red-500 hover:text-red-400">Privacy Policy</a>
                    </span>
                </label>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-red-600/30 uppercase tracking-wider text-sm"
                >
                    ✓ BUAT AKUN
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-6">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-white/10"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-2 bg-gradient-to-br from-[#1a1d24] to-[#0f1115] text-gray-500">atau</span>
                </div>
            </div>

            <!-- Login Link -->
            <div class="text-center">
                <p class="text-gray-400 text-sm">Sudah punya akun?</p>
                <a 
                    href="{{ route('auth.login') }}" 
                    class="inline-block mt-2 px-6 py-2 bg-white/10 hover:bg-white/20 border-2 border-white/20 hover:border-white/30 text-white font-bold rounded-xl transition-all text-sm uppercase tracking-wider"
                >
                    Masuk Sekarang
                </a>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-8 text-xs text-gray-500">
            <p>Bergabunglah dengan jutaan penggemar anime di seluruh dunia</p>
        </div>
    </div>
</div>
@endsection
