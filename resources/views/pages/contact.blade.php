@extends('layouts.app')
@section('title', 'Contact Us')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
        {{-- Contact Form --}}
        <div class="bg-[#1a1d24] rounded-2xl p-8 border border-white/5">
            <h1 class="text-3xl font-black text-white mb-2">Hubungi Kami</h1>
            <p class="text-gray-400 mb-6">Punya pertanyaan atau saran? Kirim pesan kepada kami!</p>

            {{-- Flash Messages --}}
            @if(session('success'))
                <div class="mb-6 bg-green-500/20 border border-green-500/50 text-green-400 px-4 py-3 rounded-xl flex items-center gap-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-6 bg-red-500/20 border border-red-500/50 text-red-400 px-4 py-3 rounded-xl">
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <form action="{{ route('contact.send') }}" method="POST" class="space-y-5">
                @csrf
                
                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Nama <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="{{ old('name', auth()->user()->name ?? '') }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                        placeholder="Nama lengkap Anda">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Email <span class="text-red-500">*</span></label>
                    <input type="email" name="email" value="{{ old('email', auth()->user()->email ?? '') }}" required
                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500"
                        placeholder="email@example.com">
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Subjek <span class="text-red-500">*</span></label>
                    <select name="subject" required
                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white focus:border-red-500 focus:ring-1 focus:ring-red-500">
                        <option value="" class="bg-gray-800">-- Pilih Subjek --</option>
                        <option value="Bug Report" class="bg-gray-800" {{ old('subject') == 'Bug Report' ? 'selected' : '' }}>🐛 Bug Report</option>
                        <option value="Feature Request" class="bg-gray-800" {{ old('subject') == 'Feature Request' ? 'selected' : '' }}>💡 Feature Request</option>
                        <option value="Video Issue" class="bg-gray-800" {{ old('subject') == 'Video Issue' ? 'selected' : '' }}>🎬 Video Tidak Bisa Diputar</option>
                        <option value="Account Issue" class="bg-gray-800" {{ old('subject') == 'Account Issue' ? 'selected' : '' }}>👤 Masalah Akun</option>
                        <option value="Business Inquiry" class="bg-gray-800" {{ old('subject') == 'Business Inquiry' ? 'selected' : '' }}>💼 Business Inquiry</option>
                        <option value="Other" class="bg-gray-800" {{ old('subject') == 'Other' ? 'selected' : '' }}>❓ Lainnya</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-300 mb-2">Pesan <span class="text-red-500">*</span></label>
                    <textarea name="message" rows="5" required
                        class="w-full px-4 py-3 bg-white/5 border border-white/20 rounded-xl text-white placeholder-gray-500 focus:border-red-500 focus:ring-1 focus:ring-red-500 resize-none"
                        placeholder="Tulis pesan Anda di sini...">{{ old('message') }}</textarea>
                    <p class="text-xs text-gray-500 mt-1">Maksimal 2000 karakter</p>
                </div>

                <button type="submit" 
                    class="w-full py-3 bg-gradient-to-r from-red-600 to-red-700 hover:from-red-700 hover:to-red-800 text-white font-bold rounded-xl transition-all flex items-center justify-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    Kirim Pesan
                </button>
            </form>
        </div>

        {{-- Contact Info --}}
        <div class="space-y-6">
            {{-- Info Cards --}}
            <div class="bg-[#1a1d24] rounded-2xl p-6 border border-white/5">
                <h2 class="text-xl font-bold text-white mb-4">📧 Email</h2>
                <div class="space-y-3">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center">
                            <span>📬</span>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Umum</p>
                            <p class="text-white font-mono">support@nipnime.com</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-yellow-500/20 rounded-lg flex items-center justify-center">
                            <span>⚖️</span>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">DMCA & Legal</p>
                            <p class="text-white font-mono">dmca@nipnime.com</p>
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center">
                            <span>💼</span>
                        </div>
                        <div>
                            <p class="text-gray-400 text-sm">Business</p>
                            <p class="text-white font-mono">business@nipnime.com</p>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Social Media --}}
            <div class="bg-[#1a1d24] rounded-2xl p-6 border border-white/5">
                <h2 class="text-xl font-bold text-white mb-4">🌐 Social Media</h2>
                <div class="grid grid-cols-2 gap-3">
                    <a href="#" class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition">
                        <div class="w-10 h-10 bg-blue-500/20 rounded-lg flex items-center justify-center text-xl">
                            𝕏
                        </div>
                        <span class="text-gray-300">Twitter/X</span>
                    </a>
                    <a href="https://discord.gg/sYfq6Pyrrr" target="_blank" rel="noopener" class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition">
                        <div class="w-10 h-10 bg-indigo-500/20 rounded-lg flex items-center justify-center text-xl">
                            🎮
                        </div>
                        <span class="text-gray-300">Discord</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition">
                        <div class="w-10 h-10 bg-pink-500/20 rounded-lg flex items-center justify-center text-xl">
                            📸
                        </div>
                        <span class="text-gray-300">Instagram</span>
                    </a>
                    <a href="#" class="flex items-center gap-3 p-3 bg-white/5 rounded-xl hover:bg-white/10 transition">
                        <div class="w-10 h-10 bg-red-500/20 rounded-lg flex items-center justify-center text-xl">
                            📺
                        </div>
                        <span class="text-gray-300">YouTube</span>
                    </a>
                </div>
            </div>

            {{-- FAQ Shortcut --}}
            <div class="bg-gradient-to-br from-red-600/20 to-purple-600/20 rounded-2xl p-6 border border-red-500/20">
                <h2 class="text-xl font-bold text-white mb-2">❓ FAQ</h2>
                <p class="text-gray-400 mb-4">Pertanyaan yang sering ditanyakan</p>
                <div class="space-y-3">
                    <details class="bg-white/5 rounded-lg">
                        <summary class="px-4 py-3 cursor-pointer text-white font-medium hover:text-red-500 transition">
                            Video tidak bisa diputar?
                        </summary>
                        <p class="px-4 pb-3 text-gray-400 text-sm">
                            Coba ganti server dengan klik dropdown server di bawah video. Jika masih tidak bisa, coba refresh halaman atau gunakan browser lain.
                        </p>
                    </details>
                    <details class="bg-white/5 rounded-lg">
                        <summary class="px-4 py-3 cursor-pointer text-white font-medium hover:text-red-500 transition">
                            Bagaimana cara request anime?
                        </summary>
                        <p class="px-4 pb-3 text-gray-400 text-sm">
                            Login ke akun Anda, lalu klik menu "Request" di navbar. Isi form dengan judul anime dan link MyAnimeList.
                        </p>
                    </details>
                    <details class="bg-white/5 rounded-lg">
                        <summary class="px-4 py-3 cursor-pointer text-white font-medium hover:text-red-500 transition">
                            Lupa password?
                        </summary>
                        <p class="px-4 pb-3 text-gray-400 text-sm">
                            Hubungi kami melalui form di samping dengan menggunakan email yang terdaftar.
                        </p>
                    </details>
                </div>
            </div>

            {{-- Response Time --}}
            <div class="bg-white/5 rounded-xl p-4 text-center">
                <p class="text-gray-400 text-sm">
                    ⏱️ Waktu respons rata-rata: <span class="text-white font-bold">1-2 hari kerja</span>
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
