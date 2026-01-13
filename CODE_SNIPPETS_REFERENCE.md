# 📋 Code Snippets Reference

## Core Components Overview

### 1. MalSync Page - Key Methods

#### Form Schema (Reactive Fields)
```php
protected function getFormSchema(): array
{
    return [
        Select::make('syncType')
            ->label('Sync Type')
            ->options([
                'top' => '🏆 Top Anime (By Rating)',
                'seasonal' => '📅 Seasonal Anime',
                'search' => '🔍 Search Specific Anime',
            ])
            ->live()
            ->default('top'),
            
        TextInput::make('limit')
            ->label('Limit (1-50)')
            ->numeric()
            ->default(10)
            ->rules(['min:1', 'max:50']),
            
        TextInput::make('searchQuery')
            ->label('Search Query')
            ->visible(fn (Get $get) => $get('syncType') === 'search'),
            
        Select::make('season')
            ->label('Season')
            ->options([
                'winter' => 'Winter',
                'spring' => 'Spring',
                'summer' => 'Summer',
                'fall' => 'Fall',
            ])
            ->visible(fn (Get $get) => $get('syncType') === 'seasonal'),
            
        TextInput::make('year')
            ->label('Year')
            ->numeric()
            ->visible(fn (Get $get) => $get('syncType') === 'seasonal'),
            
        Toggle::make('downloadImages')
            ->label('Download Poster Images')
            ->default(true),
    ];
}
```

#### Sync Execution Method
```php
public function syncAnime()
{
    $this->isSyncing = true;
    $this->syncLogs = [];
    $this->syncProgress = 0;
    
    $this->addLog('🚀 Starting sync process...');
    
    try {
        $command = 'anime:sync-mal';
        $options = [
            '--type' => $this->syncType,
            '--limit' => $this->limit,
        ];
        
        $this->addLog("📋 Type: {$this->syncType}");
        $this->addLog("🔢 Limit: {$this->limit}");
        
        if (!$this->downloadImages) {
            $options['--no-images'] = true;
            $this->addLog('⚡ Skip downloading images (faster)');
        } else {
            $this->addLog('🖼️ Will download poster images');
        }
        
        if ($this->syncType === 'search' && $this->searchQuery) {
            $options['--search'] = $this->searchQuery;
            $this->addLog("🔍 Searching: {$this->searchQuery}");
        }
        
        if ($this->syncType === 'seasonal') {
            if ($this->season) {
                $options['--season'] = $this->season;
                $this->addLog("📅 Season: {$this->season}");
            }
            if ($this->year) {
                $options['--year'] = $this->year;
                $this->addLog("📆 Year: {$this->year}");
            }
        }
        
        $this->syncProgress = 25;
        $this->addLog('⏳ Connecting to MyAnimeList API...');
        sleep(1);
        
        $this->syncProgress = 50;
        $this->addLog('📡 Fetching anime data...');
        
        Artisan::call($command, $options);
        
        $this->syncProgress = 75;
        $this->addLog('💾 Saving to database...');
        sleep(1);
        
        $this->syncProgress = 100;
        $this->addLog('✅ Sync completed successfully!');
        
        Notification::make()
            ->title('Sync Successful!')
            ->success()
            ->body('Successfully synced anime from MyAnimeList')
            ->send();
        
        sleep(2);
        return redirect()->to('/admin/animes');
        
    } catch (\Exception $e) {
        $this->syncProgress = 0;
        $this->addLog('❌ Error: ' . $e->getMessage());
        
        Notification::make()
            ->title('Sync Failed')
            ->danger()
            ->body($e->getMessage())
            ->send();
    } finally {
        $this->isSyncing = false;
    }
}
```

#### Logging Method
```php
protected function addLog($message)
{
    $this->syncLogs[] = [
        'time' => now()->format('H:i:s'),
        'message' => $message,
    ];
}
```

---

### 2. MyAnimeListService - Key Methods

