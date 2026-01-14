<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpisodeResource\Pages;
use App\Filament\Resources\EpisodeResource\RelationManagers;
use App\Models\Episode;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage; // <--- PENTING: Import Library Storage
use App\Models\VideoServer;

class EpisodeResource extends Resource
{
    protected static ?string $model = Episode::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // --- BAGIAN 1: DATA EPISODE (DATA LAMA) ---
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('episode_number')
                            ->required()
                            ->numeric()
                            ->label('Nomor Episode'),
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => $set('slug', Str::slug($state)))
                            ->label('Judul Episode'),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Auto-generated dari title'),
                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi'),
                        Forms\Components\Select::make('anime_id')
                            ->relationship('anime', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Cari & pilih anime')
                            ->required(),
                    ]),

                // --- BAGIAN 2: VIDEO SERVERS MANAGER (HANYA UNTUK ADMIN UPLOAD & SUPERADMIN) ---
                Forms\Components\Section::make('Video Servers (Manual & Upload)')
                    ->description('Kelola link video manual atau pilih file dari FileBrowser (Upload Center).')
                    ->visible(fn () => auth()->user()?->canUploadVideo())
                    ->schema([
                        Forms\Components\Repeater::make('videoServers')
                            ->relationship()
                            ->schema([
                                Forms\Components\TextInput::make('server_name')
                                    ->label('Nama Server')
                                    ->default('Server Admin')
                                    ->required(),

                                // DROPDOWN PILIH FILE VIDEO
                                Forms\Components\Select::make('manual_filename')
                                    ->label('Pilih File Video')
                                    ->placeholder('-- Pilih file video --')
                                    ->options(function () {
                                        // Ambil daftar file dari folder videos/episodes
                                        $files = [];
                                        $path = storage_path('app/public/videos/episodes');
                                        
                                        if (is_dir($path)) {
                                            // Gunakan scandir karena GLOB_BRACE tidak ada di Alpine
                                            $allFiles = scandir($path);
                                            $extensions = ['mp4', 'mkv', 'webm'];
                                            
                                            foreach ($allFiles as $file) {
                                                if ($file === '.' || $file === '..') continue;
                                                
                                                $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                                                if (in_array($ext, $extensions)) {
                                                    $fullPath = $path . '/' . $file;
                                                    $size = round(filesize($fullPath) / 1024 / 1024, 1); // MB
                                                    $files[$file] = "{$file} ({$size} MB)";
                                                }
                                            }
                                            // Sort by filename
                                            ksort($files);
                                        }
                                        
                                        return $files;
                                    })
                                    ->searchable()
                                    ->helperText('Pilih video yang sudah di-upload via FileBrowser.')
                                    ->dehydrated(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) {
                                        if ($state) {
                                            $url = Storage::disk('public')->url('videos/episodes/' . $state);
                                            $set('embed_url', $url);
                                        }
                                    })
                                    ->suffixAction(
                                        Forms\Components\Actions\Action::make('open_filebrowser')
                                            ->icon('heroicon-o-external-link')
                                            ->url('http://192.168.100.13:8081', true)
                                            ->tooltip('Buka FileBrowser untuk upload video baru')
                                    ),

                                Forms\Components\TextInput::make('embed_url')
                                    ->label('URL Video Final')
                                    ->required()
                                    ->columnSpan('full')
                                    ->helperText('Terisi otomatis jika memilih file di atas.'),
                                
                                Forms\Components\Toggle::make('is_active')
                                    ->label('Aktif')
                                    ->default(true),
                                    
                                Forms\Components\Toggle::make('is_default')
                                    ->label('Default Server')
                                    ->helperText('Server yang dipilih pertama kali')
                                    ->default(false),
                            ])
                            ->createItemButtonLabel('Tambah Server Manual')
                            ->defaultItems(0)
                            ->columns(2)
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('episode_number')
                    ->sortable(),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('anime.title')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->defaultSort('episode_number', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // ACTION SET DEFAULT SERVER
                Tables\Actions\Action::make('set_default_server')
                    ->label('Set Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->form([
                        Forms\Components\Select::make('server_id')
                            ->label('Pilih Server Default')
                            ->options(function (Episode $record) {
                                return $record->videoServers()
                                    ->where('is_active', true)
                                    ->pluck('server_name', 'id');
                            })
                            ->required()
                            ->helperText('Server ini akan dipilih pertama kali saat user membuka video'),
                    ])
                    ->action(function (Episode $record, array $data) {
                        // Reset all servers
                        $record->videoServers()->update(['is_default' => false]);
                        
                        // Set selected as default
                        \App\Models\VideoServer::where('id', $data['server_id'])
                            ->update(['is_default' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Default server updated')
                            ->success()
                            ->send();
                    })
                    ->visible(fn (Episode $record) => $record->videoServers()->where('is_active', true)->count() > 0),
                
                // ACTION UPLOAD LOKAL (HANYA ADMIN UPLOAD & SUPERADMIN)
                Tables\Actions\Action::make('upload_local')
                    ->label('Upload Video Lokal')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->canUploadVideo())
                    ->form([
                        Forms\Components\View::make('filament.components.upload-progress')
                            ->viewData(['target' => 'video_files']),
                        Forms\Components\TextInput::make('server_name')
                            ->label('Nama Server')
                            ->default('Server Admin 720p')
                            ->required(),
                        Forms\Components\FileUpload::make('video_files')
                            ->label('File Video (MP4)')
                            ->multiple()
                            ->required()
                            ->directory('videos/episodes')
                            ->disk('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['video/mp4'])
                            ->helperText('Bisa pilih banyak file; setiap file jadi satu server otomatis.'),
                    ])
                    ->action(function (Episode $record, array $data) {
                        $files = $data['video_files'] ?? [];
                        if (empty($files)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Upload gagal')
                                ->danger()
                                ->body('File video wajib diisi.')
                                ->send();
                            return;
                        }

                        $serverName = $data['server_name'] ?? 'Server Admin 720p';
                        $created = 0; $updated = 0;

                        foreach ($files as $filePath) {
                            $url = Storage::disk('public')->url($filePath);
                            $quality = null;
                            if (preg_match('/(1080|720|480|360)p/i', $filePath, $m)) {
                                $quality = $m[1] . 'p';
                            }
                            $name = $serverName;
                            if ($quality && stripos($serverName, $quality) === false) {
                                $name = $serverName . ' ' . $quality;
                            }

                            $vs = VideoServer::updateOrCreate(
                                [
                                    'episode_id' => $record->id,
                                    'server_name' => $name,
                                ],
                                [
                                    'embed_url' => $url,
                                    'is_active' => true,
                                    'source' => 'manual',
                                ]
                            );

                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                        }

                        // Admin log when a local upload happens - BAYARAN SESUAI RATE USER
                        $user = auth()->user()?->refresh();
                        if ($user && $user->isAdmin()) {
                            $rate = $user->payment_rate ?? 500;
                            \App\Models\AdminEpisodeLog::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'episode_id' => $record->id,
                                ],
                                [
                                    'amount' => $rate,
                                    'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                    'note' => 'Upload video manual (' . $serverName . ') - Rp ' . number_format($rate, 0, ',', '.'),
                                ]
                            );
                        }

                        \Filament\Notifications\Notification::make()
                                ->title('Upload berhasil')
                                ->success()
                            ->body('Server ditambahkan: ' . ($created + $updated) . ' entri')
                            ->send();
                    }),
                
                // ACTION SYNC SERVERS (HANYA ADMIN SYNC & SUPERADMIN)
                Tables\Actions\Action::make('sync_servers')
                    ->label('Sync Servers')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->canSyncServer())
                    ->form([
                        Forms\Components\TextInput::make('episode_url')
                            ->label('URL Episode (opsional)')
                            ->url()
                            ->placeholder('https://nontonanimeid.boats/episode/...')
                            ->helperText('Masukkan URL episode untuk fetch server via AJAX (NontonAnimeID)'),
                        Forms\Components\Textarea::make('episode_html')
                            ->label('Episode HTML (opsional)')
                            ->rows(6)
                            ->placeholder('Atau paste HTML halaman episode untuk parsing offline'),
                        Forms\Components\FileUpload::make('episode_html_file')
                            ->label('Upload Episode HTML (opsional)')
                            ->acceptedFileTypes(['text/html','text/plain'])
                            ->directory('uploads/html-episodes')
                            ->preserveFilenames()
                            ->helperText('Alternatif: upload file HTML halaman episode'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server yang tidak ditemukan')
                            ->helperText('Jika aktif, server lama yang tidak ada di hasil parse akan dihapus'),
                    ])
                    ->action(function (Episode $record, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);

                        // Prioritas: URL > HTML paste > HTML file
                        $url = $data['episode_url'] ?? null;
                        $html = null;
                        $servers = [];
                        
                        // Method 1: Fetch dari URL (AJAX)
                        if (!empty($url)) {
                            if (str_contains($url, 'nontonanimeid')) {
                                try {
                                    $fetcher = new \App\Services\NontonAnimeIdFetcher();
                                    $result = $fetcher->syncToEpisode($record->id, $url, !empty($data['delete_existing']));
                                    
                                    if ($result['success']) {
                                        // --- AUTO CREATE ADMIN LOG ---
                                        $user = auth()->user()?->refresh();
                                        if ($user && $user->isAdmin() && ($result['created'] > 0 || $result['updated'] > 0)) {
                                            $rate = $user->payment_rate ?? 500;
                                            \App\Models\AdminEpisodeLog::updateOrCreate(
                                                [
                                                    'user_id' => $user->id,
                                                    'episode_id' => $record->id,
                                                ],
                                                [
                                                    'amount' => $rate,
                                                    'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                                    'note' => "Sync via URL (Created: {$result['created']}, Updated: {$result['updated']}) - Rp " . number_format($rate, 0, ',', '.'),
                                                ]
                                            );
                                        }
                                        
                                        \Filament\Notifications\Notification::make()
                                            ->title('Sync berhasil!')
                                            ->success()
                                            ->body("Created: {$result['created']} | Updated: {$result['updated']} | Total: {$result['total']}")
                                            ->send();
                                    } else {
                                        \Filament\Notifications\Notification::make()
                                            ->title('Fetch gagal')
                                            ->danger()
                                            ->body($result['error'])
                                            ->send();
                                    }
                                    return;
                                } catch (\Exception $e) {
                                    \Filament\Notifications\Notification::make()
                                        ->title('Error')
                                        ->danger()
                                        ->body('Fetch URL gagal: ' . $e->getMessage())
                                        ->send();
                                    return;
                                }
                            } else {
                                \Filament\Notifications\Notification::make()
                                    ->title('URL tidak didukung')
                                    ->danger()
                                    ->body('Saat ini hanya NontonAnimeID yang didukung untuk fetch via URL.')
                                    ->send();
                                return;
                            }
                        }
                        
                        // Method 2: Parse HTML
                        if (!empty($data['episode_html'])) {
                            $html = $data['episode_html'];
                        } elseif (!empty($data['episode_html_file'])) {
                            $path = storage_path('app/public/' . $data['episode_html_file']);
                            if (is_file($path)) {
                                $html = file_get_contents($path);
                            }
                        }

                        if (empty($html)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Input Required')
                                ->danger()
                                ->body('Mohon masukkan URL, paste HTML, atau upload file HTML.')
                                ->send();
                            return;
                        }

                        $servers = $service->getEpisodeServersFromHtml($html);
                        
                        if (empty($servers)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No servers found')
                                ->warning()
                                ->body('Tidak ada video server yang ditemukan dalam HTML ini.')
                                ->send();
                            return;
                        }

                        if (!empty($data['delete_existing'])) {
                            $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                            if (!empty($keepUrls)) {
                                \App\Models\VideoServer::where('episode_id', $record->id)
                                    ->whereNotIn('embed_url', $keepUrls)
                                    ->delete();
                            }
                        }

                        $created = 0; $updated = 0;
                        foreach ($servers as $serverData) {
                            $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode(
                                $serverData['url'],
                                $serverData['name'] ?? null
                            );
                            
                            $vs = \App\Models\VideoServer::updateOrCreate(
                                [
                                    'episode_id' => $record->id,
                                    'embed_url' => $serverData['url'],
                                ],
                                [
                                    'server_name' => $serverData['name'] ?? 'Unknown',
                                    'embed_url' => $embedCode,
                                    'is_active' => true,
                                    'source' => 'sync',
                                ]
                            );
                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                        }

                        // --- AUTO CREATE ADMIN LOG (SETIAP SYNC) - BAYARAN SESUAI RATE USER ---
                        $user = auth()->user()?->refresh();
                        if ($user && $user->isAdmin() && ($created > 0 || $updated > 0)) {
                            $rate = $user->payment_rate ?? 500;
                            \App\Models\AdminEpisodeLog::updateOrCreate(
                                [
                                    'user_id' => $user->id,
                                    'episode_id' => $record->id,
                                ],
                                [
                                    'amount' => $rate,
                                    'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                    'note' => "Sync video servers (Created: {$created}, Updated: {$updated}) - Rp " . number_format($rate, 0, ',', '.'),
                                ]
                            );
                        }

                        // --- AUTO CLEANUP SINGLE UPLOAD ---
                        // Hapus file setelah selesai diproses
                        if (!empty($data['episode_html_file'])) {
                            Storage::disk('public')->delete($data['episode_html_file']);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Sync servers completed')
                            ->success()
                            ->body("Created: {$created} | Updated: {$updated} | Total detected: " . count($servers))
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                
                // BULK SET DEFAULT SERVER
                Tables\Actions\BulkAction::make('bulk_set_default')
                    ->label('Bulk Set Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->form(function (\Illuminate\Database\Eloquent\Collection $records) {
                        // Ambil semua nama server unik dari episode yang dipilih
                        $episodeIds = $records->pluck('id')->toArray();
                        $serverNames = \App\Models\VideoServer::whereIn('episode_id', $episodeIds)
                            ->where('is_active', true)
                            ->pluck('server_name')
                            ->unique()
                            ->sort()
                            ->mapWithKeys(fn ($name) => [$name => $name])
                            ->toArray();
                        
                        return [
                            Forms\Components\Select::make('method')
                                ->label('Metode Pemilihan Server Default')
                                ->options([
                                    'name' => 'Pilih Nama Server',
                                    'priority' => 'Berdasarkan Prioritas',
                                    'first' => 'Server Pertama (Urutan ID)',
                                ])
                                ->default('name')
                                ->required()
                                ->reactive()
                                ->helperText('Pilih cara menentukan server default'),
                            Forms\Components\Select::make('server_name')
                                ->label('Pilih Server')
                                ->options($serverNames)
                                ->searchable()
                                ->visible(fn (callable $get) => $get('method') === 'name')
                                ->helperText('Server dengan nama ini akan dijadikan default'),
                            Forms\Components\TextInput::make('priority_list')
                                ->label('Prioritas Nama Server (pisah koma)')
                                ->default('Cepat, Kotakvideo, Server Admin, Lokal, U-hd, Nontonku')
                                ->placeholder('Cepat, Kotakvideo, Server Admin')
                                ->visible(fn (callable $get) => $get('method') === 'priority')
                                ->helperText('Server yang cocok pertama akan dijadikan default'),
                        ];
                    })
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $method = $data['method'] ?? 'name';
                        $updated = 0;
                        $skipped = 0;
                        
                        // Parse priority list
                        $priorities = [];
                        if ($method === 'priority' && !empty($data['priority_list'])) {
                            $priorities = array_map('trim', explode(',', $data['priority_list']));
                        }
                        
                        foreach ($records as $episode) {
                            $servers = $episode->videoServers()->where('is_active', true)->get();
                            
                            if ($servers->isEmpty()) {
                                $skipped++;
                                continue;
                            }
                            
                            $selectedServer = null;
                            
                            if ($method === 'first') {
                                // Pilih server pertama
                                $selectedServer = $servers->first();
                            } elseif ($method === 'name' && !empty($data['server_name'])) {
                                // Pilih berdasarkan nama exact match
                                $selectedServer = $servers->first(function ($s) use ($data) {
                                    return $s->server_name === $data['server_name'];
                                });
                            } elseif ($method === 'priority' && !empty($priorities)) {
                                // Pilih berdasarkan prioritas (partial match)
                                foreach ($priorities as $priority) {
                                    $selectedServer = $servers->first(function ($s) use ($priority) {
                                        return stripos($s->server_name, $priority) !== false;
                                    });
                                    if ($selectedServer) break;
                                }
                            }
                            
                            // Fallback ke server pertama jika tidak ada yang cocok
                            if (!$selectedServer) {
                                $selectedServer = $servers->first();
                            }
                            
                            if ($selectedServer) {
                                // Reset semua default
                                $episode->videoServers()->update(['is_default' => false]);
                                // Set yang dipilih
                                $selectedServer->update(['is_default' => true]);
                                $updated++;
                            } else {
                                $skipped++;
                            }
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Set Default Selesai')
                            ->success()
                            ->body("Updated: {$updated} episode | Skipped: {$skipped} (tidak ada server)")
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Set Default Server Massal')
                    ->modalSubheading('Pilih server default untuk semua episode yang dipilih'),
                
                // BULK UPLOAD LOCAL (HANYA ADMIN UPLOAD & SUPERADMIN)
                Tables\Actions\BulkAction::make('bulk_upload_local')
                    ->label('Bulk Upload Video Lokal')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
                    ->visible(fn () => auth()->user()?->canUploadVideo())
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\View::make('filament.components.upload-progress')
                            ->viewData(['target' => 'video_files']),
                        Forms\Components\TextInput::make('server_name')
                            ->label('Nama Server')
                            ->default('Server Admin 720p')
                            ->required(),
                        Forms\Components\FileUpload::make('video_files')
                            ->label('File Video (MP4)')
                            ->multiple()
                            ->required()
                            ->directory('videos/episodes')
                            ->disk('public')
                            ->preserveFilenames()
                            ->acceptedFileTypes(['video/mp4'])
                            ->helperText('Map otomatis ke episode berdasar nomor di nama file (contoh: Ep 3, episode-04).'),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $files = $data['video_files'] ?? [];
                        if (empty($files)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Upload gagal')
                                ->danger()
                                ->body('File video wajib diisi.')
                                ->send();
                            return;
                        }

                        $serverName = $data['server_name'] ?? 'Server Admin 720p';
                        $map = [];
                        $unmapped = 0;

                        foreach ($files as $filePath) {
                            $filename = urldecode(basename($filePath));
                            $epNum = null;
                            if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $m)) {
                                $epNum = (int) $m[1];
                            } elseif (preg_match('/[\-_](\d+)\.(?:mp4)$/i', $filename, $m)) {
                                $epNum = (int) $m[1];
                            }

                            if ($epNum) {
                                $map[$epNum][] = $filePath;
                            } else {
                                $unmapped++;
                            }
                        }

                        $created = 0; $updated = 0; $skipped = 0;
                        $recordsList = $records->sortBy('episode_number')->values();

                        foreach ($recordsList as $episode) {
                            $epNum = $episode->episode_number;
                            if (!isset($map[$epNum]) || empty($map[$epNum])) {
                                $skipped++;
                                continue;
                            }

                            foreach ($map[$epNum] as $filePath) {
                                $url = \Storage::disk('public')->url($filePath);
                                $quality = null;
                                if (preg_match('/(1080|720|480|360)p/i', $filePath, $m)) {
                                    $quality = $m[1] . 'p';
                                }

                                $name = $serverName;
                                if ($quality && stripos($serverName, $quality) === false) {
                                    $name = $serverName . ' ' . $quality;
                                }

                                $vs = \App\Models\VideoServer::updateOrCreate(
                                    [
                                        'episode_id' => $episode->id,
                                        'server_name' => $name,
                                    ],
                                    [
                                        'embed_url' => $url,
                                        'is_active' => true,
                                        'source' => 'manual',
                                    ]
                                );

                                if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                                
                                // --- AUTO CREATE ADMIN LOG PER EPISODE - BAYARAN SESUAI RATE USER ---
                                $user = auth()->user()?->refresh();
                                if ($user && $user->isAdmin()) {
                                    $rate = $user->payment_rate ?? 500;
                                    \App\Models\AdminEpisodeLog::updateOrCreate(
                                        [
                                            'user_id' => $user->id,
                                            'episode_id' => $episode->id,
                                        ],
                                        [
                                            'amount' => $rate,
                                            'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                            'note' => "Bulk upload video manual ({$name}) - Rp " . number_format($rate, 0, ',', '.'),
                                        ]
                                    );
                                }
                            }
                        }

                        $body = "Created: {$created} | Updated: {$updated}";
                        if ($skipped > 0) {
                            $body .= " | Skipped (no match): {$skipped}";
                        }
                        if ($unmapped > 0) {
                            $body .= " | File tak ter-mapping: {$unmapped}";
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Bulk upload selesai')
                            ->success()
                            ->body($body)
                            ->send();
                    }),

                // BULK SYNC SERVERS (HANYA ADMIN SYNC & SUPERADMIN)
                Tables\Actions\BulkAction::make('bulk_sync_servers')
                    ->label('Bulk Sync Servers')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->visible(fn () => auth()->user()?->canSyncServer())
                    ->form([
                        Forms\Components\Textarea::make('episode_urls')
                            ->label('URL Episodes (1 per baris)')
                            ->rows(4)
                            ->placeholder("https://nontonanimeid.boats/anime-episode-1/\nhttps://nontonanimeid.boats/anime-episode-2/")
                            ->helperText('⚠️ NontonAnimeID hanya sync 1 server (Cepat) karena proteksi anti-bot. Untuk server lain, gunakan sumber alternatif.'),
                        Forms\Components\Textarea::make('html_content')
                            ->label('HTML Content (untuk semua episode)')
                            ->rows(4)
                            ->placeholder('Atau paste HTML halaman episode...')
                            ->helperText('⚠️ Hanya server pertama (dari iframe) yang bisa diambil dari HTML.'),
                        Forms\Components\FileUpload::make('html_files')
                            ->label('Upload HTML Files (per episode)')
                            ->multiple()
                            ->acceptedFileTypes(['text/html', 'text/plain', '.html', '.htm', '.txt'])
                            ->directory('uploads/bulk-html')
                            ->preserveFilenames()
                            ->helperText('Upload file HTML per episode. Nama file harus mengandung nomor episode.'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server lama yang tidak ditemukan')
                            ->helperText('Hanya server sync yang dihapus. Server admin upload TIDAK akan dihapus.')
                            ->default(false),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);
                        $globalHtml = $data['html_content'] ?? '';
                        $htmlFiles = $data['html_files'] ?? [];
                        $episodeUrls = $data['episode_urls'] ?? '';
                        
                        $episodeHtmlMap = [];
                        $episodeUrlMap = [];
                        
                        // --- PARSE URLs (NontonAnimeID) ---
                        if (!empty($episodeUrls)) {
                            $urls = preg_split('/[\r\n]+/', trim($episodeUrls));
                            foreach ($urls as $url) {
                                $url = trim($url);
                                if (empty($url)) continue;
                                
                                // Extract episode number from URL
                                if (preg_match('/episode-?(\d+)/i', $url, $m)) {
                                    $epNum = (int) $m[1];
                                    $episodeUrlMap[$epNum] = $url;
                                }
                            }
                        }
                        
                        // --- BACA & MAPPING FILE ---
                        foreach ($htmlFiles as $file) {
                            $path = storage_path('app/public/' . $file);
                            
                            if (is_file($path)) {
                                $filename = urldecode(basename($file));
                                $content = file_get_contents($path);
                                
                                if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $matches)) {
                                    $epNum = (int) $matches[1];
                                    $episodeHtmlMap[$epNum] = $content;
                                } elseif (preg_match('/[\s\-_](\d+)\.(?:html|txt|htm)$/i', $filename, $matches)) {
                                    $epNum = (int) $matches[1];
                                    $episodeHtmlMap[$epNum] = $content;
                                }
                            }
                        }
                        
                        if (empty($globalHtml) && empty($episodeHtmlMap) && empty($episodeUrlMap)) {
                            \Filament\Notifications\Notification::make()
                                ->title('Input Required')
                                ->danger()
                                ->body('Mohon masukkan URL, paste HTML, atau upload file HTML.')
                                ->send();
                            return;
                        }
                        
                        $totalCreated = 0;
                        $totalUpdated = 0;
                        $processedEpisodes = 0;
                        $skippedEpisodes = 0;
                        
                        $recordsList = $records->sortBy('episode_number')->values();
                        $fetcher = new \App\Services\NontonAnimeIdFetcher();
                        
                        // --- PROSES DATA KE DATABASE ---
                        foreach ($recordsList as $episode) {
                            $epNum = $episode->episode_number;
                            $servers = [];
                            
                            // Prioritas: URL > HTML File > Global HTML
                            if (isset($episodeUrlMap[$epNum])) {
                                // Fetch via AJAX (NontonAnimeID)
                                try {
                                    $result = $fetcher->syncToEpisode($episode->id, $episodeUrlMap[$epNum], !empty($data['delete_existing']));
                                    if ($result['success']) {
                                        $totalCreated += $result['created'];
                                        $totalUpdated += $result['updated'];
                                        $processedEpisodes++;
                                        
                                        // Admin log
                                        $user = auth()->user()?->refresh();
                                        if ($user && $user->isAdmin()) {
                                            $rate = $user->payment_rate ?? 500;
                                            \App\Models\AdminEpisodeLog::updateOrCreate(
                                                ['user_id' => $user->id, 'episode_id' => $episode->id],
                                                [
                                                    'amount' => $rate,
                                                    'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                                    'note' => "Bulk sync via URL ({$result['created']} created) - Rp " . number_format($rate, 0, ',', '.'),
                                                ]
                                            );
                                        }
                                        continue; // Skip to next episode
                                    }
                                } catch (\Exception $e) {
                                    // Fall through to HTML parsing
                                }
                            }
                            
                            // Parse from HTML
                            $html = null;
                            if (isset($episodeHtmlMap[$epNum])) {
                                $html = $episodeHtmlMap[$epNum];
                            } elseif (!empty($globalHtml)) {
                                $html = $globalHtml;
                            }
                            
                            if (empty($html)) {
                                $skippedEpisodes++;
                                continue;
                            }
                            
                            $servers = $service->getEpisodeServersFromHtml($html);
                            if (empty($servers)) {
                                $skippedEpisodes++;
                                continue;
                            }
                            
                            if (!empty($data['delete_existing'])) {
                                $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                                if (!empty($keepUrls)) {
                                    \App\Models\VideoServer::where('episode_id', $episode->id)
                                        ->whereNotIn('embed_url', $keepUrls)
                                        ->where(function ($q) {
                                            $q->where('source', 'sync')
                                              ->orWhereNull('source');
                                        })
                                        ->delete();
                                }
                            }
                            
                            foreach ($servers as $serverData) {
                                $serverUrl = $serverData['url'] ?? '';
                                if (empty($serverUrl) || !preg_match('/^https?:\/\//i', $serverUrl)) {
                                    continue;
                                }
                                
                                if (str_contains($serverUrl, '/redirect/')) {
                                    continue;
                                }
                                
                                $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode(
                                    $serverUrl,
                                    $serverData['name'] ?? null
                                );
                                
                                $vs = \App\Models\VideoServer::updateOrCreate(
                                    [
                                        'episode_id' => $episode->id,
                                        'embed_url' => $serverUrl,
                                    ],
                                    [
                                        'server_name' => $serverData['name'] ?? 'Unknown',
                                        'embed_url' => $embedCode ?: $serverUrl,
                                        'is_active' => true,
                                        'source' => 'sync',
                                    ]
                                );
                                if ($vs->wasRecentlyCreated) { $totalCreated++; } else { $totalUpdated++; }
                            }
                            $processedEpisodes++;
                            
                            // Admin log
                            $user = auth()->user()?->refresh();
                            if ($user && $user->isAdmin() && !empty($servers)) {
                                $rate = $user->payment_rate ?? 500;
                                \App\Models\AdminEpisodeLog::updateOrCreate(
                                    ['user_id' => $user->id, 'episode_id' => $episode->id],
                                    [
                                        'amount' => $rate,
                                        'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                        'note' => "Bulk sync (" . count($servers) . " servers) - Rp " . number_format($rate, 0, ',', '.'),
                                    ]
                                );
                            }
                        }

                        // --- AUTO CLEANUP FILE ---
                        foreach ($htmlFiles as $file) {
                            if (Storage::disk('public')->exists($file)) {
                                Storage::disk('public')->delete($file);
                            }
                        }

                        $message = "Processed: {$processedEpisodes} | Created: {$totalCreated} | Updated: {$totalUpdated}";
                        if ($skippedEpisodes > 0) {
                            $message .= " | Skipped: {$skippedEpisodes}";
                        }
                        
                        // Show warning if only 1 server synced from NontonAnimeID
                        $notificationType = 'success';
                        if ($totalCreated + $totalUpdated <= $processedEpisodes && $processedEpisodes > 0) {
                            $message .= "\n⚠️ NontonAnimeID: Hanya 1 server per episode (anti-bot protection)";
                            $notificationType = 'warning';
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Sync Completed')
                            ->{$notificationType}()
                            ->body($message)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Sync Video Servers')
                    ->modalSubheading('Pilih metode: URL (fetch AJAX) atau HTML (parse offline)'),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEpisodes::route('/'),
            'create' => Pages\CreateEpisode::route('/create'),
            'edit' => Pages\EditEpisode::route('/{record}/edit'),
        ];
    }    
}