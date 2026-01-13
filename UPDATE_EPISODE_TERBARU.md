# Update: Episode Terbaru - Auto Update Ketika Video Server Ditambahkan

## Perubahan yang Dibuat

### 1. HomeController.php
**File**: `app/Http/Controllers/HomeController.php`

**Perubahan**:
- Mengganti logika `latestEpisodes` untuk menampilkan **semua episode** yang baru mendapat update video server
- Sebelumnya: Hanya menampilkan 1 episode per anime (anime-based)
- Sekarang: Menampilkan setiap episode yang baru diupdate (episode-based)

**Cara Kerja**:
1. Query mengambil episode berdasarkan `MAX(video_servers.updated_at)` - episode dengan video server terbaru
2. Setiap kali video server ditambahkan atau diupdate, timestamp `updated_at` berubah
3. Episode dengan video server terbaru otomatis muncul di section "Episode Terbaru"
4. Mendukung multiple episode dari anime yang sama jika keduanya baru diupdate

**Hasil**:
- Episode Terbaru akan otomatis update ketika:
  - Video server baru ditambahkan ke episode
  - Video server existing diupdate
- Menampilkan maksimal 12 episode terbaru
- Diurutkan berdasarkan waktu update video server (terbaru di atas)

### 2. Contoh Tampilan
Jika anime "Jibaku Shounen Hanako-kun" memiliki:
- Episode 1 dengan video server diupdate jam 22:51
- Episode 2 dengan video server diupdate jam 22:46

Dan anime "Haikyuu!! To the Top":
- Episode 1 dengan video server diupdate jam 22:56

Maka urutan di homepage:
1. Haikyuu!! To the Top EP1 (22:56) ← Paling baru
2. Jibaku Shounen Hanako-kun EP1 (22:51)
3. Jibaku Shounen Hanako-kun EP2 (22:46)

### 3. Database Query
```php
// Mengambil episode dengan video server terbaru
DB::table('episodes')
    ->join('animes', 'episodes.anime_id', '=', 'animes.id')
    ->join('video_servers', 'episodes.id', '=', 'video_servers.episode_id')
    ->where('video_servers.is_active', true)
    ->select(
        'episodes.id as episode_id',
        'animes.id as anime_id',
        DB::raw('MAX(video_servers.updated_at) as latest_server_update')
    )
    ->groupBy('episodes.id', 'animes.id')
    ->orderBy('latest_server_update', 'desc')
    ->limit(12)
```

### 4. Testing
Untuk test query:
```bash
php check_unique_episodes.php
```

Untuk test controller logic:
```bash
php test_controller_logic.php
```

Untuk simulasi update video server:
```bash
php update_servers.php
```

## Keuntungan
✅ Episode terbaru otomatis terupdate tanpa perlu manual refresh
✅ Menampilkan semua anime yang baru mendapat update video server
✅ Mendukung multiple episode dari anime yang sama
✅ Sorting yang akurat berdasarkan waktu update video server
✅ Performa optimal dengan eager loading dan efficient query

## View yang Terpengaruh
- `resources/views/home.blade.php` - Section "Episode Terbaru"
- Tidak ada perubahan di view, hanya di controller logic

## Catatan
- Video server dengan `is_active = false` tidak akan ditampilkan
- Maksimal 12 episode ditampilkan di homepage
- Timestamp `updated_at` pada `video_servers` table menjadi kunci sorting
