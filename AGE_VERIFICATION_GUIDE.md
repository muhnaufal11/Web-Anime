# Age Verification System (18+) - NIPNIME

## Overview
Sistem verifikasi umur untuk konten dewasa (genre Hentai/Ecchi). User yang berumur di bawah 18 tahun atau belum login tidak bisa melihat poster dan menonton konten dewasa.

## Fitur

### 1. Blur Poster
Konten dewasa akan menampilkan poster yang di-blur dengan overlay "18+ Konten Dewasa" untuk:
- User yang belum login (guest)
- User yang sudah login tapi belum mengisi tanggal lahir
- User yang berumur di bawah 18 tahun

### 2. Block Video Playback
Middleware `adult.content` akan memblokir akses ke halaman watch untuk konten dewasa jika user tidak memenuhi syarat.

## Genre yang Dianggap Dewasa (18+)
Definisi di `app/Models/Anime.php`:
```php
public const ADULT_GENRES = ['hentai', 'ecchi'];
```

## Method Penting di Model

### Anime.php
```php
// Cek apakah anime adalah konten dewasa
public function isAdultContent(): bool

// Cek apakah user boleh melihat konten
public function canUserView(): bool

// Cek apakah poster harus di-blur
public function shouldBlurPoster(): bool
```

### User.php
```php
// Cek apakah user sudah 18+
public function isAdult(): bool

// Dapatkan umur user
public function getAge(): ?int
```

## Middleware

### CheckAdultContent Middleware
Lokasi: `app/Http/Middleware/CheckAdultContent.php`

Registered di `app/Http/Kernel.php`:
```php
'adult.content' => \App\Http\Middleware\CheckAdultContent::class,
```

Applied di route `watch`:
```php
Route::get('/watch/{episode}', [WatchController::class, 'show'])
    ->name('watch')
    ->middleware('adult.content');
```

## Halaman yang Sudah Di-update

1. **home.blade.php**
   - Continue Watching section
   - Latest Episodes section  
   - Trending/Popular section

2. **detail.blade.php**
   - Hero background image
   - Main poster
   - Episode list (locked icons for adult content)
   - Watch button disabled for underage

3. **latest-episodes.blade.php**
   - Episode cards with blur

4. **schedule.blade.php**
   - Schedule cards with blur

5. **search.blade.php**
   - Search results with blur

6. **watch-history.blade.php**
   - History cards with blur

## Cara Kerja

### Di View (Blur Poster)
```blade
@php $shouldBlur = $anime->shouldBlurPoster(); @endphp

<img src="{{ $poster }}" 
     class="... {{ $shouldBlur ? 'blur-xl' : '' }}">

@if($shouldBlur)
<div class="absolute inset-0 flex items-center justify-center bg-black/60">
    <span class="text-3xl font-black text-red-500">18+</span>
    <span class="text-xs">Konten Dewasa</span>
</div>
@endif
```

### Di Middleware (Block Video)
```php
// Not logged in
if (!$user) {
    return redirect()->route('auth.login')
        ->with('error', 'Konten ini hanya untuk pengguna 18+...');
}

// No birth date
if (!$user->birth_date) {
    return redirect()->route('profile.show')
        ->with('error', 'Lengkapi tanggal lahir di profil Anda.');
}

// Under 18
if (!$user->isAdult()) {
    return redirect()->back()
        ->with('error', 'Konten ini hanya untuk pengguna 18 tahun ke atas.');
}
```

## Testing

1. **Test dengan Guest User**
   - Buka halaman home/search
   - Cari anime dengan genre Hentai
   - Poster harus blur dengan overlay 18+
   - Klik harus menampilkan alert

2. **Test dengan User Under 18**
   - Login dengan user yang birth_date < 18 tahun
   - Poster masih blur
   - Tidak bisa menonton video

3. **Test dengan User 18+**
   - Login dengan user yang birth_date >= 18 tahun
   - Poster tidak blur
   - Badge 18+ muncul di pojok
   - Bisa menonton video

## Menambah Genre Dewasa Baru

Edit konstanta di `app/Models/Anime.php`:
```php
public const ADULT_GENRES = ['hentai', 'ecchi', 'new-genre-slug'];
```

Pastikan slug genre sesuai dengan yang ada di database.

## Clear Cache Setelah Perubahan

```bash
docker exec nipnime-app php artisan cache:clear
docker exec nipnime-app php artisan view:clear
docker exec nipnime-app php artisan config:clear
```
