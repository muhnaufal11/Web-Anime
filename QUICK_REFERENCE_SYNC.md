# 🎯 Quick Reference - MyAnimeList Sync

## 📍 Access Points

```
Admin Panel: http://localhost/admin/mal-sync
Database: anime, genres, episodes tables
API: Jikan API v4 (https://api.jikan.moe/)
Storage: storage/app/public/posters/
```

## 🎮 Commands

```bash
# Top anime (fastest)
php artisan anime:sync-mal --type=top --limit=10

# Seasonal anime
php artisan anime:sync-mal --type=seasonal --season=winter --year=2024

# Search specific
php artisan anime:sync-mal --type=search --search="Naruto"

# Skip images
php artisan anime:sync-mal --type=top --limit=10 --no-images

# Clear caches
php artisan cache:clear && php artisan view:clear
```

## 📲 Admin Panel Steps

1. Navigate to: **http://localhost/admin**
2. Click: **MAL Sync** (sidebar)
3. Configure: **Sync Type, Limit, Season, etc.**
4. Click: **🚀 Start Sync**
5. Watch: **Progress bar and live logs**
6. Result: **Auto-redirect to anime list**

## 📊 Sync Types

### Top Anime
- Best for: Getting highest-rated anime
- Speed: 5-10 sec (10 anime, no images)
- Images: 30-40 sec (10 anime, with images)

### Seasonal Anime
- Best for: Getting anime from specific season
- Speed: 15-20 sec (25 anime, no images)
- Images: 60-80 sec (25 anime, with images)

### Search Anime
- Best for: Finding specific anime by name
- Speed: 5-8 sec (5 anime, no images)
- Images: 15-20 sec (5 anime, with images)

## 🎨 Log Indicators

| Emoji | Meaning |
|-------|---------|
| 🚀 | Process started |
| 📋 | Configuration |
| 🔢 | Numbers/counts |
| 🖼️ | Image download |
| 🔍 | Search query |
| 📅 | Season selected |
| 📆 | Year selected |
| ⏳ | Waiting/connecting |
| 📡 | Data fetching |
| 💾 | Database saving |
| ✅ | Success |
| ❌ | Error |
| ⚡ | Optimization |

## 📈 Progress Stages

```
0%  → Waiting
25% → Connecting to API
50% → Fetching data
75% → Saving to database
100% → Complete!
```

## 🔧 Common Fixes

**Logs not showing?**
```bash
php artisan cache:clear
php artisan view:clear
```

**Images not downloading?**
```bash
mkdir -p storage/app/public/posters
chmod -R 755 storage/
```

**API timeout?**
```
Wait a few minutes and retry
(Jikan API may be temporarily overloaded)
```

**Button disabled?**
```
Refresh page with F5
Check browser console for errors
```

## 📂 Key Files

```
app/Filament/Pages/MalSync.php
app/Services/MyAnimeListService.php
app/Console/Commands/SyncAnimeFromMAL.php
resources/views/filament/pages/mal-sync.blade.php
```

## 📝 Form Fields

```
Required (Always visible):
├─ Sync Type (dropdown)
├─ Limit (1-50)
└─ Download Images (toggle)

Conditional:
├─ Search Query (when type=search)
└─ Season + Year (when type=seasonal)
```

## ⏱️ Timing Reference

| Operation | Time |
|-----------|------|
| 5 anime, no images | ~5 sec |
| 10 anime, no images | ~8 sec |
| 10 anime, with images | ~40 sec |
| 25 anime, no images | ~20 sec |
| 25 anime, with images | ~90 sec |

## ✅ Verification Checklist

- [ ] Admin page loads
- [ ] Form fields update reactively
- [ ] Sync starts without errors
- [ ] Progress bar updates
- [ ] Logs appear with timestamps
- [ ] Notification shows on completion
- [ ] Page redirects to anime list
- [ ] Database has new anime
- [ ] Images are in storage/app/public/posters/

## 🚨 Error Handling

```
Invalid search query → Shows error notification
API timeout → Logs error message
Database error → Shows error and rolls back
Missing images → Sync continues, skips image
Network error → Logs and shows error notification
```

## 🎓 Best Practices

1. **Test with limit=3**: Start small
2. **Disable images first**: Then enable
3. **Use top anime first**: More stable than seasonal
4. **Monitor storage space**: Images take ~50KB each
5. **Schedule off-peak**: Run late at night

## 📞 API Rate Limit

- **350ms delay** between requests (Jikan spec)
- **Automatic compliance** in MyAnimeListService
- **No errors** if rate limit hit (just slower)
- **Max 25-50** anime recommended per sync

## 🎬 Example Workflows

### Quick Test (30 seconds)
```
1. Type: Top Anime
2. Limit: 3
3. Images: OFF
4. Click Start
5. Watch logs
```

### Production Sync (2 minutes)
```
1. Type: Seasonal
2. Season: Winter
3. Year: 2024
4. Limit: 25
5. Images: ON
6. Let it run
```

### Search Specific (1 minute)
```
1. Type: Search
2. Query: "Jujutsu Kaisen"
3. Limit: 5
4. Images: ON
5. Start sync
```

---

**Status**: ✅ Ready to use!

Need more help? See MALYNC_COMPLETE.md or TEST_SYNC_SYSTEM.md
