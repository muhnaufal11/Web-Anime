# Web Anime - Quick Reference Guide

## Quick Start (TL;DR)

```bash
# 1. Navigate to project
cd c:\xampp\htdocs\Web\ Anime

# 2. Install dependencies
composer install
npm install

# 3. Setup environment
copy .env.example .env
php artisan key:generate

# 4. Configure database in .env
# DB_DATABASE=web_anime
# DB_USERNAME=root

# 5. Run migrations
php artisan migrate

# 6. Seed sample data
php artisan db:seed

# 7. Create admin user
php artisan tinker
# Type: App\Models\User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password')])
# Type: exit

# 8. Build assets
npm run build

# 9. Start server
php artisan serve

# 10. Create storage link
php artisan storage:link
```

**Access**:
- Frontend: http://localhost:8000
- Admin: http://localhost:8000/admin
- Email: admin@example.com
- Password: password

---

## File Structure Quick Guide

```
app/
├── Http/Controllers/
│   ├── HomeController.php       → Homepage, search functionality
│   ├── DetailController.php     → Anime detail pages
│   └── WatchController.php      → Video player pages
├── Livewire/
│   └── VideoPlayer.php          → Server switcher component
├── Models/
│   ├── Anime.php                → Anime model + relationships
│   ├── Genre.php                → Genre model
│   ├── Episode.php              → Episode model
│   └── VideoServer.php          → Video server model
└── Filament/Resources/
    ├── AnimeResource.php        → Admin anime management
    ├── GenreResource.php        → Admin genre management
    ├── EpisodeResource.php      → Admin episode management
    └── VideoServerResource.php  → Admin server management

database/
├── migrations/
│   ├── create_genres_table
│   ├── create_animes_table
│   ├── create_anime_genre_table
│   ├── create_episodes_table
│   └── create_video_servers_table
└── seeders/
    └── DatabaseSeeder.php       → Sample data

resources/
├── css/
│   └── app.css                  → Tailwind CSS imports
├── views/
│   ├── layouts/app.blade.php    → Main layout
│   ├── home.blade.php           → Homepage
│   ├── detail.blade.php         → Anime detail
│   ├── watch.blade.php          → Video player
│   ├── search.blade.php         → Search & filter
│   └── livewire/
│       └── video-player.blade.php → Video player component
└── js/
    ├── app.js
    └── bootstrap.js

routes/
└── web.php                      → All routes

config/
├── app.php
├── database.php
└── (other configs)
```

---

## Key Files Reference

| File | Purpose |
|------|---------|
| `.env` | Environment configuration (database, API keys) |
| `package.json` | JavaScript dependencies (Tailwind, Vite) |
| `composer.json` | PHP dependencies (Laravel, Filament) |
| `tailwind.config.js` | Tailwind CSS configuration |
| `postcss.config.js` | PostCSS configuration |
| `vite.config.js` | Vite bundler configuration |
| `routes/web.php` | All public routes |
| `database/migrations/*` | Database table definitions |
| `app/Models/*.php` | Database models |
| `app/Http/Controllers/*.php` | Request handlers |
| `resources/views/*.blade.php` | HTML templates |

---

## Common Commands

```bash
# Development
php artisan serve              # Start dev server
npm run dev                    # Hot reload CSS/JS
npm run build                  # Build for production

# Database
php artisan migrate            # Run migrations
php artisan migrate:fresh      # Reset database
php artisan db:seed           # Seed sample data
php artisan migrate:status    # Check migration status

# Cache & Optimization
php artisan cache:clear       # Clear cache
php artisan config:cache      # Cache configuration
php artisan optimize          # Optimize app

# Debugging
php artisan tinker            # Interactive shell
php artisan route:list        # List all routes
php artisan model:show Anime  # Show model info

# Filament/Admin
php artisan filament:install  # Setup Filament
php artisan make:filament-resource Name # Create resource

# Git
git status                    # Check changes
git add .                     # Stage changes
git commit -m "message"       # Commit changes
git push                      # Push to remote
```

---

## Database Relationships

```
Genre ←→ Anime (Many-to-Many via anime_genre table)
Anime ← Episode (One-to-Many)
Episode ← VideoServer (One-to-Many)
User ← Many content types (for future auth features)
```

**Query Examples**:
```php
// Get anime with genres and episodes
$anime = Anime::with('genres', 'episodes')->find(1);

// Get episode with anime and servers
$episode = Episode::with('anime', 'videoServers')->find(1);

// Get anime by slug
$anime = Anime::where('slug', 'attack-on-titan')->firstOrFail();

// Get active servers for episode
$servers = $episode->videoServers()->where('is_active', true)->get();

// Search anime
$results = Anime::where('title', 'like', '%attack%')->get();

// Filter by genre
$action = Anime::whereHas('genres', fn($q) => $q->where('id', 1))->get();
```

