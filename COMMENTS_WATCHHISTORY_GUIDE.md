# 💬 Comments & 📺 Watch History - Panduan Fitur

## 🎯 Ringkasan Implementasi

Sistem komentar dan riwayat tontonan telah berhasil diimplementasikan dengan fitur lengkap:

### ✅ Fitur Komentar
- ✅ Komentar pada anime (anime-level comments)
- ✅ Sistem reply/balasan bersarang (nested replies)
- ✅ User hanya bisa menghapus komentar sendiri
- ✅ Menampilkan nama user dan timestamp
- ✅ Pagination untuk komentar
- ✅ Validasi maksimal 1000 karakter
- ✅ Login required untuk berkomentar

### ✅ Fitur Watch History (Lanjutkan Tonton)
- ✅ Auto tracking saat user menonton episode
- ✅ Progress tracking dengan JavaScript (setiap 10 detik)
- ✅ Progress bar visual pada card anime
- ✅ Status "Selesai" jika sudah nonton sampai habis
- ✅ Tampil di homepage untuk user yang login
- ✅ Menampilkan 6 anime terakhir yang ditonton
- ✅ Menampilkan persentase progres dan waktu terakhir nonton

---

## 📁 File yang Dimodifikasi/Dibuat

### 1. Database Migrations
**File:**
- `database/migrations/*_create_comments_table.php`
- `database/migrations/*_create_watch_history_table.php`

**Schema Comments:**
```php
- id
- user_id (FK ke users)
- anime_id (FK ke animes)
- episode_id (nullable, FK ke episodes)
- parent_id (nullable, untuk replies)
- comment (text, max 1000 chars)
- timestamps
```

**Schema Watch History:**
```php
- id
- user_id (FK ke users)
- anime_id (FK ke animes)
- episode_id (FK ke episodes)
- progress (integer, dalam detik)
- completed (boolean, default false)
- last_watched_at (timestamp)
- UNIQUE(user_id, episode_id)
```

### 2. Models
**File:** `app/Models/Comment.php`
```php
Relationships:
- belongsTo User
- belongsTo Anime
- belongsTo Episode (nullable)
- belongsTo Comment (parent)
- hasMany Comments (replies)

Scopes:
- scopeParentOnly() // Hanya ambil top-level comments
```

**File:** `app/Models/WatchHistory.php`
```php
Relationships:
- belongsTo User
- belongsTo Anime
- belongsTo Episode

Casts:
- completed: boolean
- last_watched_at: datetime
```

### 3. Controllers

**File:** `app/Http/Controllers/CommentController.php`
```php
Methods:
- store() // POST create comment/reply
- destroy() // DELETE hapus comment (owner only)

Validation:
- anime_id: required|exists
- episode_id: nullable|exists
- parent_id: nullable|exists
- comment: required|string|max:1000
```

**File:** `app/Http/Controllers/WatchController.php`
```php
Methods:
- show() // Auto track watch history on page load
- updateProgress() // AJAX endpoint untuk save progress

Features:
- Creates/updates WatchHistory on episode view
- Saves progress setiap 10 detik via JavaScript
- Marks completed saat video selesai
```

**File:** `app/Http/Controllers/HomeController.php`
```php
Added:
- Continue Watching query untuk auth users
- Load 6 latest watched episodes
- Eager load anime, episode, genres
- Order by last_watched_at DESC
```

**File:** `app/Http/Controllers/DetailController.php`
```php
Added:
- Load comments untuk anime detail page
- Load only parent comments dengan replies
- Eager load user pada comments dan replies
- Pagination 10 comments per page
```

### 4. Routes
**File:** `routes/web.php`
```php
// Comment routes (auth required)
Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
Route::delete('/comments/{comment}', [CommentController::class, 'destroy'])->name('comments.destroy');

// Watch progress route (auth required)
Route::post('/watch/{episode:slug}/progress', [WatchController::class, 'updateProgress'])->name('watch.progress');
```

### 5. Views

**File:** `resources/views/detail.blade.php`
- ✅ Comment form section (only for auth users)
- ✅ Comments list dengan nested replies
- ✅ Reply button dan form untuk setiap comment
- ✅ Delete button (owner only)
- ✅ User avatar (initial letter circle)
- ✅ Timestamp dengan diffForHumans()
- ✅ Pagination links
- ✅ JavaScript untuk toggle reply form

**File:** `resources/views/home.blade.php`
- ✅ "Lanjutkan Tonton" section (only for auth users)
- ✅ Progress bar visual pada card
- ✅ Status "Selesai" atau persentase progress
- ✅ Timestamp "x jam yang lalu"
- ✅ Purple theme untuk continue watching (berbeda dari episode terbaru)

