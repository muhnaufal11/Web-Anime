<?php

namespace App\Filament\Resources;

use App\Filament\Resources\EpisodeResource\Pages;
use App\Models\Episode;
use App\Models\VideoServer;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\HtmlString; // Import penting untuk teks HTML helper
use Filament\Forms\Components\Section; // Import untuk layout Section
use Filament\Forms\Components\FileUpload; // Import untuk upload file
use Filament\Forms\Components\TextInput; // Import untuk input text

class EpisodeResource extends Resource
{
    protected static ?string $model = Episode::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                            ->helperText('Auto-generated dari judul'),

                        Forms\Components\Textarea::make('description')
                            ->label('Deskripsi (Opsional)'),

                        Forms\Components\Select::make('anime_id')
                            ->relationship('anime', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Cari & pilih anime')
                            ->required()
                            ->label('Anime'),
                    ]),

                // --- BAGIAN VIDEO SOURCE (DUAL MODE) ---
                Section::make('Video Source')
                    ->description('Pilih metode upload: Upload langsung (file kecil) atau Manual Filename (file besar via FileBrowser).')
                    ->schema([
                        
                        // OPSI A: Upload Biasa (Hanya untuk file kecil <100MB)
                        FileUpload::make('video_upload_kecil')
                            ->label('Opsi A: Upload Video Kecil (<100MB)')
                            ->directory('videos') // Disimpan di storage/app/public/videos
                            ->reactive()
                            ->afterStateUpdated(fn ($state, callable $set) => 
                                // Jika upload berhasil, set URL otomatis
                                $set('embed_url', $state ? 'storage/videos/' . $state->getClientOriginalName() : null)
                            )
                            ->helperText('Gunakan ini HANYA untuk trailer atau file kecil. Jangan untuk full episode 20GB.'),

                        // OPSI B: Manual Filename (Integrasi FileBrowser CasaOS)
                        TextInput::make('manual_filename')
                            ->label('Opsi B: Nama File Besar (20GB+)')
                            ->placeholder('Contoh: onepiece-ep1000.mp4')
                            ->helperText(new HtmlString('
                                <div style="margin-top:5px; padding:10px; background:#f3f4f6; border:1px solid #d1d5db; border-radius:5px; color:#1f2937;">
                                    <b>Cara Upload File Besar (Anti-Gagal):</b>
                                    <ol style="list-style-type:decimal; margin-left:15px; margin-bottom:0;">
                                        <li>Buka aplikasi <b>FileBrowser</b> atau <b>Files</b> di CasaOS.</li>
                                        <li>Upload video ke folder: <code>storage/app/public</code></li>
                                        <li>Copy nama filenya, lalu paste di kotak ini.</li>
                                    </ol>
                                </div>
                            '))
                            ->reactive()
                            ->afterStateUpdated(function ($state, callable $set) {
                                // Otomatis format path storage
                                if ($state) {
                                    $set('embed_url', 'storage/' . $state);
                                }
                            }),

                        // FIELD FINAL: URL yang akan disimpan ke Database
                        TextInput::make('embed_url')
                            ->label('Final Video URL (Auto-filled)')
                            ->required()
                            ->readOnly() // Admin tidak perlu edit ini manual
                            ->columnSpanFull()
                            ->helperText('Pastikan kotak ini terisi otomatis setelah Anda mengisi Opsi A atau B.'),
                    ])
                    ->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('episode_number')
                    ->sortable()
                    ->label('Eps'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('anime.title')
                    ->searchable()
                    ->label('Anime'),
                Tables\Columns\TextColumn::make('embed_url')
                    ->limit(30)
                    ->label('Video URL'),
            ])
            ->filters([
                //
            ])
            ->defaultSort('episode_number', 'asc')
            ->actions([
                Tables\Actions\EditAction::make(),
                
                // Action Upload Lokal (Per Episode)
                Tables\Actions\Action::make('upload_local')
                    ->label('Upload Lokal')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
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
                                ]
                            );

                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                        }

                        \Filament\Notifications\Notification::make()
                                ->title('Upload berhasil')
                                ->success()
                            ->body('Server ditambahkan: ' . ($created + $updated) . ' entri')
                            ->send();
                    }),

                // Action Sync Servers (Per Episode)
                Tables\Actions\Action::make('sync_servers')
                    ->label('Sync Servers')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('episode_html')
                            ->label('Episode HTML (opsional)')
                            ->rows(8)
                            ->placeholder('Paste HTML halaman episode...'),
                        Forms\Components\FileUpload::make('episode_html_file')
                            ->label('Upload Episode HTML (opsional)')
                            ->acceptedFileTypes(['text/html','text/plain'])
                            ->directory('uploads/html-episodes')
                            ->preserveFilenames(),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server lama yang tidak ditemukan')
                            ->default(false),
                    ])
                    ->action(function (Episode $record, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);

                        $html = null;
                        if (!empty($data['episode_html'])) {
                            $html = $data['episode_html'];
                        } elseif (!empty($data['episode_html_file'])) {
                            $path = storage_path('app/public/' . $data['episode_html_file']);
                            if (is_file($path)) {
                                $html = file_get_contents($path);
                            }
                        }

                        if (empty($html)) {
                            \Filament\Notifications\Notification::make()->title('HTML Required')->danger()->send();
                            return;
                        }

                        $servers = $service->getEpisodeServersFromHtml($html);
                        
                        if (empty($servers)) {
                            \Filament\Notifications\Notification::make()->title('No servers found')->warning()->send();
                            return;
                        }

                        if (!empty($data['delete_existing'])) {
                            $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                            if (!empty($keepUrls)) {
                                VideoServer::where('episode_id', $record->id)
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
                            
                            $vs = VideoServer::updateOrCreate(
                                [
                                    'episode_id' => $record->id,
                                    'embed_url' => $serverData['url'],
                                ],
                                [
                                    'server_name' => $serverData['name'] ?? 'Unknown',
                                    'embed_url' => $embedCode,
                                    'is_active' => true,
                                ]
                            );
                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
                        }

                        // Cleanup file
                        if (!empty($data['episode_html_file'])) {
                            Storage::disk('public')->delete($data['episode_html_file']);
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Sync completed')
                            ->success()
                            ->body("Created: {$created} | Updated: {$updated}")
                            ->send();
                    })
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                
                // Bulk Upload Lokal
                Tables\Actions\BulkAction::make('bulk_upload_local')
                    ->label('Bulk Upload Video Lokal')
                    ->icon('heroicon-o-upload')
                    ->color('primary')
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
                        if (empty($files)) return;

                        $serverName = $data['server_name'] ?? 'Server Admin 720p';
                        $map = [];

                        foreach ($files as $filePath) {
                            $filename = urldecode(basename($filePath));
                            if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $m) || preg_match('/[\-_](\d+)\.(?:mp4)$/i', $filename, $m)) {
                                $map[(int) $m[1]][] = $filePath;
                            }
                        }

                        $created = 0; $updated = 0;
                        foreach ($records as $episode) {
                            $epNum = $episode->episode_number;
                            if (isset($map[$epNum])) {
                                foreach ($map[$epNum] as $filePath) {
                                    $url = Storage::disk('public')->url($filePath);
                                    VideoServer::updateOrCreate(
                                        ['episode_id' => $episode->id, 'server_name' => $serverName],
                                        ['embed_url' => $url, 'is_active' => true]
                                    );
                                    $updated++; 
                                }
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Bulk upload selesai')
                            ->success()
                            ->body("Processed {$updated} videos.")
                            ->send();
                    }),

                // Bulk Sync Servers
                Tables\Actions\BulkAction::make('bulk_sync_servers')
                    ->label('Bulk Sync Servers')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('html_content')
                            ->label('HTML Content')
                            ->rows(6)
                            ->placeholder('Paste HTML...'),
                        Forms\Components\FileUpload::make('html_files')
                            ->label('Upload HTML Files')
                            ->multiple()
                            ->directory('uploads/bulk-html')
                            ->preserveFilenames(),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server lama')
                            ->default(false),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);
                        $htmlFiles = $data['html_files'] ?? [];
                        $episodeHtmlMap = [];
                        
                        foreach ($htmlFiles as $file) {
                            $path = storage_path('app/public/' . $file);
                            if (is_file($path)) {
                                $filename = urldecode(basename($file));
                                $content = file_get_contents($path);
                                if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $m) || preg_match('/[\s\-_](\d+)\.(?:html|txt|htm)$/i', $filename, $m)) {
                                    $episodeHtmlMap[(int) $m[1]] = $content;
                                }
                            }
                        }
                        
                        $processed = 0;
                        foreach ($records as $episode) {
                            $html = $episodeHtmlMap[$episode->episode_number] ?? ($data['html_content'] ?? null);
                            if (!$html) continue;
                            
                            $servers = $service->getEpisodeServersFromHtml($html);
                            if (empty($servers)) continue;
                            
                            if (!empty($data['delete_existing'])) {
                                $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                                VideoServer::where('episode_id', $episode->id)->whereNotIn('embed_url', $keepUrls)->delete();
                            }
                            
                            foreach ($servers as $s) {
                                $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode($s['url'], $s['name'] ?? null);
                                VideoServer::updateOrCreate(
                                    ['episode_id' => $episode->id, 'embed_url' => $s['url']],
                                    ['server_name' => $s['name'] ?? 'Unknown', 'embed_url' => $embedCode, 'is_active' => true]
                                );
                            }
                            $processed++;
                        }
                        
                        // Cleanup
                        foreach ($htmlFiles as $file) {
                            if (Storage::disk('public')->exists($file)) Storage::disk('public')->delete($file);
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Sync Completed')
                            ->success()
                            ->body("Processed: {$processed} episodes")
                            ->send();
                    })
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