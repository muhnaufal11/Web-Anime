@extends('layouts.app')
@section('title', 'Terms of Service')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-[#1a1d24] rounded-2xl p-8 border border-white/5">
        <h1 class="text-4xl font-black text-white mb-8">Terms of Service</h1>
        
        <div class="prose prose-invert max-w-none space-y-6 text-gray-300">
            <p class="text-lg">
                Selamat datang di <span class="text-red-500 font-bold">nipnime</span>. Dengan mengakses atau menggunakan website kami, Anda menyetujui untuk terikat dengan syarat dan ketentuan berikut.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">1. Penerimaan Syarat</h2>
            <p>
                Dengan mengakses website ini, Anda menyetujui untuk terikat dengan Syarat dan Ketentuan ini, semua hukum dan peraturan yang berlaku, dan setuju bahwa Anda bertanggung jawab untuk mematuhi hukum lokal yang berlaku.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">2. Deskripsi Layanan</h2>
            <p>
                nipnime adalah platform streaming anime yang menyediakan:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Akses ke konten anime dari berbagai sumber</li>
                <li>Fitur bookmark dan riwayat tontonan</li>
                <li>Sistem komentar dan diskusi</li>
                <li>Informasi dan metadata anime</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">3. Akun Pengguna</h2>
            <h3 class="text-xl font-semibold text-white mt-6 mb-3">3.1 Pendaftaran</h3>
            <p>
                Untuk mengakses fitur tertentu, Anda perlu membuat akun. Anda bertanggung jawab untuk:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Memberikan informasi yang akurat</li>
                <li>Menjaga kerahasiaan password</li>
                <li>Semua aktivitas yang terjadi di akun Anda</li>
            </ul>

            <h3 class="text-xl font-semibold text-white mt-6 mb-3">3.2 Penangguhan Akun</h3>
            <p>
                Kami berhak menangguhkan atau menghentikan akun Anda jika:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Melanggar syarat dan ketentuan ini</li>
                <li>Aktivitas spam atau abuse</li>
                <li>Penggunaan bot atau scraping</li>
                <li>Berbagi konten ilegal atau berbahaya</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">4. Konten Pengguna</h2>
            <h3 class="text-xl font-semibold text-white mt-6 mb-3">4.1 Komentar dan Diskusi</h3>
            <p>Saat memposting komentar, Anda setuju untuk TIDAK:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Memposting konten yang menyinggung, kasar, atau mengandung ujaran kebencian</li>
                <li>Melakukan spam atau promosi tanpa izin</li>
                <li>Memposting spoiler tanpa peringatan</li>
                <li>Membagikan informasi pribadi orang lain</li>
                <li>Memposting konten dewasa atau pornografi</li>
            </ul>

            <h3 class="text-xl font-semibold text-white mt-6 mb-3">4.2 Hak atas Konten</h3>
            <p>
                Dengan memposting konten, Anda memberikan kami lisensi non-eksklusif untuk menampilkan konten tersebut di platform kami.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">5. Hak Kekayaan Intelektual</h2>
            <p>
                Website ini tidak mengklaim kepemilikan atas anime atau konten media yang ditampilkan. Semua anime dan merek dagang adalah milik pemilik dan pemegang lisensi masing-masing.
            </p>
            <p class="mt-4">
                Jika Anda adalah pemegang hak dan ingin konten dihapus, silakan lihat halaman <a href="{{ route('dmca') }}" class="text-red-500 hover:underline">DMCA</a> kami.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">6. Batasan Tanggung Jawab</h2>
            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4">
                <p class="text-yellow-400">
                    Website ini disediakan "sebagaimana adanya" tanpa jaminan apapun. Kami tidak bertanggung jawab atas:
                </p>
                <ul class="list-disc list-inside space-y-2 ml-4 mt-3 text-yellow-400/80">
                    <li>Ketersediaan atau kualitas konten</li>
                    <li>Keakuratan informasi yang ditampilkan</li>
                    <li>Kerugian yang timbul dari penggunaan website</li>
                    <li>Konten dari situs pihak ketiga yang di-link</li>
                </ul>
            </div>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">7. Penggunaan yang Dilarang</h2>
            <p>Anda dilarang untuk:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Menggunakan website untuk tujuan ilegal</li>
                <li>Mencoba mengakses area terlarang dari website</li>
                <li>Mengganggu atau merusak website atau server</li>
                <li>Menggunakan scraping, bot, atau metode otomatis lainnya</li>
                <li>Menyalin atau mendistribusikan konten website tanpa izin</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">8. Link ke Situs Lain</h2>
            <p>
                Website kami mungkin berisi link ke situs pihak ketiga. Kami tidak bertanggung jawab atas konten, kebijakan privasi, atau praktik situs tersebut.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">9. Perubahan Layanan</h2>
            <p>
                Kami berhak untuk:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Mengubah atau menghentikan layanan kapan saja</li>
                <li>Memperbarui syarat dan ketentuan ini</li>
                <li>Membatasi akses ke fitur tertentu</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">10. Hukum yang Berlaku</h2>
            <p>
                Syarat dan ketentuan ini diatur oleh dan ditafsirkan sesuai dengan hukum yang berlaku di Indonesia.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">11. Hubungi Kami</h2>
            <p>
                Jika Anda memiliki pertanyaan tentang syarat dan ketentuan ini:
            </p>
            <div class="bg-white/5 rounded-xl p-4 mt-4">
                <p class="text-white font-mono">Email: legal@nipnime.com</p>
            </div>

            <p class="text-gray-500 text-sm mt-8">
                Terakhir diperbarui: {{ date('d F Y') }}
            </p>
        </div>
    </div>
</div>
@endsection