**File:** `resources/views/watch.blade.php`
- ✅ JavaScript untuk track video progress
- ✅ Auto save setiap 10 detik
- ✅ Save on video ended
- ✅ Save on page unload
- ✅ Support untuk video HTML5 dan iframe

---

## 🚀 Cara Penggunaan

### Untuk User Biasa:

#### 1. Berkomentar
1. Buka halaman detail anime
2. Scroll ke bagian "Komentar"
3. Login jika belum
4. Tulis komentar (max 1000 karakter)
5. Klik "Kirim Komentar"

#### 2. Membalas Komentar
1. Klik tombol "Balas" pada komentar yang ingin dibalas
2. Tulis balasan
3. Klik "Kirim Balasan" atau "Batal" untuk membatalkan

#### 3. Menghapus Komentar
1. Hanya bisa menghapus komentar/balasan sendiri
2. Klik "Hapus" pada komentar
3. Konfirmasi penghapusan

#### 4. Lanjutkan Tonton
1. Login ke akun
2. Tonton episode anime apa saja
3. Progres otomatis tersimpan
4. Buka homepage
5. Lihat section "Lanjutkan Tonton" di atas
6. Klik untuk melanjutkan nonton dari terakhir kali

---

## 🎨 Design Features

### Comments Section
- **Dark Theme**: Gradient dari `#1a1d24` ke `#0f1115`
- **User Avatar**: Circle dengan initial nama (gradient red)
- **Reply Indentation**: Reply dibedakan dengan border-left dan padding
- **Nested Replies**: Support unlimited depth (tapi UI hanya show 2 levels)
- **Hover Effects**: Border glow merah saat hover

### Continue Watching Section
- **Purple Theme**: Berbeda dari episode terbaru (red theme)
- **Progress Bar**: Visual bar di bawah poster
- **Completion Status**: Icon ✓ dan teks "Selesai"
- **Time Indicator**: "x jam yang lalu" untuk last watched
- **Responsive**: Grid 2-3 kolom tergantung screen size

### Watch Progress
- **Auto Save**: Setiap 10 detik otomatis save
- **Smart Tracking**: Hanya save jika progress berubah minimal 5 detik
- **Completion Detection**: Auto mark completed jika nonton sampai 30 detik terakhir
- **Background Saving**: Fetch API dengan catch error silently

---

## 🔧 Technical Details

### Comment System
**Database Optimization:**
- Index pada `anime_id + created_at` untuk sorting cepat
- Index pada `episode_id + created_at`
- Foreign key constraints dengan cascade delete

**Query Optimization:**
- Eager loading: `with(['user', 'replies.user'])`
- Parent only scope: `whereNull('parent_id')`
- Pagination untuk performa

### Watch History System
**Database Optimization:**
- UNIQUE constraint pada `user_id + episode_id` mencegah duplikat
- Index pada `user_id + last_watched_at` untuk homepage query
- Timestamps untuk tracking

**Progress Tracking:**
- Progress disimpan dalam detik (integer)
- Completed boolean untuk status selesai
- Last_watched_at untuk sorting

**JavaScript Implementation:**
- setInterval setiap 10 detik
- beforeunload event untuk save saat keluar
- video.ended event untuk mark completed
- Fetch API dengan error handling

---

## 📊 Database Relationships

```
User
├── hasMany Comments
└── hasMany WatchHistory

Anime
├── hasMany Comments
├── hasMany WatchHistory
└── hasMany Episodes

Episode
├── hasMany Comments
├── hasMany WatchHistory
└── belongsTo Anime

Comment
├── belongsTo User
├── belongsTo Anime
├── belongsTo Episode (nullable)
├── belongsTo Comment (parent, nullable)
└── hasMany Comments (replies)

WatchHistory
├── belongsTo User
├── belongsTo Anime
└── belongsTo Episode
```

---

## ⚠️ Known Limitations

1. **Progress Tracking untuk Iframe Players:**
   - Tidak bisa track progress untuk iframe embed players (YouTube, etc)
   - Hanya bisa mark sebagai "started" tapi tidak track progress
   - Solusi: Gunakan HTML5 video player untuk full tracking

2. **Comment Nesting:**
   - UI hanya menampilkan 2 level (parent + replies)
   - Bisa diperluas tapi untuk UX lebih baik dibatasi

3. **Progress Accuracy:**
   - Progress bar mengasumsikan video 24 menit (1440 detik)
   - Bisa disesuaikan dengan durasi video actual jika tersedia

