@extends('layouts.app')

@section('content')
<div class="min-h-screen flex items-center justify-center bg-[#0f0f0f] py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-md w-full space-y-8">
        
        <div class="text-center">
            <h2 class="mt-6 text-center text-3xl font-extrabold text-white tracking-wider">
                <span class="text-red-600">NIP</span>NIME
            </h2>
            <p class="mt-2 text-center text-sm text-gray-400">
                Satu langkah lagi untuk masuk ke dunia anime.
            </p>
        </div>

        <div class="bg-[#1a1a1a] py-8 px-4 shadow-2xl rounded-xl border border-gray-800 sm:px-10 relative overflow-hidden">
            
            <div class="absolute top-0 left-0 w-full h-1 bg-gradient-to-r from-red-600 to-transparent opacity-75"></div>

            <h3 class="text-xl font-bold text-white mb-6 text-center">Verifikasi Email</h3>

            @if(session('success'))
                <div class="mb-4 bg-green-900/50 border border-green-600 text-green-300 px-4 py-3 rounded relative text-sm text-center shadow-lg" role="alert">
                    <span class="block sm:inline">Check emailmu! {{ session('success') }}</span>
                </div>
            @endif

            @if(session('error') || $errors->any())
                <div class="mb-4 bg-red-900/50 border border-red-600 text-red-300 px-4 py-3 rounded relative text-sm text-center shadow-lg" role="alert">
                    <span class="block sm:inline">{{ session('error') ?? $errors->first() }}</span>
                </div>
            @endif

            <form class="space-y-6" action="{{ route('auth.otp.verify') }}" method="POST">
                @csrf
                <div>
                    <label for="otp" class="block text-sm font-medium text-gray-400 mb-2 text-center">
                        Masukkan 6 Digit Kode
                    </label>
                    <div class="mt-1 relative rounded-md shadow-sm">
                        <input id="otp" name="otp" type="text" maxlength="6" required autofocus
                            style="background-color: #1a1a1a !important; color: #ffffff !important; border-color: #4b5563;"
                            class="appearance-none block w-full px-3 py-4 border border-gray-700 rounded-lg text-white placeholder-gray-500 text-center text-3xl tracking-[0.5em] font-bold focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-red-500 sm:text-2xl transition duration-300"
                            placeholder="000000">
                    </div>
                </div>

                <div>
                    <button type="submit"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 focus:ring-offset-gray-900 transition-all duration-300 shadow-lg hover:shadow-red-900/50 transform hover:-translate-y-0.5">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-red-300 group-hover:text-red-100 transition-colors" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M5 9V7a5 5 0 0110 0v2a2 2 0 012 2v5a2 2 0 01-2 2H5a2 2 0 01-2-2v-5a2 2 0 012-2zm8-2v2H7V7a3 3 0 016 0z" clip-rule="evenodd" />
                            </svg>
                        </span>
                        Verifikasi Masuk
                    </button>
                </div>
            </form>

            <div class="mt-6">
                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-2 bg-[#1a1a1a] text-gray-400">
                            Tidak menerima kode?
                        </span>
                    </div>
                </div>

                <div class="mt-4 text-center">
                    <form method="POST" action="{{ route('auth.otp.resend') }}">
                        @csrf
                        <button type="submit" class="text-sm font-medium text-red-500 hover:text-red-400 transition duration-300 hover:underline">
                            Kirim Ulang OTP
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <p class="text-center text-xs text-gray-600">
            &copy; {{ date('Y') }} NipNime. Protected by Anime Magic.
        </p>
    </div>
</div>
@endsection