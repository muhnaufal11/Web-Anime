<?php

namespace App\Filament\Pages;

use App\Models\Anime;
use App\Models\Episode;
use App\Models\VideoServer;
use App\Services\AnimeSailScraper;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AnimeSailScrape extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    
    protected static ?string $navigationLabel = 'AnimeSail Scraper';
    
    protected static ?string $title = 'AnimeSail Scraper';
    
    protected static ?string $navigationGroup = 'Sync & Scrape';
    
    protected static ?int $navigationSort = 100;

    protected static string $view = 'filament.pages.animesail-scrape';
    
    // Form fields
    public string $scrapeType = 'anime';
    public string $animeUrl = '';
    public string $batchUrls = '';
    public array $batchEntries = [];
    public ?int $localAnimeId = null;
    public int $limit = 0;
    public int $delay = 500;
    public bool $syncToDatabase = false;
    public bool $fetchServers = true;
    public bool $sendDiscordNotif = false;
    public bool $autoCreateEpisode = true;
    public bool $skipExistingEpisodes = true;
    
    // Results
    public array $scrapeResults = [];
    public array $scrapeLogs = [];
    public int $scrapeProgress = 0;
    public string $scrapeStatus = 'idle';
    public bool $isScraping = false;
    public array $batchResults = [];
    public int $batchCurrent = 0;
    public int $batchTotal = 0;
    
    // Episode list for anime selection
    public array $episodeList = [];
    public array $animeInfo = [];

    protected function getFormSchema(): array
    {
        return [
            Select::make('scrapeType')
                ->label('Scrape Type')
                ->options([
                    'anime' => '📺 Single Anime (All Episodes)',
                    'episode' => '🎬 Single Episode',
                    'batch' => '📚 Batch Scrape (Multiple Animes)',
                ])
                ->required()
                ->reactive()
                ->helperText('Choose what to scrape from AnimeSail'),
            
            TextInput::make('animeUrl')
                ->label('AnimeSail URL')
                ->required(fn ($get) => $get('scrapeType') !== 'batch')
                ->url()
                ->placeholder('https://154.26.137.28/anime/one-piece/')
                ->visible(fn ($get) => $get('scrapeType') !== 'batch')
                ->helperText('Paste the anime or episode URL from AnimeSail'),
            
            \Filament\Forms\Components\Repeater::make('batchEntries')
                ->label('Batch Entries')
                ->required(fn ($get) => $get('scrapeType') === 'batch')
                ->visible(fn ($get) => $get('scrapeType') === 'batch')
                ->schema([
                    TextInput::make('url')
                        ->label('AnimeSail URL')
                        ->required()
                        ->url()
                        ->placeholder('https://154.26.137.28/anime/one-piece/')
                        ->columnSpan('full'),
                    
                    Select::make('animeId')
                        ->label('Match to Local Anime')
                        ->searchable()
                        ->getSearchResultsUsing(function (string $search): array {
                            if (strlen($search) < 2) {
                                return [];
                            }
                            return Anime::where('title', 'like', "%{$search}%")
                                ->orderBy('title')
                                ->limit(50)
                                ->pluck('title', 'id')
                                ->toArray();
                        })
                        ->getOptionLabelUsing(fn ($value): ?string => Anime::find($value)?->title)
                        ->columnSpan('full')
                        ->helperText('Type at least 2 characters to search anime'),
                ])
                ->columns(1)
                ->createItemButtonLabel('➕ Add Anime Entry')
                ->helperText('Masukkan URL dan pilih anime lokal untuk setiap entry'),
            
            Select::make('localAnimeId')
                ->label('Match to Local Anime (Optional)')
                ->searchable()
                ->getSearchResultsUsing(function (string $search): array {
                    if (strlen($search) < 2) {
                        return [];
                    }
                    return Anime::where('title', 'like', "%{$search}%")
                        ->orderBy('title')
                        ->limit(50)
                        ->pluck('title', 'id')
                        ->toArray();
                })
                ->getOptionLabelUsing(fn ($value): ?string => Anime::find($value)?->title)
                ->visible(fn ($get) => $get('syncToDatabase') && $get('scrapeType') !== 'batch')
                ->helperText('Type at least 2 characters to search anime'),
            
            Toggle::make('fetchServers')
                ->label('Fetch Video Servers')
                ->default(true)
                ->helperText('Fetch actual embed URLs via AJAX (slower but complete)'),
            
            Toggle::make('syncToDatabase')
                ->label('Sync to Database')
                ->default(false)
                ->reactive()
                ->helperText('Automatically save servers to local database based on anime title'),
            
            Toggle::make('autoCreateEpisode')
                ->label('Auto Create Episode')
                ->default(true)
                ->visible(fn ($get) => $get('syncToDatabase'))
                ->helperText('Automatically create episode if not exists'),
            
            Toggle::make('skipExistingEpisodes')
                ->label('Skip Existing Episodes')
                ->default(true)
                ->visible(fn ($get) => $get('syncToDatabase') && $get('localAnimeId'))
                ->helperText('Only scrape NEW episodes (skip episodes that already have servers)'),
            
            Toggle::make('sendDiscordNotif')
                ->label('Send Discord Notification')
                ->default(false)
                ->visible(fn ($get) => $get('syncToDatabase'))
                ->helperText('Send notification to Discord for new episodes'),
            
            TextInput::make('limit')
                ->label('Episode Limit')
                ->numeric()
                ->default(0)
                ->visible(fn ($get) => $get('scrapeType') === 'anime')
                ->helperText('0 = All episodes, or set number to limit'),
            
            TextInput::make('delay')
                ->label('Delay (ms)')
                ->numeric()
                ->default(500)
                ->helperText('Delay between requests to avoid rate limiting'),
        ];
    }

    public function fetchEpisodeList()
    {
        if (empty($this->animeUrl)) {
            Notification::make()
                ->title('URL Required')
                ->warning()
                ->body('Please enter AnimeSail anime URL first')
                ->send();
            return;
        }

        $this->addLog('🔍 Fetching episode list...');
        $this->scrapeStatus = 'fetching';
        
        try {
            $scraper = new AnimeSailScraper();
            $result = $scraper->fetchEpisodeList($this->animeUrl);
            
            if ($result['success']) {
                $this->episodeList = $result['episodes'];
                $this->animeInfo = $result['anime_info'];
                
                $this->addLog("✅ Found {$result['count']} episodes");
                $this->addLog("📺 Anime: " . ($this->animeInfo['title'] ?? 'Unknown'));
                
                Notification::make()
                    ->title('Episodes Found!')
                    ->success()
                    ->body("Found {$result['count']} episodes")
                    ->send();
            } else {
                $this->addLog("❌ Error: " . ($result['error'] ?? 'Unknown error'));
                Notification::make()
                    ->title('Failed')
                    ->danger()
                    ->body($result['error'] ?? 'Unknown error')
                    ->send();
            }
        } catch (\Exception $e) {
            $this->addLog("❌ Exception: " . $e->getMessage());
            Notification::make()
                ->title('Error')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
        
        $this->scrapeStatus = 'idle';
    }

    public function startScrape()
    {
        $this->validate([
            'animeUrl' => 'required|url',
        ]);

        $this->isScraping = true;
        $this->scrapeLogs = [];
        $this->scrapeResults = [];
        $this->scrapeProgress = 0;
        $this->scrapeStatus = 'running';

        // Store in cache for polling
        $key = $this->cacheKey();
        Cache::put($key, [
            'progress' => 0,
            'status' => 'running',
            'logs' => [['time' => now()->format('H:i:s'), 'message' => '🚀 Starting scrape...']],
            'results' => [],
            'error' => null,
        ], now()->addMinutes(30));

        // Dispatch job
        \App\Jobs\AnimeSailScrapeJob::dispatch(Auth::id(), [
            'scrapeType' => $this->scrapeType,
            'animeUrl' => $this->animeUrl,
            'localAnimeId' => $this->localAnimeId,
            'limit' => $this->limit,
            'delay' => $this->delay,
            'syncToDatabase' => $this->syncToDatabase,
            'fetchServers' => $this->fetchServers,
        ]);

        Notification::make()
            ->title('Scraping Started')
            ->info()
            ->body('Scrape job has been queued')
            ->send();
    }

    public function pollScrape()
    {
        $state = Cache::get($this->cacheKey());
        if (!$state) {
            return;
        }

        $logs = $state['logs'] ?? [];
        if (is_string($logs)) {
            $logs = [['time' => now()->format('H:i:s'), 'message' => $logs]];
        }

        $this->scrapeLogs = $logs;
        $this->scrapeProgress = $state['progress'] ?? 0;
        $this->scrapeStatus = $state['status'] ?? 'idle';
        $this->scrapeResults = $state['results'] ?? [];

        if (in_array($this->scrapeStatus, ['done', 'error'])) {
            $this->isScraping = false;

            if ($this->scrapeStatus === 'done') {
                Notification::make()
                    ->title('Scrape Complete!')
                    ->success()
                    ->body('Check the results below')
                    ->send();
            }

            if ($this->scrapeStatus === 'error' && !empty($state['error'])) {
                Notification::make()
                    ->title('Scrape Failed')
                    ->danger()
                    ->body($state['error'])
                    ->send();
            }
        }
    }

    public function scrapeNow()
    {
        if ($this->scrapeType === 'batch') {
            $this->scrapeBatch();
            return;
        }

        // Synchronous scrape for smaller jobs
        $this->validate([
            'animeUrl' => 'required|url',
        ]);

        $this->isScraping = true;
        $this->scrapeLogs = [];
        $this->scrapeResults = [];
        $this->scrapeProgress = 0;
        $this->scrapeStatus = 'running';

        $this->addLog('🚀 Starting scrape...');

        try {
            $scraper = new AnimeSailScraper();
            
            if ($this->scrapeType === 'episode') {
                // Single episode
                $this->addLog("🎬 Fetching episode: {$this->animeUrl}");
                $result = $scraper->fetchEpisodeServers($this->animeUrl);
                
                if ($result['success']) {
                    $this->addLog("✅ Found {$result['count']} servers");
                    
                    // Filter and fetch actual URLs
                    $validServers = [];
                    foreach ($result['servers'] as $server) {
                        $embedUrl = $server['url'] ?? null;
                        
                        // Fetch via AJAX if needed
                        if ($this->fetchServers && empty($embedUrl) && !empty($server['post_id'])) {
                            $this->addLog("  📡 Fetching {$server['name']}...");
                            $embedUrl = $scraper->fetchServerEmbed(
                                $server['post_id'],
                                $server['type'],
                                $server['nume'],
                                $server['nonce'] ?? '',
                                $this->animeUrl
                            );
                            usleep($this->delay * 1000);
                        }
                        
                        if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                            $validServers[] = [
                                'name' => $server['name'],
                                'url' => $embedUrl,
                                'type' => $server['type'] ?? 'unknown',
                            ];
                            $this->addLog("  ✅ {$server['name']}: " . substr($embedUrl, 0, 50) . '...');
                        }
                    }
                    
                    $this->scrapeResults = [
                        'type' => 'episode',
                        'url' => $this->animeUrl,
                        'servers' => $validServers,
                        'total' => count($validServers),
                    ];
                    
                    // Sync to database if enabled
                    if ($this->syncToDatabase && $this->localAnimeId && !empty($validServers)) {
                        $this->syncServersToDatabase($validServers);
                    }
                    
                    $this->addLog("✅ Scrape complete! Found " . count($validServers) . " valid servers");
                } else {
                    $this->addLog("❌ Error: " . ($result['error'] ?? 'Unknown'));
                }
            } else {
                // Full anime scrape
                $this->addLog("📺 Fetching anime: {$this->animeUrl}");
                
                $episodeResult = $scraper->fetchEpisodeList($this->animeUrl);
                if (!$episodeResult['success']) {
                    throw new \Exception($episodeResult['error'] ?? 'Failed to fetch episodes');
                }
                
                $this->animeInfo = $episodeResult['anime_info'];
                $episodes = $episodeResult['episodes'];
                
                $this->addLog("✅ Found " . count($episodes) . " episodes");
                $this->addLog("📺 Anime: " . ($this->animeInfo['title'] ?? 'Unknown'));
                
                // Get existing episodes if skipExistingEpisodes is enabled
                $existingEpisodeNumbers = [];
                if ($this->skipExistingEpisodes && $this->localAnimeId) {
                    $existingEpisodeNumbers = Episode::where('anime_id', $this->localAnimeId)
                        ->whereHas('videoServers')
                        ->pluck('episode_number')
                        ->toArray();
                    
                    if (!empty($existingEpisodeNumbers)) {
                        $this->addLog("📌 Skipping " . count($existingEpisodeNumbers) . " existing episodes with servers");
                    }
                }
                
                // Apply limit
                if ($this->limit > 0 && $this->limit < count($episodes)) {
                    $episodes = array_slice($episodes, 0, $this->limit);
                    $this->addLog("⚠️ Limited to {$this->limit} episodes");
                }
                
                $allServers = [];
                $totalServers = 0;
                
                foreach ($episodes as $index => $episode) {
                    $episodeNumber = $episode['episode_number'] ?? ($index + 1);
                    
                    // Skip existing episodes if enabled
                    if ($this->skipExistingEpisodes && in_array($episodeNumber, $existingEpisodeNumbers)) {
                        $this->addLog("⏭️ Episode {$episodeNumber} - skipped (already has servers)");
                        continue;
                    }
                    
                    $this->scrapeProgress = (int) (($index + 1) / count($episodes) * 100);
                    $this->addLog("🎬 Episode {$episodeNumber}...");
                    
                    $serverResult = $scraper->fetchEpisodeServers($episode['url']);
                    
                    if ($serverResult['success']) {
                        $validServers = [];
                        
                        foreach ($serverResult['servers'] as $server) {
                            $embedUrl = $server['url'] ?? null;
                            
                            if ($this->fetchServers && empty($embedUrl) && !empty($server['post_id'])) {
                                $embedUrl = $scraper->fetchServerEmbed(
                                    $server['post_id'],
                                    $server['type'],
                                    $server['nume'],
                                    $server['nonce'] ?? '',
                                    $episode['url']
                                );
                                usleep(($this->delay / 2) * 1000);
                            }
                            
                            if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                                $validServers[] = [
                                    'name' => $server['name'],
                                    'url' => $embedUrl,
                                ];
                                $totalServers++;
                            }
                        }
                        
                        $allServers[] = [
                            'episode' => $episode['episode_number'] ?? ($index + 1),
                            'title' => $episode['title'],
                            'url' => $episode['url'],
                            'servers' => $validServers,
                        ];
                        
                        $this->addLog("  ✅ " . count($validServers) . " servers");
                        
                        // Sync to database
                        if ($this->syncToDatabase && $this->localAnimeId && !empty($validServers)) {
                            $this->syncServersToDatabase($validServers, $episode['episode_number'], $episode['title'] ?? null);
                        }
                    }
                    
                    usleep($this->delay * 1000);
                }
                
                $this->scrapeResults = [
                    'type' => 'anime',
                    'anime' => $this->animeInfo,
                    'episodes' => $allServers,
                    'total_episodes' => count($allServers),
                    'total_servers' => $totalServers,
                ];
                
                $this->addLog("✅ Complete! {$totalServers} servers from " . count($allServers) . " episodes");
            }
            
            $this->scrapeStatus = 'done';
            $this->scrapeProgress = 100;
            
            Notification::make()
                ->title('Scrape Complete!')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            $this->addLog("❌ Error: " . $e->getMessage());
            $this->scrapeStatus = 'error';
            
            Notification::make()
                ->title('Scrape Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }
        
        $this->isScraping = false;
    }

    protected function isInternalServer(string $url): bool
    {
        $patterns = [
            '154.26.137.28',
            '185.217.95.',
            'nontonanimeid',
            'animesail',
            '/proxy/',
            '/embed-local/',
        ];

        foreach ($patterns as $pattern) {
            if (stripos($url, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function syncServersToDatabase(array $servers, ?int $episodeNumber = null, ?string $episodeTitle = null, ?int $animeId = null): void
    {
        // Use provided animeId or fall back to localAnimeId
        $animeId = $animeId ?? $this->localAnimeId;
        
        if (!$animeId) {
            return;
        }

        $query = Episode::where('anime_id', $animeId);
        
        if ($episodeNumber) {
            $query->where('episode_number', $episodeNumber);
        }
        
        $episode = $query->first();
        
        // Auto create episode if not exists (batch mode always auto-create)
        if (!$episode && $episodeNumber) {
            $anime = Anime::find($animeId);
            if ($anime) {
                $slug = \Illuminate\Support\Str::slug($anime->title . '-episode-' . $episodeNumber);
                $episode = Episode::create([
                    'anime_id' => $animeId,
                    'episode_number' => $episodeNumber,
                    'title' => $episodeTitle ?? "Episode {$episodeNumber}",
                    'slug' => $slug,
                    'is_filler' => false,
                ]);
                $this->addLog("  ✨ Created episode {$episodeNumber}");
            }
        }
        
        if (!$episode) {
            $this->addLog("  ⚠️ Episode not found in database");
            return;
        }

        // Check if episode already has servers (for Discord notification logic)
        $hadServers = VideoServer::where('episode_id', $episode->id)->exists();

        $created = 0;
        foreach ($servers as $server) {
            if (empty($server['url'])) {
                continue;
            }

            $vs = VideoServer::updateOrCreate(
                [
                    'episode_id' => $episode->id,
                    'embed_url' => $server['url'],
                ],
                [
                    'server_name' => $server['name'],
                    'is_active' => true,
                    'source' => 'sync',
                ]
            );
            
            if ($vs->wasRecentlyCreated) {
                $created++;
            }
        }
        
        if ($created > 0) {
            $this->addLog("  💾 Saved {$created} new servers to DB");
            
            // Send Discord notification if enabled and this is first time episode has servers
            if ($this->sendDiscordNotif && !$hadServers) {
                try {
                    $discord = app(\App\Services\DiscordNotificationService::class);
                    $discord->notifyNewEpisode($episode);
                    $this->addLog("  📢 Discord notification sent!");
                } catch (\Exception $e) {
                    $this->addLog("  ⚠️ Discord notification failed: " . $e->getMessage());
                }
            }
        }
    }

    protected function addLog(string $message): void
    {
        $this->scrapeLogs[] = [
            'time' => now()->format('H:i:s'),
            'message' => $message,
        ];
    }

    protected function cacheKey(): string
    {
        return 'animesail_scrape:' . Auth::id();
    }

    public function scrapeBatch(): void
    {
        $this->validate([
            'batchEntries' => 'required|array|min:1',
            'batchEntries.*.url' => 'required|url',
        ]);

        $urlAnimeMap = [];
        foreach ($this->batchEntries as $entry) {
            $url = $entry['url'] ?? null;
            $animeId = $entry['animeId'] ?? null;
            
            if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            // Get anime title if animeId provided
            $animeTitle = null;
            if ($animeId) {
                $anime = Anime::find($animeId);
                if ($anime) {
                    $animeTitle = $anime->title;
                }
            }

            $urlAnimeMap[] = [
                'url' => $url,
                'title' => $animeTitle,
                'animeId' => $animeId,
            ];
        }

        if (empty($urlAnimeMap)) {
            Notification::make()
                ->title('Invalid Entries')
                ->danger()
                ->body('Please enter valid anime entries')
                ->send();
            return;
        }

        $this->isScraping = true;
        $this->scrapeLogs = [];
        $this->batchResults = [];
        $this->scrapeProgress = 0;
        $this->scrapeStatus = 'running';
        $this->batchTotal = count($urlAnimeMap);
        $this->batchCurrent = 0;

        // Initialize cache for real-time updates
        $cacheKey = $this->cacheBatchKey();
        Cache::put($cacheKey, [
            'progress' => 0,
            'status' => 'running',
            'current' => 0,
            'total' => $this->batchTotal,
            'logs' => [['time' => now()->format('H:i:s'), 'message' => '🚀 Starting batch scrape for ' . count($urlAnimeMap) . ' animes...']],
            'results' => [],
        ], now()->addMinutes(60));

        $scraper = new AnimeSailScraper();

        try {
            foreach ($urlAnimeMap as $index => $item) {
                $this->batchCurrent = $index + 1;
                $this->scrapeProgress = (int)((($index) / count($urlAnimeMap)) * 100);
                $url = $item['url'];
                $animeTitle = $item['title'];
                $animeId = $item['animeId'];

                $this->addLog("\n🎬 [{$this->batchCurrent}/{$this->batchTotal}] Fetching: {$url}");
                if ($animeTitle) {
                    $this->addLog("  📺 Matched Anime: {$animeTitle}");
                }

                // Update cache with current progress
                $this->updateBatchCache();

                try {
                    $episodeResult = $scraper->fetchEpisodeList($url);
                    
                    if (!$episodeResult['success']) {
                        $this->addLog("❌ Error: " . ($episodeResult['error'] ?? 'Unknown'));
                        $this->updateBatchCache();
                        $this->batchResults[] = [
                            'url' => $url,
                            'title' => $animeTitle,
                            'success' => false,
                            'error' => $episodeResult['error'] ?? 'Unknown error',
                            'episodes' => 0,
                            'servers' => 0,
                        ];
                        continue;
                    }

                    $episodes = $episodeResult['episodes'];
                    $animeInfo = $episodeResult['anime_info'];
                    $totalServers = 0;

                    $this->addLog("✅ Found " . count($episodes) . " episodes");
                    $this->updateBatchCache();

                    // Apply limit per anime
                    if ($this->limit > 0 && $this->limit < count($episodes)) {
                        $episodes = array_slice($episodes, 0, $this->limit);
                        $this->addLog("⚠️ Limited to {$this->limit} episodes");
                        $this->updateBatchCache();
                    }

                    foreach ($episodes as $ep_index => $episode) {
                        $episodeNumber = $episode['episode_number'] ?? ($ep_index + 1);

                        $serverResult = $scraper->fetchEpisodeServers($episode['url']);

                        if ($serverResult['success']) {
                            $validServers = [];

                            foreach ($serverResult['servers'] as $server) {
                                $embedUrl = $server['url'] ?? null;

                                if ($this->fetchServers && empty($embedUrl) && !empty($server['post_id'])) {
                                    $embedUrl = $scraper->fetchServerEmbed(
                                        $server['post_id'],
                                        $server['type'],
                                        $server['nume'],
                                        $server['nonce'] ?? '',
                                        $episode['url']
                                    );
                                    usleep(($this->delay / 2) * 1000);
                                }

                                if (!empty($embedUrl) && !$this->isInternalServer($embedUrl)) {
                                    $validServers[] = [
                                        'name' => $server['name'],
                                        'url' => $embedUrl,
                                    ];
                                    $totalServers++;
                                }
                            }

                            if (count($validServers) > 0) {
                                $this->addLog("  ✅ Episode {$episodeNumber}: " . count($validServers) . " servers");

                                // Sync to database if anime matched and syncToDatabase enabled
                                if ($this->syncToDatabase && $animeId && !empty($validServers)) {
                                    $this->syncServersToDatabase($validServers, $episodeNumber, $episode['title'] ?? null, $animeId);
                                }
                            }
                        }

                        usleep($this->delay * 1000);
                    }

                    $this->batchResults[] = [
                        'url' => $url,
                        'title' => $animeTitle,
                        'success' => true,
                        'matched' => $animeId ? true : false,
                        'episodes' => count($episodes),
                        'servers' => $totalServers,
                        'synced' => $this->syncToDatabase && $animeId,
                    ];

                    $this->addLog("✅ Complete: {$totalServers} servers from " . count($episodes) . " episodes");
                    if ($this->syncToDatabase && $animeId) {
                        $this->addLog("  💾 Synced to database!");
                    }
                    $this->updateBatchCache();

                } catch (\Exception $e) {
                    $this->addLog("❌ Exception: " . $e->getMessage());
                    $this->updateBatchCache();
                    $this->batchResults[] = [
                        'url' => $url,
                        'title' => $animeTitle,
                        'success' => false,
                        'error' => $e->getMessage(),
                        'episodes' => 0,
                        'servers' => 0,
                    ];
                }
            }

            $this->scrapeProgress = 100;
            $this->scrapeStatus = 'done';
            $this->addLog("\n✅ Batch scrape complete!");
            $this->updateBatchCache();

            Notification::make()
                ->title('Batch Scrape Complete!')
                ->success()
                ->body("Processed " . count($this->batchResults) . " animes")
                ->send();

        } catch (\Exception $e) {
            $this->addLog("❌ Fatal Error: " . $e->getMessage());
            $this->scrapeStatus = 'error';
            $this->updateBatchCache();

            Notification::make()
                ->title('Batch Scrape Failed')
                ->danger()
                ->body($e->getMessage())
                ->send();
        }

        $this->isScraping = false;
    }

    protected function updateBatchCache(): void
    {
        $cacheKey = $this->cacheBatchKey();
        Cache::put($cacheKey, [
            'progress' => $this->scrapeProgress,
            'status' => $this->scrapeStatus,
            'current' => $this->batchCurrent,
            'total' => $this->batchTotal,
            'logs' => $this->scrapeLogs,
            'results' => $this->batchResults,
        ], now()->addMinutes(60));
    }

    public function pollBatchProgress(): void
    {
        $state = Cache::get($this->cacheBatchKey());
        if (!$state) {
            return;
        }

        $this->scrapeLogs = $state['logs'] ?? [];
        $this->scrapeProgress = $state['progress'] ?? 0;
        $this->scrapeStatus = $state['status'] ?? 'idle';
        $this->batchCurrent = $state['current'] ?? 0;
        $this->batchTotal = $state['total'] ?? 0;
        $this->batchResults = $state['results'] ?? [];

        if (in_array($this->scrapeStatus, ['done', 'error'])) {
            $this->isScraping = false;

            if ($this->scrapeStatus === 'done') {
                Notification::make()
                    ->title('Batch Scrape Complete!')
                    ->success()
                    ->body('Check the results below')
                    ->send();
            }

            if ($this->scrapeStatus === 'error') {
                Notification::make()
                    ->title('Batch Scrape Failed')
                    ->danger()
                    ->body('Check the logs for details')
                    ->send();
            }
        }
    }

    protected function cacheBatchKey(): string
    {
        return 'animesail_batch_scrape:' . Auth::id();
    }
    
    public function downloadResults()
    {
        if (empty($this->scrapeResults)) {
            Notification::make()
                ->title('No Results')
                ->warning()
                ->body('Please scrape first')
                ->send();
            return;
        }
        
        $json = json_encode($this->scrapeResults, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        $filename = 'animesail_scrape_' . date('Y-m-d_His') . '.json';
        
        return response()->streamDownload(function () use ($json) {
            echo $json;
        }, $filename, [
            'Content-Type' => 'application/json',
        ]);
    }
}