---

## 🎯 Testing Checklist

### Comments:
- [ ] Create comment as logged-in user
- [ ] Create reply to comment
- [ ] Delete own comment
- [ ] Cannot delete other's comment (403)
- [ ] Cannot comment as guest (redirect to login)
- [ ] Comment validation (max 1000 chars)
- [ ] Pagination works
- [ ] Nested replies display correctly

### Watch History:
- [ ] Watch history created on episode view
- [ ] Progress saved every 10 seconds
- [ ] Progress saved on page close
- [ ] Marked completed when video ends
- [ ] Continue watching shows on homepage
- [ ] Progress bar displays correctly
- [ ] Clicking continue watching goes to correct episode
- [ ] Latest watched appears first

---

## 📝 Future Enhancements

### Comments:
- [ ] Like/dislike komentar
- [ ] Report spam/abuse
- [ ] Edit komentar
- [ ] Rich text formatting
- [ ] Attach images
- [ ] Mention @username
- [ ] Email notifications untuk reply

### Watch History:
- [ ] Export watch history
- [ ] Statistics (total hours watched)
- [ ] Watch history per anime
- [ ] Clear history option
- [ ] Resume from exact timestamp
- [ ] Multiple device sync
- [ ] Watchlist vs Watch History separation

---

## 🐛 Troubleshooting

**Problem:** Comments tidak muncul
- **Solution:** Check apakah user sudah login, cek database ada comments, clear browser cache

**Problem:** Progress tidak tersimpan
- **Solution:** Check browser console untuk error, pastikan route `watch.progress` exists, verify CSRF token

**Problem:** Continue watching section kosong
- **Solution:** Pastikan user sudah login, sudah nonton minimal 1 episode, check `$continueWatching` variable di HomeController

**Problem:** Tidak bisa delete comment
- **Solution:** Verify user adalah owner comment, check `$comment->user_id === auth()->id()`

---

## 🎓 Code Examples

### Create Comment via Code:
```php
use App\Models\Comment;

Comment::create([
    'user_id' => auth()->id(),
    'anime_id' => $animeId,
    'episode_id' => null, // atau $episodeId
    'parent_id' => null, // atau $parentCommentId untuk reply
    'comment' => 'Anime keren banget!',
]);
```

### Create Watch History via Code:
```php
use App\Models\WatchHistory;

WatchHistory::updateOrCreate(
    [
        'user_id' => auth()->id(),
        'episode_id' => $episodeId,
    ],
    [
        'anime_id' => $episode->anime_id,
        'progress' => 300, // 5 menit = 300 detik
        'completed' => false,
        'last_watched_at' => now(),
    ]
);
```

### Query Comments with Replies:
```php
$comments = Comment::where('anime_id', $animeId)
    ->whereNull('episode_id')
    ->parentOnly()
    ->with(['user', 'replies.user'])
    ->latest()
    ->paginate(10);
```

### Query Continue Watching:
```php
$continueWatching = WatchHistory::where('user_id', auth()->id())
    ->with(['episode.anime.genres', 'anime.genres'])
    ->orderBy('last_watched_at', 'desc')
    ->limit(6)
    ->get();
```

---

## ✅ Checklist Implementasi

### Backend:
- [x] Create comments migration
- [x] Create watch_history migration
- [x] Run migrations
- [x] Create Comment model
- [x] Create WatchHistory model
- [x] Create CommentController
- [x] Update WatchController with history tracking
- [x] Update HomeController with continue watching
- [x] Update DetailController with comments loading
- [x] Add routes for comments and progress

### Frontend:
- [x] Add comment form to detail.blade.php
- [x] Add comments list with nested replies
- [x] Add reply form toggle functionality
- [x] Add delete button for own comments
- [x] Add continue watching section to home.blade.php
- [x] Add progress bar visual
- [x] Add watch progress tracking JavaScript
- [x] Add styling for all components

### Testing:
- [ ] Test comment creation
- [ ] Test reply creation
- [ ] Test comment deletion
- [ ] Test watch history creation
- [ ] Test progress tracking
- [ ] Test continue watching display
- [ ] Test pagination
- [ ] Test authorization

---

## 🎉 Conclusion

Sistem komentar dan watch history telah **selesai diimplementasikan** dengan fitur lengkap:

**Comments:** ✅ Create, ✅ Reply, ✅ Delete, ✅ Pagination, ✅ Auth
**Watch History:** ✅ Auto Track, ✅ Progress, ✅ Completed, ✅ Homepage

Semua backend dan frontend sudah siap digunakan. Tinggal testing di browser!
