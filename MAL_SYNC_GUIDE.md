# MyAnimeList Sync - User Guide

## 🎯 Overview

Sistem sinkronisasi otomatis anime dari MyAnimeList menggunakan Jikan API v4. Mendukung sync seasonal anime, top anime, dan pencarian spesifik.

## 📋 Features

✅ **Seasonal Anime Sync** - Sync anime berdasarkan musim (winter/spring/summer/fall)
✅ **Top Anime Sync** - Sync anime dengan rating tertinggi
✅ **Search Sync** - Cari dan sync anime spesifik
✅ **Auto Image Download** - Download poster otomatis ke storage
✅ **Rate Limiting** - Respect Jikan API limit (3 req/second)
✅ **Batch Processing** - Sync multiple anime sekaligus
✅ **Progress Bar** - Visual progress saat sync
✅ **Error Handling** - Handle API errors gracefully

## 🚀 Command Usage

### 1. **Sync Seasonal Anime**

Sync anime dari musim tertentu:

```bash
# Sync anime musim ini (default: 25 anime)
php artisan anime:sync-mal --type=seasonal

# Sync anime musim winter 2025
php artisan anime:sync-mal --type=seasonal --season=winter --year=2025

# Sync lebih banyak anime (contoh: 50)
php artisan anime:sync-mal --type=seasonal --limit=50

# Sync tanpa download gambar (lebih cepat)
php artisan anime:sync-mal --type=seasonal --no-images
```

**Seasons:**
- `winter` (Januari - Maret)
- `spring` (April - Juni)
- `summer` (Juli - September)
- `fall` (Oktober - Desember)

---

### 2. **Sync Top Anime**

Sync anime dengan rating tertinggi dari MAL:

```bash
# Sync top 25 anime
php artisan anime:sync-mal --type=top

# Sync top 10 anime saja
php artisan anime:sync-mal --type=top --limit=10

# Sync top 50 anime dengan gambar
php artisan anime:sync-mal --type=top --limit=50
```

---

### 3. **Search & Sync Specific Anime**

Cari anime berdasarkan judul lalu sync:

```bash
# Cari dan sync "Naruto"
php artisan anime:sync-mal --type=search --search="Naruto"

# Cari dan sync "One Piece" (max 5 hasil)
php artisan anime:sync-mal --type=search --search="One Piece" --limit=5

# Cari tanpa download gambar
php artisan anime:sync-mal --type=search --search="Demon Slayer" --no-images
```

---

## 📊 Output Example

```
Starting MyAnimeList sync (search)...

Searching for: Naruto...
 3/3 [============================] 100%

+---------------------+-------+
| Metric              | Value |
+---------------------+-------+
| Total Processed     | 3     |
| Successfully Synced | 3     |
| Failed              | 0     |
+---------------------+-------+

✓ Successfully synced anime:
  - Naruto (ID: 7)
  - Naruto: Shippuuden (ID: 8)
  - Boruto: Naruto Next Generations (ID: 9)
```

---

## ⚙️ What Gets Synced?

Setiap anime yang di-sync akan menyimpan data berikut:

| Field | Description | Example |
|-------|-------------|---------|
| `title` | Judul anime | "Attack on Titan" |
| `slug` | URL-friendly slug | "attack-on-titan" |
| `synopsis` | Deskripsi/sinopsis | "..." |
| `poster_image` | Path ke poster image | "posters/attack-on-titan-123.jpg" |
| `rating` | Rating 0-10 | 9.0 |
| `status` | Status (Completed/Ongoing) | "Completed" |
| `type` | Type (TV/Movie/ONA) | "TV" |
| `release_year` | Tahun rilis | 2013 |
| `genres` | Genre (many-to-many) | Action, Drama, Fantasy |

---

## 🔄 Auto-Update Existing Anime

Jika anime sudah ada di database (by slug atau title), sistem akan **update** data yang sudah ada, bukan membuat duplicate.

---

## ⚡ Performance Tips

### 1. **Skip Image Downloads**
Jika ingin sync cepat (testing), skip download gambar:
```bash
php artisan anime:sync-mal --type=top --limit=50 --no-images
```

### 2. **Limit Results**
Jangan sync terlalu banyak sekaligus (API rate limit):
```bash
# Good: 25-50 anime
php artisan anime:sync-mal --type=top --limit=25

# Avoid: 100+ anime (very slow + risk API throttle)
```

### 3. **Rate Limiting**
Sistem sudah otomatis handle rate limiting (350ms delay antar request).

---

## 🛠️ Programmatic Usage

Anda juga bisa menggunakan service secara langsung di kode:

```php
use App\Services\MyAnimeListService;

// Di controller atau service
$malService = app(MyAnimeListService::class);

// Sync seasonal anime
$result = $malService->syncSeasonalAnime(2025, 'winter', 10, true);

// Sync top anime
$result = $malService->syncTopAnime(20, true);

// Search & sync
$animeList = $malService->searchAnime('Naruto', 5);
$result = $malService->batchSync($animeList, true);

// Check results
if ($result['success_count'] > 0) {
    echo "Synced {$result['success_count']} anime!";
}
```

---

## 🐛 Troubleshooting

### Error: "Rate limit exceeded"
**Solution:** API sedang throttle. Tunggu 1-2 menit lalu coba lagi.

### Error: "Failed to download image"
**Solution:** Image URL tidak valid atau network error. Sync akan tetap berhasil, tapi tanpa gambar.

### Error: "No anime found"
**Solution:** Query search tidak ditemukan. Coba keyword lain.

### Anime tidak muncul di homepage
**Solution:** 
1. Pastikan anime punya gambar (check di admin)
2. Set anime sebagai "Featured" di admin panel

---

## 📅 Scheduled Sync (Optional)

Anda bisa setup automated sync di `app/Console/Kernel.php`:

```php
protected function schedule(Schedule $schedule)
{
    // Sync seasonal anime setiap hari jam 2 pagi
    $schedule->command('anime:sync-mal --type=seasonal --limit=25')
             ->dailyAt('02:00');
    
    // Sync top anime setiap minggu
    $schedule->command('anime:sync-mal --type=top --limit=50')
             ->weekly();
}
```

---

## 🔗 API Reference

Service ini menggunakan **Jikan API v4** (unofficial MyAnimeList API):
- Base URL: `https://api.jikan.moe/v4`
- Rate Limit: 3 requests/second
- Documentation: https://docs.api.jikan.moe/

---

## ✅ Best Practices

1. **Start Small**: Test dengan `--limit=5` dulu
2. **Use Specific Searches**: Lebih akurat daripada seasonal sync
3. **Monitor Storage**: Image downloads akan memakan storage
4. **Check Logs**: Cek `storage/logs/laravel.log` jika ada error
5. **Respect API**: Jangan spam request, use rate limiting

---

## 📝 Examples

### Example 1: Setup Database Awal
```bash
# Sync top 25 anime populer
php artisan anime:sync-mal --type=top --limit=25
```

### Example 2: Update Seasonal Content
```bash
# Sync anime musim ini
php artisan anime:sync-mal --type=seasonal
```

### Example 3: Add Specific Anime
```bash
# Cari dan tambah anime tertentu
php artisan anime:sync-mal --type=search --search="Jujutsu Kaisen"
```

---

**Ready to sync!** 🚀
