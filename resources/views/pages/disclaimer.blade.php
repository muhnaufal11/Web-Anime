@extends('layouts.app')
@section('title', 'Disclaimer - nipnime')
@section('meta_description', 'Disclaimer dan penyangkalan konten untuk website nipnime. Informasi penting tentang konten dan layanan yang kami sediakan.')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-12">
    <div class="bg-[#1a1d24] rounded-2xl p-8 border border-white/5">
        <h1 class="text-4xl font-black text-white mb-8">Disclaimer</h1>
        
        <div class="prose prose-invert max-w-none space-y-6 text-gray-300">
            <p class="text-lg">
                Dengan mengakses dan menggunakan website <span class="text-red-500 font-bold">nipnime</span>, Anda menyetujui disclaimer berikut ini. Harap baca dengan seksama sebelum menggunakan layanan kami.
            </p>

            <div class="bg-yellow-500/10 border border-yellow-500/30 rounded-xl p-6 my-8">
                <div class="flex items-start gap-4">
                    <svg class="w-6 h-6 text-yellow-500 flex-shrink-0 mt-1" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                    </svg>
                    <div>
                        <h3 class="text-yellow-400 font-bold mb-2">Pemberitahuan Penting</h3>
                        <p class="text-yellow-200/80 text-sm">
                            Website ini adalah platform agregator yang mengumpulkan dan menampilkan konten dari berbagai sumber pihak ketiga. Kami tidak meng-host atau menyimpan file video apapun di server kami.
                        </p>
                    </div>
                </div>
            </div>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📋 Informasi Umum</h2>
            <p>
                Semua informasi yang disediakan di website nipnime adalah untuk tujuan informasi umum saja. Kami berusaha untuk menjaga informasi tetap akurat dan terbaru, namun kami tidak membuat pernyataan atau jaminan apapun, baik tersurat maupun tersirat, tentang kelengkapan, keakuratan, keandalan, kesesuaian, atau ketersediaan terkait website atau informasi, produk, layanan, atau grafik terkait yang terdapat di website untuk tujuan apapun.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🎬 Konten Video</h2>
            <ul class="list-disc list-inside space-y-3 ml-4">
                <li>
                    <strong>Sumber Pihak Ketiga:</strong> Semua video streaming yang tersedia di website ini di-embed dari layanan pihak ketiga. nipnime tidak meng-host, mengunggah, atau menyimpan file video apapun.
                </li>
                <li>
                    <strong>Hak Cipta:</strong> Semua konten anime adalah milik dari pemegang hak cipta masing-masing. Kami tidak mengklaim kepemilikan atas konten yang ditampilkan.
                </li>
                <li>
                    <strong>Tujuan Edukatif:</strong> Konten yang tersedia ditujukan untuk tujuan hiburan dan mengenalkan budaya anime kepada masyarakat Indonesia.
                </li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">⚖️ Kepatuhan Hukum</h2>
            <p>
                Kami menghormati hak kekayaan intelektual dan akan merespons pemberitahuan dugaan pelanggaran hak cipta yang sesuai dengan Digital Millennium Copyright Act (DMCA) atau peraturan yang berlaku. Jika Anda adalah pemegang hak cipta atau agen yang diberi wewenang dan yakin bahwa konten di website kami melanggar hak cipta Anda, silakan hubungi kami melalui halaman <a href="{{ route('dmca') }}" class="text-red-500 hover:text-red-400 underline">DMCA</a>.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🔗 Link Eksternal</h2>
            <p>
                Website kami mungkin berisi link ke website eksternal yang tidak disediakan atau dikelola oleh kami. Harap dicatat bahwa kami tidak memiliki kendali atas konten dan praktik website tersebut, dan tidak dapat bertanggung jawab atas kebijakan privasi masing-masing. Kami sangat menyarankan Anda untuk membaca syarat dan ketentuan serta kebijakan privasi dari setiap website pihak ketiga yang Anda kunjungi.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🛡️ Batasan Tanggung Jawab</h2>
            <p>
                Dalam keadaan apapun, kami tidak akan bertanggung jawab atas kerugian atau kerusakan termasuk tanpa batasan, kerugian atau kerusakan tidak langsung atau konsekuensial, atau kerugian atau kerusakan apapun yang timbul dari kehilangan data atau keuntungan yang timbul dari, atau sehubungan dengan, penggunaan website ini.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📺 Kualitas Streaming</h2>
            <p>
                Kualitas streaming bergantung pada layanan pihak ketiga dan koneksi internet Anda. Kami tidak dapat menjamin ketersediaan atau kualitas video setiap saat. Server video dapat mengalami gangguan sewaktu-waktu tanpa pemberitahuan sebelumnya.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">👥 Konten Pengguna</h2>
            <p>
                Pengguna dapat meninggalkan komentar dan interaksi lainnya di website. Kami tidak bertanggung jawab atas konten yang diposting oleh pengguna. Kami berhak untuk menghapus konten yang dianggap tidak pantas, menyinggung, atau melanggar ketentuan layanan kami.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🔞 Konten Dewasa</h2>
            <p>
                Beberapa konten di website ini mungkin mengandung materi yang tidak cocok untuk anak di bawah umur. Konten dengan rating dewasa (18+) memerlukan verifikasi usia. Orang tua dan wali disarankan untuk mengawasi aktivitas online anak-anak mereka.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">🚫 Penggunaan yang Dilarang</h2>
            <p>Anda dilarang menggunakan website ini untuk:</p>
            <ul class="list-disc list-inside space-y-2 ml-4">
                <li>Menyalahgunakan atau mengganggu keamanan website</li>
                <li>Mengunduh atau mendistribusikan konten tanpa izin</li>
                <li>Menggunakan bot atau scraper otomatis</li>
                <li>Melakukan tindakan yang melanggar hukum</li>
                <li>Menyebarkan malware atau kode berbahaya</li>
            </ul>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">📝 Perubahan Disclaimer</h2>
            <p>
                Kami berhak untuk memperbarui atau mengubah disclaimer ini kapan saja tanpa pemberitahuan sebelumnya. Perubahan akan berlaku segera setelah diposting di website. Penggunaan website secara berkelanjutan setelah perubahan tersebut merupakan penerimaan Anda terhadap disclaimer yang diperbarui.
            </p>

            <h2 class="text-2xl font-bold text-white mt-8 mb-4">✅ Persetujuan</h2>
            <p>
                Dengan menggunakan website kami, Anda menyetujui disclaimer ini dan setuju untuk mematuhi syarat dan ketentuannya. Jika Anda tidak setuju dengan bagian apapun dari disclaimer ini, harap jangan gunakan website kami.
            </p>

            <div class="bg-[#0f1115] rounded-xl p-6 mt-8 border border-white/10">
                <p class="text-gray-400 text-sm">
                    <strong class="text-white">Terakhir diperbarui:</strong> Januari 2026
                </p>
                <p class="text-gray-400 text-sm mt-2">
                    Jika Anda memiliki pertanyaan tentang disclaimer ini, silakan <a href="{{ route('contact') }}" class="text-red-500 hover:text-red-400 underline">hubungi kami</a>.
                </p>
            </div>
        </div>
    </div>
</div>
@endsection
