# Update: Filter Tahun dan Perbaikan Bug Genre

## Perubahan yang Dibuat

### 1. Perbaikan Bug Genre ❌ → ✅
**File**: `app/Http/Controllers/HomeController.php`

**Bug Sebelumnya**:
```php
// BUG: OR condition tidak di-wrap, menyebabkan filter lain tidak bekerja
if (request('search')) {
    $search = request('search');
    $query->where('title', 'like', "%{$search}%")
        ->orWhere('synopsis', 'like', "%{$search}%"); // ← OR ini break filter lain!
}

if (request('genre')) {
    $query->whereHas('genres', fn ($q) => $q->where('id', request('genre')));
}
```

**Contoh Masalah**:
- User search "naruto" + filter genre "Action"
- Query yang dihasilkan: `WHERE title LIKE '%naruto%' OR synopsis LIKE '%naruto%' AND genre = 'Action'`
- Hasil: Menampilkan semua anime yang judulnya mengandung "naruto" (ignore genre filter)

**Solusi**:
```php
// FIX: Wrap OR condition dalam closure
if (request('search')) {
    $search = request('search');
    $query->where(function($q) use ($search) {
        $q->where('title', 'like', "%{$search}%")
          ->orWhere('synopsis', 'like', "%{$search}%");
    });
}

if (request('genre')) {
    $query->whereHas('genres', fn ($q) => $q->where('genres.id', request('genre')));
}
```

Query yang dihasilkan sekarang: `WHERE (title LIKE '%naruto%' OR synopsis LIKE '%naruto%') AND genre = 'Action'` ✅

### 2. Penambahan Filter Baru 🎯

#### Filter Status
- **Pilihan**: Semua Status / Ongoing / Completed
- **Field**: `status` (enum)

#### Filter Tipe
- **Pilihan**: Semua Tipe / TV / Movie / ONA
- **Field**: `type` (enum)

#### Filter Tahun
- **Pilihan**: Semua Tahun / 2020 / 2021 / dst...
- **Field**: `release_year` (integer)
- **Source**: Data tahun dari database (dynamic)

### 3. Perbaikan Pagination
**Perubahan**:
```php
// Sebelum
->paginate(12);

// Sesudah (preserve filter saat paging)
->paginate(12)
->appends(request()->except('page'));
```

Sekarang saat user klik halaman 2, filter tidak hilang.

### 4. Update View
**File**: `resources/views/search.blade.php`

Ditambahkan:
- Dropdown filter Status
- Dropdown filter Tipe
- Dropdown filter Tahun (dynamic dari database)

Diperbarui:
- Clear filter button sekarang support semua filter baru

## Testing

### Test Bug Genre Fix
```bash
php test_genre_fix.php
```

**Hasil**:
```
Test 1: Filter Genre Saja
-------------------------
Genre: Action
Found: 3 anime
  - Dorohedoro
  - Darwin's Game
  - Itai no wa Iya nanode Bougyoryoku ni Kyokufuri Shitai to Omoimasu

Test 2: Search Term + Genre (Previously Buggy)
-----------------------------------------------
Search: 'a' + Genre: Action
Found: 3 anime
  - Dorohedoro
  - Darwin's Game
  - Itai no wa Iya nanode Bougyoryoku ni Kyokufuri Shitai to Omoimasu

✓ Bug genre sudah diperbaiki!
```

### Test di Browser
1. Akses: `http://127.0.0.1:8000/search`
2. Test kombinasi filter:
   - ✅ Search + Genre
   - ✅ Genre + Status
   - ✅ Genre + Type + Year
   - ✅ Search + Genre + Status + Type + Year

## Files Modified
- ✅ `app/Http/Controllers/HomeController.php`
- ✅ `resources/views/search.blade.php`

## Keuntungan
✅ Bug genre sudah diperbaiki - filter kombinasi sekarang bekerja
✅ Filter lebih lengkap (Status, Tipe, Tahun)
✅ Filter tahun dynamic dari database
✅ Pagination mempertahankan filter
✅ UI lebih user-friendly dengan lebih banyak opsi filter

## Catatan
- Filter tahun mengambil data dari kolom `release_year` di database
- Jika ada anime tanpa tahun (NULL), tidak akan muncul di dropdown
- Semua filter bersifat opsional dan bisa dikombinasikan
