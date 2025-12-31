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
                            ->helperText('Upload file HTML per episode. Jika jumlah file = jumlah episode yang dipilih, akan dicocokkan urut. Atau nama file mengandung "Episode X" / "Ep X".'),
                        Forms\Components\Toggle::make('delete_existing')
                            ->label('Hapus server lama yang tidak ditemukan')
                            ->default(false),
                    ])
                    ->action(function (\Illuminate\Database\Eloquent\Collection $records, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);
                        $globalHtml = $data['html_content'] ?? '';
                        $htmlFiles = $data['html_files'] ?? [];
                        
                        // Build episode number to HTML mapping from uploaded files
                        $episodeHtmlMap = [];
                        $fileContents = []; // Store all file contents for fallback
                        
                        foreach ($htmlFiles as $file) {
    $path = storage_path('app/public/' . $file);
    if (is_file($path)) {
        $filename = basename($file); // Sekarang ini akan membaca nama ASLI
        $content = file_get_contents($path);
        $fileContents[] = $content;

        // --- UPDATE LOGIKA DETEKSI NOMOR ---
        
        // Coba cari pola "Episode 10", "Ep 10", "E10"
        if (preg_match('/(?:ep|episode|e)[._\-\s]*(\d+)/i', $filename, $matches)) {
            $epNum = (int) $matches[1];
            $episodeHtmlMap[$epNum] = $content;
        } 
        // Coba cari angka yang berdiri sendiri atau di akhir (misal: "One Piece - 1050.html")
        elseif (preg_match('/(?:^|[\s\-_\[\(])(\d+)(?:[\s\-_\]\)\.]|$)/', $filename, $matches)) {
            $epNum = (int) $matches[1];
            $episodeHtmlMap[$epNum] = $content;
        }
    }
}
                        
                        if (empty($globalHtml) && empty($episodeHtmlMap) && empty($fileContents)) {
                            \Filament\Notifications\Notification::make()
                                ->title('HTML Required')
                                ->danger()
                                ->body('Mohon paste HTML atau upload file HTML terlebih dahulu.')
                                ->send();
                            return;
                        }
                        
                        $totalCreated = 0;
                        $totalUpdated = 0;
                        $processedEpisodes = 0;
                        $skippedEpisodes = 0;
                        
                        // If we have files but couldn't map them, use them in order
                        $fileIndex = 0;
                        $recordsList = $records->sortBy('episode_number')->values();
                        
                        foreach ($recordsList as $episode) {
                            // Determine which HTML to use for this episode
                            $html = null;
                            
                            // Priority 1: Specific HTML file for this episode number
                            if (isset($episodeHtmlMap[$episode->episode_number])) {
                                $html = $episodeHtmlMap[$episode->episode_number];
                            }
                            // Priority 2: Use files in order (if same count as selected episodes)
                            elseif (!empty($fileContents) && count($fileContents) === $recordsList->count()) {
                                $html = $fileContents[$fileIndex] ?? null;
                                $fileIndex++;
                            }
                            // Priority 3: Global HTML (same for all)
                            elseif (!empty($globalHtml)) {
                                $html = $globalHtml;
                            }
                            // Priority 4: Use any available file content
                            elseif (!empty($fileContents)) {
                                $html = $fileContents[$fileIndex % count($fileContents)] ?? null;
                                $fileIndex++;
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
                            
                            // Optional delete existing
                            if (!empty($data['delete_existing'])) {
                                $keepUrls = collect($servers)->pluck('url')->unique()->values()->all();
                                if (!empty($keepUrls)) {
                                    \App\Models\VideoServer::where('episode_id', $episode->id)
                                        ->whereNotIn('embed_url', $keepUrls)
                                        ->delete();
                                }
                            }
                            
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
                            $message .= " | Skipped: {$skippedEpisodes}";
                        }
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Bulk Sync Completed!')
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
