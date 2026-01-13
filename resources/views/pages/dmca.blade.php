@extends('layouts.app')
@section('title', 'DMCA')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-[#1a1d24] rounded-2xl p-8 border border-white/5">
        <h1 class="text-4xl font-black text-white mb-8">DMCA Policy</h1>
        
        <div class="prose prose-invert max-w-none space-y-6 text-gray-300">
            <p class="text-lg">
                <span class="text-red-500 font-bold">nipnime</span> menghormati hak kekayaan intelektual pihak lain dan mengharapkan pengguna kami untuk melakukan hal yang sama.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📋 Tentang Konten</h2>
            <p>
                Website ini <strong>TIDAK</strong> menyimpan file video apapun di server kami. Semua konten yang tersedia di website ini di-embed dari penyedia pihak ketiga seperti Google Drive, Streamtape, dan layanan streaming lainnya.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">⚠️ Pengajuan DMCA Takedown</h2>
            <p>
                Jika Anda yakin bahwa konten yang tersedia melalui website kami melanggar hak cipta Anda, silakan kirimkan pemberitahuan DMCA Takedown dengan informasi berikut:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Identifikasi karya berhak cipta yang diklaim telah dilanggar</li>
                <li>Identifikasi materi yang diklaim melanggar (termasuk URL)</li>
                <li>Informasi kontak Anda (nama, alamat, email, nomor telepon)</li>
                <li>Pernyataan bahwa Anda memiliki keyakinan dengan itikad baik bahwa penggunaan materi tersebut tidak diizinkan oleh pemilik hak cipta</li>
                <li>Pernyataan bahwa informasi dalam pemberitahuan adalah akurat</li>
                <li>Tanda tangan fisik atau elektronik dari pemilik hak cipta atau orang yang berwenang untuk bertindak atas nama mereka</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📧 Kontak DMCA</h2>
            <p>
                Kirimkan pemberitahuan DMCA Anda ke:
            </p>
            <div class="bg-white/5 rounded-xl p-4 mt-4">
                <p class="text-white font-mono">Email: dmca@nipnime.com</p>
                <p class="text-gray-400 text-sm mt-2">Subjek: DMCA Takedown Notice - [Judul Konten]</p>
            </div>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">⏱️ Waktu Respons</h2>
            <p>
                Kami akan merespons semua pemberitahuan DMCA yang valid dalam waktu <strong>24-72 jam</strong>. Konten yang melanggar akan segera dihapus atau dinonaktifkan setelah verifikasi.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🔄 Counter-Notification</h2>
            <p>
                Jika Anda yakin bahwa konten yang dihapus tidak melanggar hak cipta, Anda dapat mengajukan counter-notification dengan menyertakan:
            </p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Identifikasi materi yang telah dihapus</li>
                <li>Pernyataan bahwa Anda setuju dengan yurisdiksi pengadilan</li>
                <li>Tanda tangan fisik atau elektronik Anda</li>
            </ul>

            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-4 mt-8">
                <p class="text-yellow-400 font-medium">
                    ⚠️ <strong>Peringatan:</strong> Pengajuan klaim DMCA palsu dapat mengakibatkan konsekuensi hukum. Pastikan Anda adalah pemilik hak cipta atau agen resmi sebelum mengajukan klaim.
                </p>
            </div>

            <p class="text-gray-500 text-sm mt-8">
                Terakhir diperbarui: {{ date('d F Y') }}
            </p>
        </div>
    </div>
</div>
@endsection
