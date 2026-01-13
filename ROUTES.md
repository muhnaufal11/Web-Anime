# Web Anime - API Routes & Endpoints Documentation

## Overview

This document describes all available routes and their functionality in the Web Anime streaming platform.

## Public Routes (Accessible without authentication)

All public routes return HTML responses and are rendered using Blade templates. No API authentication is required.

### Homepage
```
GET /
Controller: App\Http\Controllers\HomeController@index
Purpose: Display the homepage with featured anime, latest episodes, and popular series
Response: Blade view (home.blade.php)
Data passed:
  - featuredAnimes: Anime collection (featured = true)
  - latestEpisodes: Latest 12 anime with their recent episodes
  - popularAnimes: Top 10 anime by rating
  - genres: All available genres for sidebar
```

### Search & Filter
```
GET /search
Controller: App\Http\Controllers\HomeController@search
Purpose: Search anime and apply filters
Query Parameters:
  - search (optional): Search anime title or synopsis
  - genre (optional): Filter by genre ID
  - status (optional): Filter by status (Ongoing/Completed)
  - type (optional): Filter by type (TV/Movie/ONA)
Response: Blade view (search.blade.php)
Data passed:
  - animes: Paginated results (12 per page)
  - genres: All genres for filter dropdown
Example URLs:
  /search
  /search?search=Attack
  /search?genre=1
  /search?status=Ongoing&type=TV
  /search?search=Dragon&genre=1&status=Completed
```

### Anime Detail Page
```
GET /anime/{slug}
Controller: App\Http\Controllers\DetailController@show
Purpose: Display comprehensive anime information
Parameters:
  - slug: Anime slug (e.g., attack-on-titan)
Response: Blade view (detail.blade.php)
Data passed:
  - anime: Single Anime model with relations loaded
    - genres: Associated genres
    - episodes: All episodes ordered by episode number
  - relatedAnimes: 6 related anime by shared genres
Example URLs:
  /anime/attack-on-titan
  /anime/death-note
  /anime/my-hero-academia
```

### Watch Episode
```
GET /watch/{slug}
Controller: App\Http\Controllers\WatchController@show
Purpose: Display video player with episode details
Parameters:
  - slug: Episode slug (e.g., attack-on-titan-episode-1)
Response: Blade view (watch.blade.php)
Data passed:
  - episode: Single Episode model with relations loaded
    - anime: Parent anime with genres
    - videoServers: All active video servers
  - animeEpisodes: All episodes of the anime for sidebar navigation
Example URLs:
  /watch/attack-on-titan-episode-1
  /watch/death-note-episode-5
```

## Livewire Components

### Video Player Component
```
Component: App\Livewire\VideoPlayer
Location: resources/views/livewire/video-player.blade.php
Purpose: Interactive video player with server switching
Properties:
  - episode: Episode model
  - selectedServerId: Currently selected video server ID
Methods:
  - selectServer($serverId): Switch to a different video server
Features:
  - Server tabs for switching
  - Responsive video container
  - Support for iframe and direct URL embeds
  - Real-time updates without page reload
```

## Admin Routes (FilamentPHP v3)

All admin routes are protected by Filament's authentication middleware. You must be logged in as an admin user.

### Dashboard
```
GET /admin
Purpose: Admin dashboard and navigation
Access: Requires admin authentication
```

### Genre Management
```
GET    /admin/genres              - List all genres
GET    /admin/genres/create       - Create genre form
POST   /admin/genres              - Store new genre
GET    /admin/genres/{record}/edit - Edit genre form
PUT    /admin/genres/{record}      - Update genre
DELETE /admin/genres/{record}      - Delete genre
```

### Anime Management
```
GET    /admin/animes              - List all anime (grid view)
GET    /admin/animes/create       - Create anime form
POST   /admin/animes              - Store new anime
GET    /admin/animes/{record}/edit - Edit anime form
PUT    /admin/animes/{record}      - Update anime
DELETE /admin/animes/{record}      - Delete anime
```

**Anime Form Fields**:
- title (text, required)
- slug (text, unique, auto-generated)
- synopsis (textarea, required)
- poster_image (file upload, image)
- type (select: TV/Movie/ONA)
- status (select: Ongoing/Completed)
- release_year (number, optional)
- rating (decimal 0-10, optional)
- featured (toggle)
- genres (multi-select, many-to-many)

### Episode Management
```
GET    /admin/episodes              - List all episodes
GET    /admin/episodes/create       - Create episode form
POST   /admin/episodes              - Store new episode
GET    /admin/episodes/{record}/edit - Edit episode form
PUT    /admin/episodes/{record}      - Update episode
DELETE /admin/episodes/{record}      - Delete episode
```

**Episode Form Fields**:
- anime_id (relationship select, required)
- episode_number (number, required)
- title (text, required)
- slug (text, unique, auto-generated)
- description (textarea, optional)
- videoServers (repeater field, many-to-many)
  - server_name (text)
  - embed_url (textarea)

### Video Server Management
```
GET    /admin/video-servers              - List all video servers
GET    /admin/video-servers/create       - Create video server form
POST   /admin/video-servers              - Store new video server
GET    /admin/video-servers/{record}/edit - Edit video server form
PUT    /admin/video-servers/{record}      - Update video server
DELETE /admin/video-servers/{record}      - Delete video server
```

**Video Server Form Fields**:
- episode_id (relationship select, required)
- server_name (text, required)
- embed_url (textarea, required)
- is_active (toggle)

## Model Relationships

### Anime Model
```php
// Get all genres
$anime->genres() // BelongsToMany relationship

// Get all episodes
$anime->episodes() // HasMany relationship

// Get route key (for slug-based routing)
$anime->getRouteKeyName() // Returns 'slug'
```

