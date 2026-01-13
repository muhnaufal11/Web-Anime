<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimeResource\Pages;
use App\Filament\Resources\AnimeResource\RelationManagers;
use App\Models\Anime;
use App\Models\Genre;
use App\Models\Episode;
use App\Models\VideoServer;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class AnimeResource extends Resource
{
    protected static ?string $model = Anime::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->reactive()
                            ->afterStateUpdated(fn (callable $set, $state) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('slug')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Auto-generated dari title'),
                        Forms\Components\Textarea::make('synopsis')
                            ->required()
                            ->rows(5),
                        Forms\Components\FileUpload::make('poster_image')
                            ->image()
                            ->directory('posters')
                            ->visibility('public')
                            ->getUploadedFileNameForStorageUsing(function (UploadedFile $file, callable $get): string {
                                $slug = $get('slug') ?: Str::slug($get('title'));
                                return $slug . '-' . time() . '.' . $file->getClientOriginalExtension();
                            })
                            ->helperText('Upload poster anime (JPG/PNG)'),
                    ])
                    ->columnSpan(2),
                
                Forms\Components\Card::make()
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(['TV' => 'TV', 'Movie' => 'Movie', 'ONA' => 'ONA'])
                            ->default('TV')
                            ->required(),
                        Forms\Components\Select::make('status')
                            ->options(['Ongoing' => 'Ongoing', 'Completed' => 'Completed'])
                            ->default('Ongoing')
                            ->required(),
                        Forms\Components\TextInput::make('release_year')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(date('Y') + 1)
                            ->helperText('Tahun rilis anime'),
                        Forms\Components\TextInput::make('rating')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(10)
                            ->step(0.01)
                            ->helperText('Rating 0-10 (gunakan titik untuk desimal, misal: 7.63)')
                            ->placeholder('7.6'),
                        Forms\Components\Select::make('genres')
                            ->multiple()
                            ->relationship('genres', 'name')
                            ->preload()
                            ->required()
                            ->helperText('Pilih satu atau lebih genre'),
                        Forms\Components\Toggle::make('featured')
                            ->label('Tampilkan di Featured')
                            ->helperText('Tampilkan anime ini di spotlight homepage'),
                    ])
                    ->columnSpan(1),
            ])
            ->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('poster_image')
                    ->label('Poster')
                    ->width(50)
                    ->height(75)
                    ->url(fn ($record) => $record->poster_image ? asset('storage/' . $record->poster_image) : null)
                    ->getStateUsing(fn ($record) => $record->poster_image ? asset('storage/' . $record->poster_image) : null),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('genres_list')
                    ->label('Genres')
                    ->getStateUsing(fn ($record) => $record->genres->pluck('name')->take(3)->join(', '))
                    ->wrap(),
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'success' => 'TV',
                        'warning' => 'Movie',
                        'info' => 'ONA',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'success' => 'Completed',
                        'warning' => 'Ongoing',
                    ]),
                Tables\Columns\TextColumn::make('release_year')
                    ->label('Tahun')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ?? '-'),
                Tables\Columns\TextColumn::make('rating')
                    ->sortable()
                    ->formatStateUsing(fn ($state) => $state ? number_format($state, 1) . '/10' : '-'),
                Tables\Columns\IconColumn::make('featured')
                    ->boolean()
                    ->label('Featured'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'TV' => 'TV',
                        'Movie' => 'Movie',
                        'ONA' => 'ONA',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'Ongoing' => 'Ongoing',
                        'Completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('release_year')
                    ->label('Tahun Rilis')
                    ->options(function () {
                        return \App\Models\Anime::whereNotNull('release_year')
                            ->distinct()
                            ->orderBy('release_year', 'desc')
                            ->pluck('release_year', 'release_year')
                            ->toArray();
                    })
                    ->placeholder('Semua Tahun'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\Action::make('sync_videos')
                    ->label('Sync Videos')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->form([
                        Forms\Components\Textarea::make('html_source')
                            ->label('HTML Source (optional)')
                            ->rows(8)
                            ->placeholder('Paste HTML dari halaman anime (opsional)')
                            ->helperText('Jika situs memakai JavaScript, paste HTML yang sudah di-load untuk mempercepat sync'),
                        Forms\Components\FileUpload::make('html_file')
                            ->label('Upload HTML (optional)')
                            ->acceptedFileTypes(['text/html','text/plain'])
                            ->directory('uploads/html')
                            ->helperText('Alternatif: upload file HTML yang berisi daftar episode'),
                        Forms\Components\Toggle::make('delete_missing')
                            ->label('Hapus episode yang tidak ada di HTML')
                            ->helperText('Setelah sync, episode yang tidak ada di HTML akan dihapus'),
                    ])
                    ->action(function (Anime $record, array $data) {
                        $service = app(\App\Services\AnimeSailService::class);

                        $html = null;
                        if (!empty($data['html_source'])) {
                            $html = $data['html_source'];
                        } elseif (!empty($data['html_file'])) {
                            // Read uploaded file from public storage
                            $path = storage_path('app/public/' . $data['html_file']);
                            if (is_file($path)) {
                                $html = file_get_contents($path);
                            }
                        }

                        // HTML is now required
                        if (empty($html)) {
                            \Filament\Notifications\Notification::make()
                                ->title('HTML Required')
                                ->danger()
                                ->body('Mohon paste atau upload HTML halaman anime terlebih dahulu.')
                                ->send();
                            return;
                        }

                        // For getting anime URL from HTML, we need to detect it from the HTML context
                        // We'll extract from the page or use a fallback approach
                        $url = null;
                        // Try to detect URL from HTML meta og:url or from page context
                        if (preg_match('/og:url["\']?\s+content=["\']([^"\']+)["\']/', $html, $m)) {
                            $url = $m[1];
                        }
                        
                        // Parse details first (for optional cleanup)
                        $details = $service->getAnimeDetailsFromHtml($url, $html);
                        $result = $service->syncEpisodesFromHtml($record, $html, $url);

                        // Optional cleanup: delete episodes not present in HTML
                        if (!empty($data['delete_missing']) && !empty($details['episodes'])) {
                            $keptNumbers = collect($details['episodes'])->pluck('number')->unique()->values()->all();
                            $toDelete = Episode::where('anime_id', $record->id)
                                ->whereNotIn('episode_number', $keptNumbers)
                                ->pluck('id');
                            if ($toDelete->count() > 0) {
                                VideoServer::whereIn('episode_id', $toDelete)->delete();
                                Episode::whereIn('id', $toDelete)->delete();
                            }
                        }

                        $errorText = '';
                        if (!empty($result['errors'])) {
                            $errorText = "\nErrors: " . implode('; ', array_slice($result['errors'], 0, 3));
                        }

                        // --- AUTO CREATE ADMIN LOG PER EPISODE YANG BERHASIL DI-SYNC ---
                        $user = auth()->user()?->refresh();
                        if ($user && $user->isAdmin()) {
                            // Gunakan payment_rate user
                            $rate = $user->payment_rate ?? \App\Models\AdminEpisodeLog::DEFAULT_AMOUNT;
                            
                            // Dapatkan episode yang baru dibuat atau diupdate
                            $episodeCount = $result['created'] + $result['updated'];
                            if ($episodeCount > 0) {
                                // Ambil episode terbaru dari anime ini
                                $recentEpisodes = Episode::where('anime_id', $record->id)
                                    ->orderBy('created_at', 'desc')
                                    ->limit($episodeCount)
                                    ->get();
                                
                                foreach ($recentEpisodes as $episode) {
                                    \App\Models\AdminEpisodeLog::updateOrCreate(
                                        [
                                            'user_id' => $user->id,
                                            'episode_id' => $episode->id,
                                        ],
                                        [
                                            'amount' => $rate,
                                            'status' => \App\Models\AdminEpisodeLog::STATUS_PENDING,
                                            'note' => 'Sync videos dari anime: ' . $record->title . ' - Rp ' . number_format($rate, 0, ',', '.'),
                                        ]
                                    );
                                }
                            }
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Sync videos completed')
                            ->success()
                            ->body("Created: {$result['created']} | Updated: {$result['updated']} | Errors: " . count($result['errors']) . $errorText)
                            ->send();
                    })
                        ->requiresConfirmation(),
                    ])
                    ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->label('Hapus Terpilih'),
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
            'index' => Pages\ListAnimes::route('/'),
            'create' => Pages\CreateAnime::route('/create'),
            'edit' => Pages\EditAnime::route('/{record}/edit'),
            'list-empty-animes' => Pages\ListEmptyAnimes::route('/empty'),
        ];
    }    
}
