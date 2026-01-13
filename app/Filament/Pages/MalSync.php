<?php

namespace App\Filament\Pages;

use App\Services\MyAnimeListService;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Jobs\MalSyncJob;

class MalSync extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-download';
    
    protected static ?string $navigationLabel = 'MAL Sync';
    
    protected static ?string $title = 'MyAnimeList Sync';
    
    protected static ?int $navigationSort = 99;

    protected static string $view = 'filament.pages.mal-sync';
    
    public $syncType = 'top';
    public $limit = 10;
    public $searchQuery = '';
    public $malId = '';
    public $downloadImages = true;
    public $season = '';
    public $year = '';
    
    public $syncResults = null;
    public $isSyncing = false;
    public $syncLogs = [];
    public $syncProgress = 0;
    public $syncStatus = 'idle';

    protected function getFormSchema(): array
    {
        return [
            Select::make('syncType')
                ->label('Sync Type')
                ->options([
                    'top' => '🏆 Top Anime (By Rating)',
                    'seasonal' => '📅 Seasonal Anime',
                    'search' => '🔍 Search Specific Anime',
                    'mal_id' => '🔢 Search by MAL ID',
                ])
                ->required()
                ->reactive()
                ->helperText('Choose what type of anime to sync from MyAnimeList'),
            
            TextInput::make('searchQuery')
                ->label('Search Query')
                ->visible(fn ($get) => $get('syncType') === 'search')
                ->required(fn ($get) => $get('syncType') === 'search')
                ->placeholder('e.g., Naruto, One Piece')
                ->helperText('Enter anime title to search'),
            
            TextInput::make('malId')
                ->label('MAL ID')
                ->visible(fn ($get) => $get('syncType') === 'mal_id')
                ->required(fn ($get) => $get('syncType') === 'mal_id')
                ->placeholder('e.g., 21 (One Piece), 16498 (Attack on Titan)')
                ->helperText('Enter MyAnimeList anime ID (from URL: myanimelist.net/anime/ID)'),
            
            Select::make('season')
                ->label('Season')
                ->options([
                    'all' => 'All Seasons',
                    'winter' => 'Winter (Jan-Mar)',
                    'spring' => 'Spring (Apr-Jun)',
                    'summer' => 'Summer (Jul-Sep)',
                    'fall' => 'Fall (Oct-Dec)',
                ])
                ->visible(fn ($get) => $get('syncType') === 'seasonal')
                ->placeholder('Current season')
                ->helperText('Leave empty for current season, or select All'),
            
            TextInput::make('year')
                ->label('Year')
                ->numeric()
                ->visible(fn ($get) => $get('syncType') === 'seasonal')
                ->placeholder(date('Y'))
                ->helperText('Leave empty for current year'),
            
            TextInput::make('limit')
                ->label('Limit (optional)')
                ->numeric()
                ->default(null)
                ->minValue(1)
                ->helperText('Leave empty to fetch all available anime'),
            
            Toggle::make('downloadImages')
                ->label('Download Poster Images')
                ->default(true)
                ->helperText('Download and save poster images locally'),
        ];
    }

    public function syncAnime()
    {
        $this->validate();
        
        $this->isSyncing = true;
        $this->syncLogs = [];
        $this->syncProgress = 0;
        $this->syncStatus = 'running';

        $key = $this->cacheKey();
        Cache::put($key, [
            'progress' => 0,
            'status' => 'running',
            'logs' => [
                [
                    'time' => now()->format('H:i:s'),
                    'message' => '🚀 Starting sync process...'
                ]
            ],
            'error' => null,
        ], now()->addMinutes(30));

        MalSyncJob::dispatch(Auth::id(), [
            'syncType' => $this->syncType,
            'limit' => $this->limit,
            'searchQuery' => $this->searchQuery,
            'malId' => $this->malId,
            'season' => $this->season,
            'year' => $this->year,
            'downloadImages' => $this->downloadImages,
        ]);
    }
    
    protected function addLog($message)
    {
        $this->syncLogs[] = [
            'time' => now()->format('H:i:s'),
            'message' => $message,
        ];
    }

    public function pollSync()
    {
        $state = Cache::get($this->cacheKey());
        if (!$state) {
            return;
        }

        $logs = $state['logs'] ?? [];
        // Normalize logs in case legacy string entries exist
        if (is_string($logs)) {
            $logs = [
                [
                    'time' => now()->format('H:i:s'),
                    'message' => $logs,
                ],
            ];
        }
        if (!empty($logs) && isset($logs[0]) && is_string($logs[0])) {
            $logs = collect($logs)->map(fn ($msg) => [
                'time' => now()->format('H:i:s'),
                'message' => $msg,
            ])->toArray();
        }

        $this->syncLogs = $logs;
        $this->syncProgress = $state['progress'] ?? 0;
        $this->syncStatus = $state['status'] ?? 'idle';

        if (in_array($this->syncStatus, ['done', 'error'])) {
            $this->isSyncing = false;

            if ($this->syncStatus === 'done') {
                Notification::make()
                    ->title('Sync Successful!')
                    ->success()
                    ->body('Sync selesai, cek log untuk detail hasil.')
                    ->send();
            }

            if ($this->syncStatus === 'error' && !empty($state['error'])) {
                Notification::make()
                    ->title('Sync Failed')
                    ->danger()
                    ->body($state['error'])
                    ->send();
            }
        }
    }

    private function cacheKey(): string
    {
        return 'mal_sync:' . Auth::id();
    }
}
