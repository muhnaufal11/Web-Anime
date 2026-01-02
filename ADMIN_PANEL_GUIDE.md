# 🎛️ Panduan Lengkap Panel Admin - nipnime

##  Daftar Isi
1. [Akses Panel Admin](#akses-panel-admin)
2. [Dashboard Overview](#dashboard-overview)
3. [Manajemen Anime](#manajemen-anime)
4. [Manajemen Episode](#manajemen-episode)
5. [Video Servers](#video-servers)
6. [Manajemen Genre](#manajemen-genre)
7. [Manajemen User](#manajemen-user)
8. [Anime Requests](#anime-requests)
9. [Admin Episode Logs / Pendapatan](#admin-episode-logs--pendapatan)
10. [Scraping System](#scraping-system)
11. [MAL Sync](#mal-sync)
12. [Import dari HTML](#import-dari-html)
13. [Jadwal Tayang](#jadwal-tayang)
14. [Holiday Settings](#holiday-settings)
15. [Tips & Troubleshooting](#tips--troubleshooting)

---

## 🔐 Akses Panel Admin

### Cara Login ke Admin Panel

1. **Buka URL Admin:**
   ```
   http://localhost:8000/admin
   atau
   http://127.0.0.1:8000/admin
   ```

2. **Login dengan Akun Admin:**
   - **Email:** `admin@example.com`
   - **Password:** `password`
   
   > ⚠️ **PENTING:** Segera ganti password default setelah login pertama kali!

3. **Jika Belum Punya Akun Admin:**
   ```bash
   # Di terminal/command prompt:
   cd "c:\xampp\htdocs\Web Anime"
   php create_admin.php
   ```
   Script akan membuat user admin baru dengan kredensial default.

### Membuat Admin dari User yang Sudah Ada

```bash
# Jalankan script untuk upgrade user biasa menjadi admin:
php quick_make_admin.php

# Pilih user yang ingin dijadikan admin dari daftar
```

### Cek Status Admin User

```bash
# Lihat semua user dan status adminnya:
php list_users.php

# Debug info user tertentu:
php debug_admin.php
```

---

## 🏠 Dashboard Overview

Setelah login, Anda akan melihat Dashboard dengan:

### Sidebar Menu (Kiri)
- **Dashboard** - Halaman utama
- **Anime** - Kelola daftar anime
- **Episodes** - Kelola episode anime
- **Genres** - Kelola kategori genre
- **Users** - Kelola user dan admin
- **Video Servers** - Kelola server streaming
- **Anime Requests** - Antrian permintaan user
- **Admin Episode Logs / Pendapatan** - Performa & bayaran admin (khusus admin/superadmin)
- **Scrape Configs** - Konfigurasi scraping (MAL / AnimeSail)
- **Scrape Logs** - Log history scraping
- **Schedules** - Jadwal tayang anime
- **MAL Sync** - Sinkronisasi dari MyAnimeList
- **Import dari HTML** - Import anime/episode via HTML
- **Holiday Settings** - Toggle tema Natal/Tahun Baru

### Top Bar (Atas)
- **Search** - Cari data dengan cepat
- **Notifications** - Notifikasi sistem
- **Profile** - Menu profil admin
- **Dark/Light Mode** - Toggle tema

---

## 🎬 Manajemen Anime

### Melihat Daftar Anime

1. Klik **"Anime"** di sidebar
2. Anda akan melihat tabel dengan kolom:
   - **Poster** - Gambar poster anime
   - **Title** - Judul anime
   - **Type** - TV/Movie/OVA/Special
   - **Status** - Ongoing/Completed
   - **Rating** - Rating 0-10
   - **Episodes** - Jumlah episode
   - **Created At** - Tanggal ditambahkan

### Fitur Tabel Anime

**Pencarian:**
- Kotak search di kanan atas
- Cari berdasarkan judul, synopsis, atau slug

**Filter:**
- **Type:** TV, Movie, OVA, Special, ONA
- **Status:** Ongoing, Completed, Upcoming
- **Rating:** Filter berdasarkan range rating

**Sorting:**
- Klik header kolom untuk sort ascending/descending
- Sort by: Title, Rating, Created Date, dll

**Pagination:**
- Pilih items per page: 10, 25, 50, 100
- Navigasi halaman dengan tombol prev/next

### Menambah Anime Baru

1. **Klik tombol "New Anime"** (hijau, kanan atas)

2. **Isi Form:**
   
   **Tab: Details**
   - **Title** *(Required)* - Judul anime
     - Contoh: "Naruto Shippuden"
   
   - **Slug** *(Auto-generate)* - URL-friendly version
     - Auto diisi dari title
     - Contoh: "naruto-shippuden"
   
   - **Synopsis** *(Required)* - Deskripsi cerita
     - Min 10 karakter
     - Jelaskan plot anime
   
    - **Type** *(Required)* - Pilih jenis:
       - TV (Serial TV)
       - Movie (Film)
       - ONA (Original Net Animation)
   
    - **Status** *(Required)* - Status tayang:
       - Ongoing (Sedang tayang)
       - Completed (Sudah selesai)
   
   - **Rating** *(Required)* - Rating 0-10
     - Gunakan slider atau ketik angka
     - Contoh: 8.5
   
   - **Release Year** *(Optional)* - Tahun rilis
     - Contoh: 2023

   **Tab: Media**
   - **Poster Image** - Upload gambar poster
     - Format: JPG, PNG, WebP
     - Ukuran max: 2MB
     - Rekomendasi: 300x450px (aspect ratio 2:3)
       - Klik "Browse" untuk pilih file (nama file otomatis memakai slug + timestamp)

   **Tab: Relations**
   - **Genres** - Pilih genre anime
     - Multi-select, bisa pilih banyak
     - Contoh: Action, Adventure, Shounen
    - **Featured** - Toggle untuk menampilkan di spotlight homepage

3. **Klik "Create"** untuk simpan

### Mengedit Anime

1. **Pilih Anime:**
   - Klik baris anime di tabel, atau
   - Klik icon pensil di kolom Actions

2. **Edit Form:**
   - Sama seperti form create
   - Semua field bisa diubah
   - Upload poster baru untuk ganti

3. **Klik "Save"** untuk simpan perubahan

### Menghapus Anime

1. **Pilih Anime** yang ingin dihapus
2. **Klik icon trash** di kolom Actions
3. **Konfirmasi** penghapusan
4. **Warning:** Semua episode dan video servers terkait akan ikut terhapus (cascade delete)

### Bulk Actions (Aksi Massal)

1. **Centang checkbox** di beberapa anime
2. **Pilih action** di dropdown "Bulk Actions":
   - **Delete selected** - Hapus semua yang dipilih
   - **Export** - Export data ke Excel/CSV
3. **Klik "Run"** untuk eksekusi

### Sinkronisasi Episode + Server via HTML (Action "Sync Videos")

Gunakan jika sudah memiliki HTML halaman anime (hasil scrape manual atau simpanan offline) untuk otomatis membuat/update episode sekaligus server:

1. Klik **Action "Sync Videos"** pada baris anime.
2. Masukkan sumber HTML:
   - **HTML Source**: paste langsung isi HTML.
   - **Upload HTML**: unggah file `.html`/`.txt` (disimpan sementara di `storage/app/public/uploads/html`).
3. Opsional: aktifkan **Delete missing** untuk menghapus episode yang tidak ada di HTML.
4. Jalankan. Sistem akan membuat/memperbarui episode, membersihkan server episode yang dihapus (jika dipilih), dan mencatat log pendapatan admin untuk episode yang diproses.
5. Jika HTML kosong, aksi akan dibatalkan dan muncul notifikasi "HTML Required".

---

## 📺 Manajemen Episode

### Melihat Daftar Episode

1. Klik **"Episodes"** di sidebar
2. Tabel menampilkan:
   - **Anime** - Anime yang terkait
   - **Episode Number** - Nomor episode
   - **Title** - Judul episode
   - **Slug** - URL slug
   - **Servers** - Jumlah video server
   - **Created At** - Tanggal ditambahkan

### Menambah Episode Baru

1. **Klik "New Episode"**

2. **Isi Form:**
   
   - **Anime** *(Required)* - Pilih anime
     - Search box dengan autocomplete
     - Ketik nama anime untuk cari
   
   - **Episode Number** *(Required)* - Nomor episode
     - Integer, contoh: 1, 2, 3
     - Untuk season 2 ep 1, bisa: 25 (lanjutan)
   
    - **Title** *(Required)* - Judul episode
       - Contoh: "The Boy in the Iceberg"
   
   - **Slug** *(Auto-generate)* - URL slug
     - Format: anime-slug-episode-X
     - Contoh: naruto-shippuden-episode-1
   
   - **Description** *(Optional)* - Deskripsi episode
     - Sinopsis singkat episode

3. **Klik "Create"**

### Menambahkan Video Server ke Episode

Setelah episode dibuat, tambahkan video server:

1. **Buka Episode** yang baru dibuat
2. **Scroll ke bagian "Video Servers"**
3. **Klik "Add Video Server"**
4. **Isi data:**
   - **Server Name** - Nama server (Streamtape, Googledrive, dll)
   - **Embed URL** - URL embed video
     - Contoh: `https://streamtape.com/e/xxxxx`
   - **Server Type** - Pilih jenis server
   - **Quality** - 360p, 480p, 720p, 1080p
   - **Is Primary** - Centang jika server utama

5. **Klik "Create"**

### Sync Servers dari HTML (Action "Sync Servers")

Gunakan di tabel Episodes untuk parsing server dari HTML episode:

1. Klik **Action "Sync Servers"** pada baris episode.
2. Masukkan HTML:
   - **Episode HTML**: paste langsung konten.
   - **Upload Episode HTML**: unggah file `.html`/`.txt` (disimpan sementara di `storage/app/public/uploads/html-episodes`).
3. Opsional: **Delete existing** untuk menghapus server lama yang tidak ditemukan di hasil parse.
4. Sistem akan membuat/memperbarui server, mengonversi URL ke embed code, mencatat log pendapatan admin, dan menghapus file upload setelah diproses.
5. Jika HTML kosong atau server tidak ditemukan, akan muncul notifikasi peringatan.

### Bulk Sync Servers (aksi massal)

Untuk banyak episode sekaligus (misal 1 season):

1. Pilih beberapa episode → **Bulk Actions → Bulk Sync Servers**.
2. Pilih sumber:
   - **HTML Content**: satu HTML dipakai untuk semua episode terpilih.
   - **Upload HTML Files (per episode)**: unggah banyak file; nama file harus mengandung nomor episode (regex akan mendeteksi `Episode 5`, `_05`, `-12.html`, dll).
3. Opsional: **Delete existing** untuk membersihkan server yang tidak muncul di hasil parse.
4. Jalankan. Sistem akan memetakan file ke episode, memproses server, membuat log pendapatan admin, dan otomatis menghapus file upload setelah selesai.

### Tips Episode Management

**Nomor Episode:**
- Season 1: 1-24
- Season 2: 25-48 (atau 1-24 jika terpisah)
- Movie: 0 atau 999
- OVA: 0.5, 13.5 (between episodes)

**Multiple Servers:**
- Selalu tambahkan minimal 2-3 server backup
- Set server tercepat sebagai primary
- Gunakan quality berbeda untuk pilihan user

**Bulk Episode Creation:**
- Gunakan script scraping untuk import massal
- Atau gunakan CSV import (jika tersedia)

---

## 🔗 Video Servers

Gunakan menu **Video Servers** untuk mengelola server per episode secara langsung (di luar halaman Episode).

### Membuat / Mengedit Server

1. Klik **"New Video Server"** atau edit server yang ada.
2. Isi field:
   - **Server Name** *(required)* – nama provider (mis. Streamtape, GDrive).
   - **Embed URL** *(required)* – URL atau embed code; sistem menyimpan apa adanya.
   - **Active** – toggle aktif/nonaktif.
   - **Episode** *(required)* – pilih episode terkait (searchable + preload).
3. Simpan.

### Fitur Tabel

- Kolom utama: server name, episode title.
- Aksi **Copy** untuk menduplikasi server (nama otomatis ditambahkan "(Copy)") lalu diarahkan ke halaman edit.
- Aksi standar: Edit, Delete; Bulk Delete tersedia.

---

## 🎭 Manajemen Genre

### Melihat Daftar Genre

1. Klik **"Genres"** di sidebar
2. Tabel menampilkan:
   - **Name** - Nama genre
   - **Slug** - URL slug
   - **Animes Count** - Jumlah anime dengan genre ini

### Menambah Genre Baru

1. **Klik "New Genre"**

2. **Isi Form:**
   - **Name** *(Required)* - Nama genre
     - Contoh: "Action", "Romance", "Isekai"
   - **Slug** *(Auto-generate)* - URL slug
     - Auto dari name
     - Contoh: "action", "romance"

3. **Klik "Create"**

### Genre yang Umum Digunakan

```
Action          - Aksi, pertarungan
Adventure       - Petualangan
Comedy          - Komedi
Drama           - Drama
Fantasy         - Fantasi
Horror          - Horor
Isekai          - Dunia lain
Magic           - Sihir
Mecha           - Robot
Military        - Militer
Music           - Musik
Mystery         - Misteri
Psychological   - Psikologis
Romance         - Romansa
School          - Sekolah
Sci-Fi          - Fiksi ilmiah
Shounen         - Target remaja pria
Shoujo          - Target remaja wanita
Slice of Life   - Kehidupan sehari-hari
Sports          - Olahraga
Supernatural    - Supranatural
Thriller        - Thriller
```

### Mengedit/Menghapus Genre

- **Edit:** Klik baris genre → Edit nama/slug
- **Delete:** Klik trash icon → Konfirmasi
- **Warning:** Genre dengan anime terkait tidak bisa dihapus (referential integrity)

---

## 👥 Manajemen User

### Melihat Daftar User

1. Klik **"Users"** di sidebar.
2. Kolom penting:
   - **Avatar, Nama, Email, Role** (User/Admin/Superadmin).
   - **Episode Dibuat** & **Total Bayaran** (hanya terlihat oleh superadmin).
   - **Komentar** & **Riwayat** (jumlah komentar dan watch history per user).
   - **Terdaftar** (tanggal join).

### Membuat / Mengedit User

1. Klik **"New User"** atau edit baris yang ada.
2. Field utama:
   - **Name, Email, Password** (password wajib saat create, opsional saat edit; kosongkan jika tidak ganti).
   - **Avatar upload**, **Bio**, **Phone**, **Gender**, **Birth Date**, **Location**.
   - **Role** (User/Admin/Superadmin) hanya bisa diubah oleh superadmin; admin biasa hanya bisa melihat perannya.
3. Simpan. Password otomatis di-hash.

### Mengelola Role Admin

- **Toggle Admin** di tabel hanya terlihat oleh superadmin. Tidak bisa menurunkan/menaikkan diri sendiri atau superadmin lain.
- Bulk actions untuk **Jadikan Admin** / **Hapus Role Admin** juga hanya untuk superadmin.
- Skrip CLI alternatif: `php quick_make_admin.php` atau `php create_admin.php` untuk membuat akun admin baru.

### Aturan Hapus

- Tidak bisa menghapus akun sendiri (baik single delete maupun bulk).
- Hindari menghapus superadmin lain tanpa konfirmasi operasional.

### Tips User Management

- Gunakan peran minimal (least privilege) dan review admin berkala.
- Lengkapi avatar & bio untuk tampilan profil publik.
- Manfaatkan badge **Episode Dibuat** dan **Total Bayaran** untuk audit kinerja (superadmin only).

---

## 📨 Anime Requests

Menu ini menampung permintaan user (anime baru atau penambahan episode).

### Melihat & Menyaring
- Kolom: Votes, Judul, Tipe (Anime Baru / Tambah Episode), Status (Pending/Approved/Rejected/Completed), User, Link MAL, Tanggal.
- Filter berdasarkan **Status** dan **Tipe**. Badge navigasi menampilkan jumlah pending.

### Memproses Request
1. Pilih baris → pilih aksi:
   - **Approve** (isi catatan opsional) → status jadi Approved.
   - **Reject** (wajib isi alasan) → status jadi Rejected.
   - **Complete** (setelah dikerjakan) → status Completed.
2. Setiap aksi mencatat `processed_at` dan `processed_by`.

### Form & Catatan
- Field utama: Judul, MAL URL/ID, alasan request, tipe (anime baru / tambah episode), opsi link ke anime yang sudah ada, status, admin notes, upvotes (read-only).
- Gunakan catatan admin untuk memberi update ke user.

---

## 💰 Admin Episode Logs / Pendapatan

Menu ini hanya muncul untuk admin/superadmin. Log dibuat otomatis saat sync episode/server melalui panel.

### Akses & Tampilan
- Admin melihat log miliknya; superadmin melihat semua log.
- Kolom: Admin, Episode (dengan nomor), Anime, Bayaran, Status (Pending/Approved/Paid), Tanggal, Catatan. Superadmin juga bisa melihat data rekening/metode bayar admin.

### Update Metode Pembayaran (Header Action)
- Tombol **Update Rekening / Metode Bayar** di header untuk menyimpan bank/provider, nomor akun/wallet, atas nama, metode (bank/ewallet/paypal/cash), dan catatan pembayaran.

### Alur Pembayaran (Superadmin)
1. Review log berstatus Pending.
2. Gunakan aksi **Set Approved** untuk menyetujui.
3. Gunakan **Tandai Dibayar** (single atau bulk) untuk mengubah status ke Paid; modal akan menampilkan ringkasan metode bayar yang terisi.

### Catatan
- Admin tidak bisa membuat/mengedit log manual; hanya superadmin yang dapat membuat/mengedit/hapus.
- Log pending badge di menu menampilkan jumlah pending saat ini.

---

## 🕷️ Scraping System

### Apa & Sumber

Sistem sinkronisasi terjadwal/manual untuk menarik metadata atau episode dari sumber yang didukung:
- **MyAnimeList**
- **AnimeSail**
- **Both** (kombinasi)

### Scrape Configs

1. Buka **Scrape Configs**.
2. Kolom utama: Name, Source (MAL/AnimeSail/Both), Sync Type (Metadata/Episodes/Both), Active, Auto Sync, Max Items, Last Run.
3. Buat/ubah config:
    - **Name** *(wajib)*.
    - **Source**: `myanimelist`, `animesail`, atau `both`.
    - **Sync Type**: `metadata`, `episodes`, atau `both`.
    - **Active** toggle untuk mengaktifkan config.
    - **Auto Sync** toggle + **Schedule (Cron)** jika auto sync diaktifkan.
    - **Max Items Per Sync** (1–100) untuk membatasi batch.
    - **Filters (key/value)** opsional.
4. **Run Sync** action akan memanggil `php artisan anime:sync` dengan parameter config; `last_run_at` diupdate dan notifikasi sukses dikirim.

### Scrape Logs

1. Buka **Scrape Logs**.
2. Kolom: Config, Source (MAL/AnimeSail), Type (Metadata/Episodes/Full), Status (Running/Success/Failed/Partial), Processed/Created/Updated/Failed, Started, Completed.
3. Filter berdasarkan **Source** dan **Status**. Aksi: View, Delete. Sorting default berdasarkan waktu terbaru.

### Troubleshooting Ringkas
- **Running terlalu lama**: kurangi `Max Items`, matikan `Auto Sync` sementara, cek queue worker.
- **Failed/Partial**: buka log detail, cek pesan error, coba turunkan limit atau ganti sumber.
- **Tidak ada data baru**: pastikan config aktif, sumber benar, dan limit > 0.

---

## 🔄 MAL Sync

Gunakan fitur MAL Sync untuk mengimpor data anime dari MyAnimeList (via Jikan API) ke database Anda.

### Prasyarat
- Internet aktif (Jikan API membutuhkan koneksi)
- `.env` disarankan:
   - `QUEUE_CONNECTION=database` (atau `sync` untuk eksekusi langsung)
   - `CACHE_DRIVER=file`
   - `FILESYSTEM_DISK=public`
- Buat symlink storage agar poster tampil:

```bash
php artisan storage:link
```

- Jalankan queue worker (jika menggunakan `QUEUE_CONNECTION=database`):

```bash
php artisan queue:work --queue=default --tries=3
```

Biarkan worker berjalan di terminal terpisah saat proses sync.

### Cara Pakai di Panel
1. Buka menu: Sidebar → **MAL Sync** (halaman `mal-sync`).
2. Pilih **Sync Type**:
   - **Top**: Top anime by rating.
   - **Seasonal**: Pilih musim (Winter/Spring/Summer/Fall/All) + Year (opsional, default tahun berjalan).
   - **Search**: Cari berdasarkan judul.
   - **Search by MAL ID**: Masukkan ID anime spesifik (dari URL MAL).
3. Parameter lain:
   - **Limit** (opsional): kosongkan untuk ambil semua dari endpoint; isi angka kecil (10-25) untuk uji coba.
   - **Download Poster Images**: ON untuk unduh poster ke storage lokal; OFF untuk lebih cepat.
4. Klik **Start Sync**. Proses dijalankan via job `MalSyncJob`; progress & log disimpan di cache dan akan tampil real-time di panel.
5. Setelah status `done`, periksa **Anime** untuk hasil import. Jika `error`, lihat notifikasi dan ulangi dengan limit lebih kecil.

### Tips Penggunaan
- Mulai dengan `Limit = 10` dulu untuk uji coba.
- Untuk seasonal, jika ingin semua musim dalam setahun, pilih Season = `All` dan isi Year.
- ON image download akan menyimpan file di `public/storage/posters/...`.

### Troubleshooting
- **Stuck di "Syncing... 0%"**:
   - Pastikan queue worker jalan: `php artisan queue:work`
   - Atau set `.env` ke `QUEUE_CONNECTION=sync` lalu reload panel untuk eksekusi langsung
   - Clear cache: `php artisan cache:clear`
- **Gagal ambil data (API error/timeout)**:
   - Turunkan `Limit`, coba lagi
   - Cek koneksi internet, Jikan rate limit (maks ~3 request/detik)
- **Poster tidak muncul**:
   - Jalankan `php artisan storage:link`
   - Pastikan `FILESYSTEM_DISK=public` dan folder `public/storage/posters/` terbentuk

---

## 📥 Import dari HTML

Gunakan menu **Import dari HTML** (Tools) untuk memasukkan data dari file HTML yang sudah dimuat lengkap.

### Kapan Dipakai
- Saat memiliki dump HTML dari situs sumber dan ingin mengisi anime/episode/server tanpa scraping langsung.

### Langkah Umum
1. Buka **Tools → Import dari HTML**.
2. Pilih halaman **Import**.
3. Upload/paste HTML sesuai instruksi di halaman (gunakan HTML yang sudah fully rendered agar data lengkap).
4. Jalankan import. Sistem akan mencoba membuat Anime, Episodes, dan Video Servers berdasarkan parser internal.
5. Cek hasil di menu **Anime** / **Episodes**. Jika diperlukan, lengkapi poster/genre secara manual.

### Catatan
- Simpan backup HTML untuk rerun bila diperlukan.
- Pastikan slug unik; sesuaikan manual bila ada konflik.

---

## 📅 Jadwal Tayang (Schedules)

### Melihat Jadwal Tayang

1. Klik **"Schedules"** di sidebar
2. Tabel menampilkan:
   - **Anime** - Anime yang dijadwalkan
   - **Day of Week** - Hari tayang
   - **Broadcast Time** - Jam tayang
   - **Is Active** - Status aktif

### Menambah Jadwal Baru

1. **Klik "New Schedule"**

2. **Isi Form:**
   - **Anime** *(Required)* - Pilih anime
    - **Day of Week** *(Required)* - Hari tayang (Senin–Minggu)
    - **Broadcast Time** - Jam tayang (format 24 jam, 00:00–23:59)
    - **Tanggal Episode Berikutnya** - Opsional, catat tanggal episode selanjutnya.
    - **Timezone** - Pilih WIB/WITA/WIT atau JST.
    - **Is Active** - Toggle aktif/nonaktif.
    - **Notes** - Catatan tambahan (opsional).

3. **Klik "Create"**

### Menggunakan Time Picker

- **Hour Step:** Klik panah atau ketik jam
- **Minute Step:** Klik panah atau ketik menit
- **Format:** 24 jam (00:00 - 23:59)

### Tips Jadwal Tayang

**Timezone:**
- Gunakan timezone lokal (WIB/WITA/WIT) atau JST sesuai sumber.
- Catat timezone dengan benar jika jadwal dipakai untuk reminder user.

**Update Otomatis:**
- Jadwal dapat difilter per hari dan status aktif di tabel.
- Gunakan kolom "Episode Berikutnya" untuk mencatat siklus rilis.

---

## 🎄 Holiday Settings

Halaman **Holiday Settings** (Tools) untuk mengaktifkan tema musiman di sisi pengguna.

- Toggle **Christmas Mode** untuk efek salju.
- Toggle **New Year Mode** untuk efek kembang api.
- Simpan untuk menulis nilai ke `SiteSetting` (kunci `christmas_mode` dan `new_year_mode`).

---

## 🎨 Customization & Settings

### Theme & Appearance

**Dark Mode:**
- Toggle di top bar (icon bulan/matahari)
- Otomatis save preference

**Sidebar:**
- Collapse/Expand dengan tombol hamburger
- Width bisa disesuaikan (drag)

**Table Density:**
- Comfortable (default)
- Compact (lebih banyak data)
- Ubah di table settings

### Notifications

**Email Notifications:**
- Scraping selesai
- Error critical
- New user registration

**In-App Notifications:**
- Icon bell di top bar
- Badge untuk unread count
- Click untuk lihat detail

### User Preferences

**Profile Settings:**
1. Click avatar di top bar
2. Select "Profile"
3. Edit:
   - Name
   - Email
   - Password
   - Avatar
   - Bio

**Logout:**
- Click avatar → Logout
- Atau `/admin/logout`

---

## 🔧 Tips & Troubleshooting

### Tips Umum

**Performance:**
- Gunakan pagination untuk table besar
- Filter data sebelum export
- Clear cache jika UI lambat

**Data Entry:**
- Gunakan keyboard shortcuts (Ctrl+S untuk save)
- Tab untuk navigasi antar field
- Enter untuk submit form

**Backup:**
- Export data secara berkala
- Backup database setiap minggu
- Simpan file export di cloud

### Common Issues

#### Issue 1: Cannot Access Admin Panel

**Symptoms:** 403 Forbidden saat akses `/admin`

**Solutions:**
```bash
# Cek apakah user adalah admin
php list_users.php

# Jadikan user sebagai admin
php quick_make_admin.php

# Atau buat admin baru
php create_admin.php
```

#### Issue 2: Upload Poster Failed

**Symptoms:** Error saat upload gambar

**Solutions:**
- Cek ukuran file (max 2MB)
- Cek format (JPG/PNG/WebP only)
- Cek permission folder `storage/app/public`
- Run: `php artisan storage:link`

#### Issue 3: Slug Conflict

**Symptoms:** "Slug already exists" error

**Solutions:**
- Edit slug manual agar unique
- Tambah suffix angka: `naruto-1`, `naruto-2`
- Delete anime lama jika duplicate

#### Issue 4: Relationship Error

**Symptoms:** "Foreign key constraint fails"

**Solutions:**
- Pastikan anime exist sebelum create episode
- Pastikan genre exist sebelum assign ke anime
- Jangan hapus parent jika masih ada child

#### Issue 5: Scraping Timeout

**Symptoms:** Scraping stuck atau timeout

**Solutions:**
- Kurangi max pages
- Tingkatkan PHP timeout: `set_time_limit(300)`
- Gunakan queue untuk long-running tasks
- Check website sumber masih accessible

### Performance Optimization

**Database:**
```bash
# Optimize tables
php artisan db:optimize

# Clear query cache
php artisan cache:clear
```

**Files:**
```bash
# Clear view cache
php artisan view:clear

# Clear route cache
php artisan route:clear

# Clear config cache
php artisan config:clear
```

**Assets:**
```bash
# Rebuild assets
npm run build

# Or for development
npm run dev
```

---

## 📊 Best Practices

### Content Management

1. **Struktur Nama:**
   - Title: Sesuai official (English/Japanese)
   - Slug: lowercase-with-dashes
   - Episode: Konsisten (S01E01 atau Episode 1)

2. **Image Guidelines:**
   - Poster: 300x450px (2:3 ratio)
   - Cover: 1920x1080px (16:9 ratio)
   - Quality: High (80-90%)
   - Format: WebP (optimal) atau JPG

3. **Content Quality:**
   - Synopsis: 100-300 karakter
   - Description: Jelas dan menarik
   - Rating: Sesuai sumber (MAL/AniList)
   - Genre: Max 5-6 genre per anime

### Security

1. **Password Policy:**
   - Min 12 karakter
   - Kombinasi huruf, angka, simbol
   - Ganti password setiap 3 bulan
   - Jangan share akun admin

2. **Access Control:**
   - Minimal privilege principle
   - Review admin list regularly
   - Revoke akses yang tidak perlu

3. **Data Protection:**
   - Backup database weekly
   - Export critical data
   - Test restore procedure
   - Keep Laravel & packages updated

### Workflow

1. **Adding New Anime:**
   ```
   1. Create anime (basic info)
   2. Upload poster
   3. Assign genres
   4. Create episodes
   5. Add video servers
   6. Set schedule (if ongoing)
   7. Publish to homepage
   ```

2. **Weekly Routine:**
   ```
   Monday:
   - Check scraping logs
   - Review new episodes
   - Update ongoing anime
   
   Wednesday:
   - Backup database
   - Clear cache
   - Check error logs
   
   Friday:
   - Add new releases
   - Update schedules
   - Review user reports
   ```

3. **Monthly Tasks:**
   ```
   - Update completed anime status
   - Archive old scrape logs
   - Review analytics
   - Update documentation
   - Security audit
   ```

---

## 🚀 Keyboard Shortcuts

### Global
- `Ctrl + K` - Global search
- `Ctrl + /` - Toggle sidebar
- `Esc` - Close modal/dialog

### Table
- `Ctrl + F` - Focus search
- `Arrow Up/Down` - Navigate rows
- `Enter` - Open selected row

### Form
- `Ctrl + S` - Save form
- `Tab` - Next field
- `Shift + Tab` - Previous field
- `Ctrl + Enter` - Submit form

---

## 📚 Resources

### Documentation
- Laravel: https://laravel.com/docs
- Filament: https://filamentphp.com/docs
- Livewire: https://laravel-livewire.com

### Support
- GitHub Issues: [Link ke repo]
- Discord Community: [Link]
- Email: admin@nipnime.com

### Tools
- phpMyAdmin: http://localhost/phpmyadmin
- Laravel Telescope: http://localhost:8000/telescope (if installed)
- Laravel Horizon: http://localhost:8000/horizon (if installed)

---

## ✅ Quick Reference

### Must-Know URLs
```
Admin Panel:    http://localhost:8000/admin
Login:          http://localhost:8000/admin/login
Dashboard:      http://localhost:8000/admin
Logout:         http://localhost:8000/admin/logout
```

### Default Credentials
```
Email:          admin@example.com
Password:       password
```

### Important Commands
```bash
# Create admin
php create_admin.php

# Make existing user admin
php quick_make_admin.php

# List all users
php list_users.php

# Check admin status
php debug_admin.php

# Run migrations
php artisan migrate

# Clear all cache
php artisan optimize:clear

# Create storage link
php artisan storage:link
```

### Database Info
```
Host:           localhost
Port:           3306 (MySQL) / 5432 (PostgreSQL)
Database:       web_anime
Username:       root
Password:       (empty for XAMPP)
```

---

## 🎉 Conclusion

Panel admin Filament menyediakan interface yang powerful dan user-friendly untuk mengelola website anime Anda. Dengan mengikuti panduan ini, Anda dapat:

✅ Mengelola anime, episode, dan genre dengan mudah
✅ Mengatur user dan permission
✅ Menggunakan sistem scraping otomatis
✅ Monitor dan maintain website dengan efisien
✅ Troubleshoot masalah umum

**Happy Managing! 🚀**

---

*Dokumen ini terakhir diupdate: 2 Januari 2026*
*Versi Panel: Filament v3.x*
*Framework: Laravel 11.x*