---

## Routes Overview

### Public Routes (No Auth)
```
GET  /                    → Homepage
GET  /search              → Search & filter
GET  /anime/{slug}        → Anime detail
GET  /watch/{slug}        → Watch episode
```

### Admin Routes (Protected)
```
GET  /admin               → Dashboard
GET  /admin/animes        → Manage anime
GET  /admin/genres        → Manage genres
GET  /admin/episodes      → Manage episodes
GET  /admin/video-servers → Manage servers
```

---

## Creating Anime (Admin Steps)

1. Go to `/admin/animes`
2. Click "Create"
3. Fill in:
   - Title (auto-generates slug)
   - Synopsis
   - Upload poster image
   - Select type (TV/Movie/ONA)
   - Select status (Ongoing/Completed)
   - Add year and rating
   - Toggle "Featured" if for homepage
   - Select genres
4. Click "Save"

---

## Adding Episodes

1. Go to `/admin/episodes`
2. Click "Create"
3. Fill in:
   - Select anime
   - Episode number
   - Title (auto-generates slug)
   - Description (optional)
   - Click "Add Video Server"
   - For each server:
     - Server name (GDrive, Mirror, etc.)
     - Embed URL (iframe code or direct link)
4. Click "Save"

---

## Video Server Types

- **GDrive**: Google Drive shared links
- **Mirror**: Alternative mirror hosts
- **Backup**: Backup streaming servers
- Any custom server name you add

Embed URL format:
```html
<!-- Full iframe code -->
<iframe src="https://..." width="100%" height="600" frameborder="0" allow="autoplay"></iframe>

<!-- Or just the URL -->
https://player.com/video/embed/123
```

---

## Customization Checklist

- [ ] Update site colors in `tailwind.config.js`
- [ ] Update site name in `resources/views/layouts/app.blade.php`
- [ ] Upload proper logo/icon
- [ ] Update footer information
- [ ] Change admin panel theme (Filament config)
- [ ] Add favicon to `public/`
- [ ] Update `.env` APP_NAME
- [ ] Configure mail settings for admin notifications
- [ ] Set up storage disk for faster uploads

---

## Troubleshooting Checklist

| Issue | Solution |
|-------|----------|
| Database error | Check .env DB settings, ensure MySQL running |
| CSS not loading | Run `npm install && npm run build` |
| 404 on admin | Check migrations ran, check Filament installed |
| Storage/images not showing | Run `php artisan storage:link` |
| Migrations fail | Run `php artisan migrate:fresh` if database empty |
| Can't login to admin | Create user with `php artisan tinker` |
| Livewire not working | Check Filament installed, clear cache |
| Slug generation fails | Check Illuminate\Support\Str is imported |

---

## Performance Tips

1. **Cache frequently accessed data**:
```php
$popular = Cache::remember('popular_anime', 3600, function() {
    return Anime::orderBy('rating', 'desc')->limit(10)->get();
});
```

2. **Eager load relationships**:
```php
// Good
Anime::with('genres', 'episodes')->get()

// Bad
Anime::all() // Then accessing $anime->genres causes N+1 queries
```

3. **Index database columns**:
Already done for:
- Foreign keys
- Slugs (unique)
- ID fields

4. **Use pagination**:
```php
Anime::paginate(12) // Instead of get() for large datasets
```

5. **Optimize images**:
- Compress before upload
- Use modern formats (WebP)
- Implement lazy loading

---

## Security Notes

✅ **Already Implemented**:
- CSRF protection on all forms
- SQL injection prevention (Eloquent ORM)
- XSS protection (Blade escaping)
- Password hashing
- Authentication guards

⚠️ **To Add for Production**:
- Rate limiting on search
- Sanitize embed URLs
- Validate file uploads
- Use HTTPS
- Security headers
- SQL injection tests
- XSS vulnerability scan
- Regular security updates

---

## Useful Links

- [Laravel Docs](https://laravel.com/docs)
- [Filament Docs](https://filamentphp.com/docs)
- [Tailwind CSS](https://tailwindcss.com)
- [Livewire Docs](https://livewire.laravel.com)
- [Eloquent ORM](https://laravel.com/docs/eloquent)
- [Blade Templates](https://laravel.com/docs/blade)

---

## Support

1. Check logs: `storage/logs/laravel.log`
2. Run `php artisan migrate:status`
3. Test DB: `php artisan db:show`
4. Check routes: `php artisan route:list`
5. Clear cache: `php artisan cache:clear`

---

**Version**: 1.0
**Last Updated**: December 26, 2025
**Status**: ✅ Complete and Ready
