@extends('layouts.app')
@section('title', 'Privacy Policy')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-[#1a1d24] rounded-2xl p-8 border border-white/5">
        <h1 class="text-4xl font-black text-white mb-8">Privacy Policy</h1>
        
        <div class="prose prose-invert max-w-none space-y-6 text-gray-300">
            <p class="text-lg">
                <span class="text-red-500 font-bold">nipnime</span> berkomitmen untuk melindungi privasi pengunjung kami. Kebijakan ini menjelaskan bagaimana kami mengumpulkan, menggunakan, dan melindungi informasi Anda.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🔍 Informasi yang Kami Kumpulkan</h2>
            
            <h3 class="text-xl font-semibold text-white mt-6 mb-3">Informasi Akun</h3>
            <p>Saat Anda mendaftar, kami mengumpulkan:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Nama pengguna</li>
                <li>Alamat email</li>
                <li>Password (terenkripsi)</li>
                <li>Foto profil (opsional)</li>
            </ul>

            <h3 class="text-xl font-semibold text-white mt-6 mb-3">Data Penggunaan</h3>
            <p>Kami secara otomatis mengumpulkan:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Riwayat tontonan (untuk fitur "Continue Watching")</li>
                <li>Alamat IP</li>
                <li>Jenis browser dan perangkat</li>
                <li>Halaman yang dikunjungi</li>
                <li>Waktu akses</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📊 Penggunaan Informasi</h2>
            <p>Kami menggunakan informasi Anda untuk:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Menyediakan dan memelihara layanan kami</li>
                <li>Mempersonalisasi pengalaman Anda (rekomendasi anime)</li>
                <li>Menyimpan riwayat tontonan dan progress</li>
                <li>Mengirim notifikasi tentang anime baru (jika diaktifkan)</li>
                <li>Meningkatkan website dan layanan kami</li>
                <li>Mencegah aktivitas berbahaya</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🍪 Cookies</h2>
            <p>
                Kami menggunakan cookies untuk:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li><strong>Session cookies:</strong> Untuk menjaga Anda tetap login</li>
                <li><strong>Preference cookies:</strong> Untuk mengingat pengaturan Anda</li>
                <li><strong>Analytics cookies:</strong> Untuk memahami bagaimana website digunakan</li>
            </ul>
            <p class="mt-4">
                Anda dapat mengatur browser untuk menolak cookies, namun beberapa fitur website mungkin tidak berfungsi dengan baik.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🔒 Keamanan Data</h2>
            <p>
                Kami menerapkan langkah-langkah keamanan untuk melindungi data Anda:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Enkripsi SSL/TLS untuk semua koneksi</li>
                <li>Password di-hash menggunakan algoritma bcrypt</li>
                <li>Akses database terbatas</li>
                <li>Backup data berkala</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🤝 Berbagi Data</h2>
            <p>
                Kami <strong>TIDAK</strong> menjual, memperdagangkan, atau menyewakan informasi pribadi Anda kepada pihak ketiga. Kami hanya berbagi informasi dalam situasi berikut:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Dengan persetujuan Anda</li>
                <li>Untuk mematuhi kewajiban hukum</li>
                <li>Untuk melindungi hak dan keamanan kami</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">👤 Hak Anda</h2>
            <p>Anda memiliki hak untuk:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Mengakses data pribadi Anda</li>
                <li>Memperbarui atau mengoreksi data Anda</li>
                <li>Menghapus akun dan data Anda</li>
                <li>Menolak pemrosesan data tertentu</li>
                <li>Meminta salinan data Anda</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">👶 Privasi Anak</h2>
            <p>
                Website ini tidak ditujukan untuk anak di bawah 13 tahun. Kami tidak secara sengaja mengumpulkan informasi dari anak-anak. Jika Anda adalah orang tua dan mengetahui anak Anda telah memberikan informasi kepada kami, silakan hubungi kami.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📝 Perubahan Kebijakan</h2>
            <p>
                Kami dapat memperbarui kebijakan privasi ini dari waktu ke waktu. Perubahan signifikan akan diberitahukan melalui email atau pemberitahuan di website.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📧 Hubungi Kami</h2>
            <p>
                Jika Anda memiliki pertanyaan tentang kebijakan privasi ini, silakan hubungi kami:
            </p>
            <div class="bg-white/5 rounded-xl p-4 mt-4">
                <p class="text-white font-mono">Email: privacy@nipnime.my.id</p>
            </div>

            <p class="text-gray-500 text-sm mt-8">
                Terakhir diperbarui: {{ date('d F Y') }}
            </p>
        </div>
    </div>
</div>
@endsection