#### Batch Sync
```php
public function batchSync($animeList, $downloadImages = true): array
{
    $results = [
        'synced' => 0,
        'failed' => 0,
        'errors' => [],
        'anime' => [],
    ];
    
    foreach ($animeList as $malData) {
        try {
            $anime = $this->syncAnime($malData, $downloadImages);
            if ($anime) {
                $results['synced']++;
                $results['anime'][] = $anime;
            }
        } catch (\Exception $e) {
            $results['failed']++;
            $results['errors'][] = [
                'mal_id' => $malData['mal_id'] ?? null,
                'error' => $e->getMessage(),
            ];
        }
        
        // Rate limiting: 350ms between requests
        usleep(350000);
    }
    
    return $results;
}
```

#### Single Anime Sync
```php
public function syncAnime($malData, $downloadImage = true): ?Anime
{
    $slug = Str::slug($malData['title']);
    
    $anime = Anime::updateOrCreate(
        ['mal_id' => $malData['mal_id']],
        [
            'title' => $malData['title'],
            'synopsis' => $malData['synopsis'] ?? null,
            'rating' => $malData['score'] ?? 0,
            'status' => $malData['status'] ?? 'Unknown',
            'type' => $malData['type'] ?? 'Unknown',
            'release_year' => $malData['year'] ?? null,
        ]
    );
    
    if ($downloadImage && isset($malData['images']['jpg']['image_url'])) {
        $imagePath = $this->downloadImage(
            $malData['images']['jpg']['image_url'],
            $slug
        );
        $anime->update(['poster_image' => $imagePath]);
    }
    
    // Sync genres
    if (isset($malData['genres'])) {
        $genreIds = [];
        foreach ($malData['genres'] as $genre) {
            $genreModel = Genre::firstOrCreate(
                ['name' => $genre['name']],
                ['mal_id' => $genre['mal_id']]
            );
            $genreIds[] = $genreModel->id;
        }
        $anime->genres()->sync($genreIds);
    }
    
    return $anime;
}
```

#### Image Download
```php
public function downloadImage($url, $slug): string
{
    try {
        $response = Http::get($url);
        $filename = $slug . '.jpg';
        Storage::disk('public')->put('posters/' . $filename, $response->body());
        return 'posters/' . $filename;
    } catch (\Exception $e) {
        \Log::error("Image download failed: {$url}", ['error' => $e->getMessage()]);
        return null;
    }
}
```

#### Top Anime Fetch
```php
public function syncTopAnime($limit = 10, $downloadImages = true): array
{
    try {
        $response = Http::get('https://api.jikan.moe/v4/top/anime', [
            'limit' => min($limit, 50),
            'filter' => 'airing',
        ]);
        
        $animeList = $response->json('data', []);
        return $this->batchSync($animeList, $downloadImages);
    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch top anime: {$e->getMessage()}");
    }
}
```

#### Seasonal Anime Fetch
```php
public function syncSeasonalAnime($year, $season, $limit, $downloadImages): array
{
    try {
        $response = Http::get("https://api.jikan.moe/v4/seasons/{$year}/{$season}", [
            'limit' => min($limit, 50),
        ]);
        
        $animeList = $response->json('data', []);
        return $this->batchSync($animeList, $downloadImages);
    } catch (\Exception $e) {
        throw new \Exception("Failed to fetch seasonal anime: {$e->getMessage()}");
    }
}
```

---

### 3. Artisan Command - Key Parts

#### Command Definition
```php
protected function execute(): int
{
    $type = $this->option('type');
    $limit = (int) $this->option('limit') ?: 10;
    $downloadImages = !$this->option('no-images');
    
    $this->info("Starting MyAnimeList sync ({$type})...\n");
    
    try {
        $results = match($type) {
            'top' => $this->syncTopAnime($limit, $downloadImages),
            'seasonal' => $this->syncSeasonalAnime($limit, $downloadImages),
            'search' => $this->searchAnime($limit, $downloadImages),
            default => throw new \Exception("Invalid sync type: {$type}"),
        };
        
        $this->displayResults($results);
        return self::SUCCESS;
    } catch (\Exception $e) {
        $this->error("Sync failed: {$e->getMessage()}");
        return self::FAILURE;
    }
}
```

