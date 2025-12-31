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

class EpisodeResource extends Resource
{
    protected static ?string $model = Episode::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('episode_number')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->reactive()
                    ->afterStateUpdated(fn (callable $set, $state) => $set('slug', Str::slug($state))),
                Forms\Components\TextInput::make('slug')
                    ->required()
                    ->maxLength(255)
                    ->helperText('Auto-generated dari title'),
                Forms\Components\Textarea::make('description'),
                Forms\Components\Select::make('anime_id')
                    ->relationship('anime', 'title')
                    ->searchable()
                    ->preload()
                    ->placeholder('Cari & pilih anime')
                    ->required(),
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
                Tables\Actions\Action::make('sync_servers')
                    ->label('Sync Servers')
                    ->icon('heroicon-o-link')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('episode_html')
                            ->label('Episode HTML (opsional)')
                            ->rows(8)
                            ->placeholder('Paste HTML halaman episode untuk parsing tanpa jaringan'),
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

                        // Determine HTML input precedence: pasted > uploaded > none
                        $html = null;
                        if (!empty($data['episode_html'])) {
                            $html = $data['episode_html'];
                        } elseif (!empty($data['episode_html_file'])) {
                            $path = storage_path('app/public/' . $data['episode_html_file']);
                            if (is_file($path)) {
                                $html = file_get_contents($path);
                            }
                        }

                        // HTML is now required
                        if (empty($html)) {
                            \Filament\Notifications\Notification::make()
                                ->title('HTML Required')
                                ->danger()
                                ->body('Mohon paste atau upload HTML halaman episode terlebih dahulu.')
                                ->send();
                            return;
                        }

                        // Parse servers from HTML
                        $servers = $service->getEpisodeServersFromHtml($html);
                        
                        if (empty($servers)) {
                            \Filament\Notifications\Notification::make()
                                ->title('No servers found')
                                ->warning()
                                ->body('Tidak ada video server yang ditemukan dalam HTML ini.')
                                ->send();
                            return;
                        }

                        // Optional delete existing not found
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
                                ]
                            );
                            if ($vs->wasRecentlyCreated) { $created++; } else { $updated++; }
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
                Tables\Actions\BulkAction::make('bulk_sync_servers')
                    ->label('Bulk Sync Servers')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('html_content')
                            ->label('HTML Content (untuk semua episode)')
                            ->rows(6)
                            ->placeholder('Paste HTML halaman yang berisi video servers...')
                            ->helperText('Opsional: Paste HTML yang sama untuk semua episode yang dipilih'),
                        Forms\Components\FileUpload::make('html_files')
                            ->label('Upload HTML Files (per episode)')
                            ->multiple()
                            ->acceptedFileTypes(['text/html', 'text/plain', '.html', '.htm', '.txt'])
                            ->directory('uploads/bulk-html')
                            ->preserveFilenames() // <--- WAJIB: Agar nama file asli tidak berubah
                            ->helperText('Upload file HTML. Sistem otomatis mencocokkan "Episode X" di nama file dengan nomor episode.'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server lama yang tidak ditemukan')
                            ->default(false),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);
                        $globalHtml = $data['html_content'] ?? '';
                        $htmlFiles = $data['html_files'] ?? [];
                        
                        $episodeHtmlMap = [];
                        
                        // --- PROSES MAPPING FILE ---
                        foreach ($htmlFiles as $file) {
                            $path = storage_path('app/public/' . $file);
                            
                            if (is_file($path)) {
                                // [FIX 1] urldecode: Mengatasi spasi yg jadi %20
                                $filename = urldecode(basename($file));
                                $content = file_get_contents($path);
                                
                                // [FIX 2] Regex yang lebih fleksibel & akurat
                                // Logic: Cari kata "Episode" atau "Ep", abaikan spasi/strip, ambil angkanya
                                if (preg_match('/(?:Episode|Ep)[^0-9]*(\d+)/i', $filename, $matches)) {
                                    $epNum = (int) $matches[1];
                                    $episodeHtmlMap[$epNum] = $content;
                                } 
                                // Logic Backup: Cari angka di akhir nama file (misal: "Judul - 05.html")
                                elseif (preg_match('/[\s\-_](\d+)\.(?:html|txt|htm)$/i', $filename, $matches)) {
                                    $epNum = (int) $matches[1];
                                    $episodeHtmlMap[$epNum] = $content;
                                }
                            }
                        }
                        
                        if (empty($globalHtml) && empty($episodeHtmlMap)) {
                            \Filament\Notifications\Notification::make()
                                ->title('File Tidak Terbaca')
                                ->danger()
                                ->body('Tidak ada file HTML yang cocok dengan nomor episode, atau HTML kosong.')
                                ->send();
                            return;
                        }
                        
                        $totalCreated = 0;
                        $totalUpdated = 0;
                        $processedEpisodes = 0;
                        $skippedEpisodes = 0;
                        
                        $recordsList = $records->sortBy('episode_number')->values();
                        
                        foreach ($recordsList as $episode) {
                            $html = null;
                            $epNum = $episode->episode_number;

                            // [FIX 3] HANYA PAKAI FILE YANG NOMORNYA COCOK
                            // Tidak ada lagi tebak-tebakan urutan array
                            if (isset($episodeHtmlMap[$epNum])) {
                                $html = $episodeHtmlMap[$epNum];
                            } elseif (!empty($globalHtml)) {
                                $html = $globalHtml;
                            }
                            
                            // Jika tidak ada file untuk nomor ini, SKIP.
                            if (empty($html)) {
                                $skippedEpisodes++;
                                continue;
                            }
                            
                            // Parsing
                            $servers = $service->getEpisodeServersFromHtml($html);
                            
                            if (empty($servers)) {
                                $skippedEpisodes++;
                                continue;
                            }
                            
                            // Hapus yang lama (opsional)
                            if (!empty($data['delete_existing'])) {
                                $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                                if (!empty($keepUrls)) {
                                    \App\Models\VideoServer::where('episode_id', $episode->id)
                                        ->whereNotIn('embed_url', $keepUrls)
                                        ->delete();
                                }
                            }
                            
                            // Simpan yang baru
                            foreach ($servers as $serverData) {
                                $embedCode = \App\Services\VideoEmbedHelper::toEmbedCode(
                                    $serverData['url'],
                                    $serverData['name'] ?? null
                                );
                                
                                $vs = \App\Models\VideoServer::updateOrCreate(
                                    [
                                        'episode_id' => $episode->id,
                                        'embed_url' => $serverData['url'],
                                    ],
                                    [
                                        'server_name' => $serverData['name'] ?? 'Unknown',
                                        'embed_url' => $embedCode,
                                        'is_active' => true,
                                    ]
                                );
                                if ($vs->wasRecentlyCreated) { $totalCreated++; } else { $totalUpdated++; }
                            }
                            $processedEpisodes++;
                        }
                        
                        $message = "Processed: {$processedEpisodes} | Created: {$totalCreated} | Updated: {$totalUpdated}";
                        if ($skippedEpisodes > 0) {
                            $message .= " | Skipped: {$skippedEpisodes} (No matching file)";
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Sync Completed')
                            ->success()
                            ->body($message)
                            ->send();
                    })
                    ->deselectRecordsAfterCompletion()
                    ->requiresConfirmation()
                    ->modalHeading('Bulk Sync Video Servers')
                    ->modalSubheading('Sync video servers untuk semua episode yang dipilih'),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
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