### Genre Model
```php
// Get all anime in this genre
$genre->animes() // BelongsToMany relationship
```

### Episode Model
```php
// Get parent anime
$episode->anime() // BelongsTo relationship

// Get all active video servers
$episode->videoServers() // HasMany relationship (filtered by is_active=true)

// Get route key (for slug-based routing)
$episode->getRouteKeyName() // Returns 'slug'
```

### VideoServer Model
```php
// Get parent episode
$videoServer->episode() // BelongsTo relationship
```

## Database Queries Used

### HomePage Controller
```php
// Featured anime
Anime::where('featured', true)->with('genres', 'episodes')->limit(5)->get()

// Latest episodes
Anime::with(['episodes' => fn ($q) => $q->orderBy('episode_number', 'desc')->limit(1)])
     ->orderBy('updated_at', 'desc')->limit(12)->get()

// Popular anime
Anime::with('genres')->orderBy('rating', 'desc')->limit(10)->get()
```

### Search Controller
```php
// Basic search
Anime::where('title', 'like', "%{$search}%")
     ->orWhere('synopsis', 'like', "%{$search}%")

// Genre filter
Anime::whereHas('genres', fn ($q) => $q->where('id', $genreId))

// Status/Type filter
Anime::where('status', $status)->where('type', $type)

// Combined with pagination
Anime::with('genres', 'episodes')
     ->orderBy('updated_at', 'desc')
     ->paginate(12)
```

### Related Anime Query
```php
Anime::whereHas('genres', function ($query) use ($anime) {
    $query->whereIn('genre_id', $anime->genres->pluck('id'));
})
->where('id', '!=', $anime->id)
->with('genres')
->limit(6)
->get()
```

## View Templates

### Layout (Base Template)
- **Location**: `resources/views/layouts/app.blade.php`
- **Includes**:
  - Navigation bar with search
  - Admin link (visible when authenticated)
  - Footer
  - Vite asset loading
  - Layout extends all other templates

### Home Page
- **Location**: `resources/views/home.blade.php`
- **Sections**:
  - Hero section with featured anime
  - Latest Episodes grid
  - Popular Series sidebar
  - Genre filter links

### Detail Page
- **Location**: `resources/views/detail.blade.php`
- **Sections**:
  - Anime poster image
  - Title and metadata
  - Genre tags
  - Synopsis
  - Episode list
  - Related anime sidebar
  - Watch button

### Watch Page
- **Location**: `resources/views/watch.blade.php`
- **Sections**:
  - Breadcrumb navigation
  - Video player (Livewire component)
  - Episode information
  - Share buttons
  - Episode list sidebar
  - Anime info card

### Search Page
- **Location**: `resources/views/search.blade.php`
- **Sections**:
  - Search/filter sidebar
  - Anime grid results
  - Pagination
  - Result count

### Video Player Component
- **Location**: `resources/views/livewire/video-player.blade.php`
- **Sections**:
  - Server selection tabs
  - Responsive iframe container
  - Current server display

## Slug Generation

Slugs are automatically generated from titles using Laravel's `Str::slug()` helper:
- Converts to lowercase
- Replaces spaces with hyphens
- Removes special characters
- Examples:
  - "Attack on Titan" → "attack-on-titan"
  - "My Hero Academia" → "my-hero-academia"
  - "Episode 1: Beginning" → "episode-1-beginning"

## Pagination

Search results use Laravel's built-in pagination:
- 12 results per page
- Links with Tailwind styling
- Query parameters preserved

## Response Status Codes

### Success
- `200 OK`: Successful page/resource load
- `201 Created`: Resource created (admin)
- `204 No Content`: Resource deleted (admin)

### Redirect
- `302 Found`: Redirect after form submission

### Client Error
- `404 Not Found`: Anime/episode not found
- `419 Token Mismatch`: CSRF token invalid (admin forms)
- `422 Unprocessable Entity`: Validation failed (admin)

### Server Error
- `500 Internal Server Error`: Server error
- `503 Service Unavailable`: Server under maintenance

## CORS & Security

- All routes use Laravel's default CSRF protection
- Admin routes protected by Filament authentication
- Public routes have no authentication requirements
- File uploads are stored in `storage/app/public/`
- User-uploaded images are served through Laravel's storage system

## Rate Limiting

No rate limiting is currently implemented, but can be added:
```php
Route::get('/search', [HomeController::class, 'search'])->middleware('throttle:60,1');
```

## Future API Endpoints (For Enhancement)

These could be added for JSON API support:
```
GET    /api/animes              - List anime as JSON
GET    /api/animes/{id}         - Single anime details
GET    /api/animes/{id}/episodes - Anime episodes
GET    /api/episodes/{id}       - Episode details
GET    /api/genres              - List genres
POST   /api/watchlist           - Add to user watchlist (requires auth)
GET    /api/user/watchlist      - Get user's watchlist
```

## Testing Routes

You can test routes manually:

```bash
# Homepage
http://localhost:8000/

# Search
http://localhost:8000/search?search=attack
http://localhost:8000/search?genre=1
http://localhost:8000/search?status=Ongoing

# Detail
http://localhost:8000/anime/attack-on-titan

# Watch
http://localhost:8000/watch/attack-on-titan-episode-1

# Admin
http://localhost:8000/admin
http://localhost:8000/admin/animes
http://localhost:8000/admin/episodes
```

## Debugging

Enable debug mode in `.env`:
```env
APP_DEBUG=true
```

View logs:
```bash
tail -f storage/logs/laravel.log
```

Use Tinker for database queries:
```bash
php artisan tinker
>>> Anime::all()
>>> Episode::find(1)->videoServers
```