#### Progress Display
```php
protected function syncTopAnime($limit, $downloadImages): array
{
    $this->line('Syncing top anime...');
    
    $results = $this->service->syncTopAnime($limit, $downloadImages);
    
    $bar = $this->output->createProgressBar(count($results['anime']));
    $bar->finish();
    
    return $results;
}
```

---

### 4. Blade View - Log Display

#### Progress Bar Section
```blade
@if($isSyncing || count($syncLogs) > 0)
<div class="mt-6 bg-gray-50 dark:bg-gray-900 rounded-lg p-4 border-2 border-blue-200 dark:border-blue-800">
    @if($isSyncing)
    <div class="mb-4">
        <div class="flex items-center justify-between mb-2">
            <span class="text-sm font-semibold text-gray-700 dark:text-gray-300">Syncing Progress</span>
            <span class="text-sm font-bold text-blue-600 dark:text-blue-400">{{ $syncProgress }}%</span>
        </div>
        <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 overflow-hidden">
            <div class="bg-gradient-to-r from-blue-500 to-purple-600 h-3 rounded-full transition-all duration-500 ease-out" 
                 style="width: {{ $syncProgress }}%"></div>
        </div>
    </div>
    @endif
    
    {{-- Logs Display --}}
    <div class="space-y-2 max-h-64 overflow-y-auto">
        @foreach($syncLogs as $log)
        <div class="flex items-start gap-3 text-sm animate-fadeIn">
            <span class="text-xs text-gray-500 dark:text-gray-500 font-mono flex-shrink-0">{{ $log['time'] }}</span>
            <span class="text-gray-700 dark:text-gray-300 flex-1">{{ $log['message'] }}</span>
        </div>
        @endforeach
    </div>
</div>
@endif
```

---

### 5. Tailwind Config - Animation

```javascript
module.exports = {
    content: ['./resources/**/*.blade.php', './vendor/filament/**/*.blade.php'],
    theme: {
        extend: {
            colors: {
                danger: colors.rose,
                primary: colors.blue,
                success: colors.green,
                warning: colors.yellow,
            },
            animation: {
                fadeIn: 'fadeIn 0.3s ease-in',
            },
            keyframes: {
                fadeIn: {
                    '0%': { opacity: '0' },
                    '100%': { opacity: '1' },
                },
            },
        },
    },
}
```

---

## Usage Examples

### CLI Command Execution
```bash
# Top anime
php artisan anime:sync-mal --type=top --limit=10

# Seasonal
php artisan anime:sync-mal --type=seasonal --season=winter --year=2024 --limit=25

# Search
php artisan anime:sync-mal --type=search --search="Naruto" --limit=5

# Skip images
php artisan anime:sync-mal --type=top --limit=10 --no-images
```

### Tinker Testing
```bash
php artisan tinker

>>> $service = app(App\Services\MyAnimeListService::class)
>>> $results = $service->syncTopAnime(5, false)
>>> Anime::latest()->first()
>>> Anime::count()
```

### Database Queries
```bash
# Check latest synced anime
SELECT * FROM anime ORDER BY created_at DESC LIMIT 5;

# Count anime by status
SELECT status, COUNT(*) FROM anime GROUP BY status;

# Get anime with images
SELECT title, poster_image FROM anime WHERE poster_image IS NOT NULL;
```

---

## File Size Reference

| File | Lines | Purpose |
|------|-------|---------|
| MalSync.php | 192 | Admin page component |
| MyAnimeListService.php | 200+ | API integration |
| SyncAnimeFromMAL.php | 150+ | CLI command |
| mal-sync.blade.php | 190 | Admin UI |
| tailwind.config.js | 23 | Styles configuration |

---

## Environment Variables Needed

```
# .env file (if using custom API endpoints)
JIKAN_API_URL=https://api.jikan.moe/v4
ANIME_SYNC_LIMIT=50
ANIME_STORAGE_DISK=public
```

---

## Dependencies

```php
// Already installed
- Laravel 11
- Filament v3
- Livewire v3
- Illuminate\Support\Facades\Http
- Illuminate\Support\Facades\Storage
- Illuminate\Support\Facades\Artisan
```

---

This reference guide shows all the key code components. For complete implementation details, refer to the actual files in your project.
