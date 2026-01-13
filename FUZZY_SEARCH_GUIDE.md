# 🔍 Fuzzy Search & Typo Tolerance Guide

## Overview
Sistem pencarian anime sekarang dilengkapi dengan fitur **Fuzzy Search** yang dapat menangani typo, ejaan salah, dan partial matches. Pengguna dapat menemukan anime bahkan jika mereka mengetik dengan tidak sempurna.

## Features

### 1. **Real-time Autocomplete**
- Ketika user mengetik di search box, akan muncul dropdown suggestions
- Menampilkan 8 hasil terbaik dengan preview poster, tahun, tipe, dan rating
- Support keyboard navigation (↑↓ arrows, Enter, Escape)
- Debounce 300ms untuk performa optimal

### 2. **Typo Tolerance**
Sistem mendukung berbagai jenis kesalahan:
- ✅ Huruf yang salah: "Nuto" → "Naruto"
- ✅ Huruf yang hilang: "Natuo" → "Naruto"
- ✅ Huruf yang berlebih: "Narutoo" → "Naruto"
- ✅ Typo fonetik (SOUNDEX): "Shingeki" → "Shingaki"
- ✅ Partial match: "Narut" → "Naruto"
- ✅ Tanpa spasi: "OnePiece" → "One Piece"

### 3. **Smart Ranking**
Hasil pencarian diurutkan berdasarkan:
1. **Prefix Match** (tertinggi) - Judul dimulai dengan query
2. **Exact Match** - Query ditemukan dalam judul
3. **Similar Text %** - Tingkat kesamaan (0-100%)
4. **Word Match** - Jumlah kata yang cocok
5. **Levenshtein Distance** - Jumlah perubahan karakter
6. **SOUNDEX** - Kesamaan fonetik
7. **Substring Match** - Tanpa spasi

### 4. **"Did You Mean" Suggestion**
Ketika pencarian tidak menemukan hasil, sistem akan menyarankan judul yang paling mirip dengan message "Apakah maksud Anda...?"

## Technical Implementation

### Backend (HomeController.php)

#### API Endpoint
```php
GET /api/search/suggestions?q=query
```
Response:
```json
{
    "suggestions": [
        {
            "id": 1,
            "title": "Naruto",
            "slug": "naruto",
            "poster_image": "...",
            "type": "TV",
            "release_year": 2002,
            "rating": "8.5",
            "url": "..."
        }
    ],
    "count": 1
}
```

#### Main Search Method
`HomeController::search()` - Menangani:
- Exact match (priority pertama)
- Fuzzy search dengan berbagai method jika exact match kosong
- Filtering berdasarkan genre, status, type, tahun
- Scoring dan ranking hasil
- "Did you mean" suggestions

#### Helper Methods
1. **`searchSuggestions()`** - API untuk autocomplete
2. **`findBestMatch(string $searchTerm)`** - Mencari suggestion terbaik
3. **`calculateRelevance(string $title, string $search)`** - Scoring untuk hasil

### Frontend (search.blade.php)

#### JavaScript
- Autocomplete dengan debounce 300ms
- Keyboard navigation (Arrow keys, Enter, Escape)
- Real-time rendering suggestions
- Click outside to close
- Focus management

#### View Features
- Live preview suggestions saat user mengetik
- "Did you mean" blue notification box
- Fuzzy search indicator saat hasil ditemukan
- Improved empty state dengan helpful message
- Suggestions grid di empty state

## Usage Examples

### Example 1: User Search "Narto"
1. User ketik "Narto" di search box
2. API mendeteksi typo dan return "Naruto" dengan score tinggi
3. Autocomplete dropdown menampilkan "Naruto" di posisi #1
4. User klik atau tekan Enter
5. Hasil menampilkan "Naruto" dengan note "Menampilkan hasil pencarian mirip untuk 'Narto'"

### Example 2: User Search "One Pece"
1. Typo "Pece" dan missing space "One Pece"
2. Sistem mencari:
   - "One" dalam database ✓
   - "Pece" partial match "Piece" ✓
   - SOUNDEX match ✓
3. Return "One Piece" di autocomplete
4. Click → full page results

### Example 3: User Search "DBZ"
1. Partial match - "DBZ" adalah shorthand "Dragon Ball Z"
2. Sistem cari "dbz" dalam judul (lowercase)
3. Find match atau similar text
4. Return "Dragon Ball Z" atau related titles

## Configuration

### Scoring Weights (dapat di-adjust)
Di `searchSuggestions()` dan `calculateRelevance()`:
- Prefix match: **1000** (highest)
- Word starts match: **200**
- Contains match: **300**
- SOUNDEX: **60-80**
- Similar text: **3x percentage**
- Levenshtein distance: berbanding terbalik

### Limits
- API suggestions: **8 results** max
- Database query: **500-1500** candidates
- Minimum score: **25-50** untuk inclusion

## Performance Notes

- ✅ Debounce 300ms prevents API spam
- ✅ Limit 500-1500 candidates keeps queries fast
- ✅ Manual pagination for fuzzy results
- ✅ Select only needed fields in queries

## Browser Support

- ✅ Modern browsers (Chrome, Firefox, Safari, Edge)
- ✅ Keyboard accessible
- ✅ Mobile friendly with touch support

## Future Improvements

1. **Redis Caching** - Cache popular searches
2. **Weighted Database** - Add popularity score
3. **User Feedback** - Track which searches lead to clicks
4. **Synonym Support** - Map "DBZ" → "Dragon Ball Z"
5. **Language Support** - Handle romanized vs kanji
6. **Analytics** - Track search patterns

## Testing

Test cases to verify:
1. Exact match: "Naruto" → shows Naruto first
2. Typo: "Narto", "Naroto" → still shows Naruto
3. Case insensitive: "NARUTO" → shows naruto
4. Partial: "Naru" → shows Naruto in dropdown
5. No results: "XYZ123" → shows suggestions
6. Empty query: < 2 characters → no dropdown
7. Keyboard nav: Arrow keys work
8. Mobile: Touch friendly
