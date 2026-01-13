@extends('layouts.app')
@section('title', 'Login - nipnime')

@section('content')
<div class="min-h-screen bg-gradient-to-br from-[#0f1115] via-[#0f1115] to-[#1a1d24] flex items-center justify-center px-4">
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
                <h1 class="text-3xl font-black text-white mb-2">Masuk ke <span class="text-red-500">nipnime</span></h1>
                <p class="text-gray-400">Nikmati koleksi anime terlengkap dengan kualitas terbaik</p>
            </div>

            <!-- Alerts -->
            @if ($errors->any())
                <div class="mb-6 p-4 bg-red-500/20 border-l-4 border-red-500 rounded-lg">
                    <p class="text-red-400 text-sm font-semibold">
                        @foreach ($errors->all() as $error)
                            {{ $error }}<br>
                        @endforeach
                    </p>
                </div>
            @endif

            @if (session('success'))
                <div class="mb-6 p-4 bg-green-500/20 border-l-4 border-green-500 rounded-lg">
                    <p class="text-green-400 text-sm font-semibold">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Login Form -->
            <form action="{{ route('auth.login') }}" method="POST" class="space-y-5">
                @csrf

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
                    <label class="text-xs font-black text-gray-400 uppercase tracking-widest mb-2 block group-focus-within:text-red-500 transition">🔐 Password</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Masukkan password"
                        required
                        class="w-full bg-[#0f1115] border-2 border-white/10 text-white rounded-xl px-5 py-3 text-sm focus:border-red-600 focus:ring-2 focus:ring-red-600/30 transition-all placeholder-gray-600 focus:placeholder-gray-500"
                    >
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer group">
                        <input 
                            type="checkbox" 
                            name="remember" 
                            class="w-4 h-4 rounded bg-[#0f1115] border-2 border-white/10 checked:bg-red-600 checked:border-red-600 cursor-pointer"
                        >
                        <span class="text-xs text-gray-400 group-hover:text-gray-300 transition">Ingat saya</span>
                    </label>
                    <a href="#" class="text-xs text-red-500 hover:text-red-400 transition font-semibold">Lupa password?</a>
                </div>

                <!-- Submit Button -->
                <button 
                    type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-black rounded-xl transition-all transform hover:scale-[1.02] active:scale-95 shadow-lg shadow-red-600/30 uppercase tracking-wider mt-6"
                >
                    ▶ MASUK SEKARANG
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

            <!-- Google Login Button -->
            <div class="mb-6">
                <a href="{{ route('auth.google') }}" class="w-full flex items-center justify-center gap-3 py-3 px-4 bg-white text-gray-800 font-bold rounded-xl shadow hover:bg-gray-100 transition-all border border-gray-200">
                    <svg class="w-5 h-5" viewBox="0 0 48 48"><g><path fill="#4285F4" d="M24 9.5c3.54 0 6.7 1.22 9.19 3.22l6.85-6.85C35.64 2.7 30.18 0 24 0 14.82 0 6.71 5.82 2.69 14.09l7.98 6.2C12.13 13.13 17.57 9.5 24 9.5z"/><path fill="#34A853" d="M46.1 24.55c0-1.64-.15-3.22-.43-4.74H24v9.01h12.42c-.54 2.9-2.18 5.36-4.65 7.01l7.19 5.59C43.99 37.13 46.1 31.36 46.1 24.55z"/><path fill="#FBBC05" d="M10.67 28.29c-1.13-3.36-1.13-6.97 0-10.33l-7.98-6.2C.89 15.1 0 19.41 0 24c0 4.59.89 8.9 2.69 12.24l7.98-6.2z"/><path fill="#EA4335" d="M24 48c6.18 0 11.64-2.05 15.52-5.59l-7.19-5.59c-2.01 1.35-4.59 2.15-8.33 2.15-6.43 0-11.87-3.63-14.33-8.85l-7.98 6.2C6.71 42.18 14.82 48 24 48z"/><path fill="none" d="M0 0h48v48H0z"/></g></svg>
                    <span>Masuk dengan Google</span>
                </a>
            </div>

            <!-- Register Link -->
            <div class="text-center">
                <p class="text-gray-400 text-sm">Belum punya akun?</p>
                <a 
                    href="{{ route('auth.register') }}" 
                    class="inline-block mt-2 px-6 py-2 bg-white/10 hover:bg-white/20 border-2 border-white/20 hover:border-white/30 text-white font-bold rounded-xl transition-all text-sm uppercase tracking-wider"
                >
                    Daftar Sekarang
                </a>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="text-center mt-8 text-xs text-gray-500">
            <p>Dengan masuk, Anda menyetujui <a href="{{ route('terms') }}" class="text-red-500 hover:text-red-400">Terms of Service</a> dan <a href="{{ route('privacy') }}" class="text-red-500 hover:text-red-400">Privacy Policy</a></p>
        </div>
    </div>
</div>
@endsection
