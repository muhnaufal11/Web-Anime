<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimeRequestResource\Pages;
use App\Models\AnimeRequest;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AnimeRequestResource extends Resource
{
    protected static ?string $model = AnimeRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-inbox';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?string $navigationLabel = 'Anime Requests';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Request Info')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->label('Judul Anime')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('mal_url')
                            ->label('URL MyAnimeList')
                            ->url()
                            ->maxLength(500),
                        Forms\Components\TextInput::make('mal_id')
                            ->label('MAL ID')
                            ->numeric(),
                        Forms\Components\Textarea::make('reason')
                            ->label('Alasan Request')
                            ->rows(3),
                        Forms\Components\Select::make('type')
                            ->label('Tipe')
                            ->options([
                                'new_anime' => 'Anime Baru',
                                'add_episodes' => 'Tambah Episode',
                            ])
                            ->required(),
                        Forms\Components\Select::make('anime_id')
                            ->label('Anime Terkait')
                            ->relationship('anime', 'title')
                            ->searchable()
                            ->preload()
                            ->placeholder('Pilih anime (jika tambah episode)'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Status & Response')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'completed' => 'Completed',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Admin')
                            ->rows(3)
                            ->placeholder('Catatan untuk user...'),
                        Forms\Components\TextInput::make('upvotes')
                            ->label('Upvotes')
                            ->numeric()
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('upvotes')
                    ->label('Votes')
                    ->sortable()
                    ->color('danger'),
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->sortable()
                    ->limit(40),
                Tables\Columns\BadgeColumn::make('type')
                    ->label('Tipe')
                    ->colors([
                        'primary' => 'new_anime',
                        'success' => 'add_episodes',
                    ])
                    ->formatStateUsing(fn ($state) => $state === 'new_anime' ? 'Anime Baru' : 'Tambah Episode'),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'primary' => 'approved',
                        'danger' => 'rejected',
                        'success' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Dari')
                    ->sortable(),
                Tables\Columns\TextColumn::make('mal_url')
                    ->label('MAL')
                    ->url(fn ($record) => $record->mal_url)
                    ->openUrlInNewTab()
                    ->formatStateUsing(fn ($state) => $state ? 'Link' : '-'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'rejected' => 'Rejected',
                        'completed' => 'Completed',
                    ]),
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'new_anime' => 'Anime Baru',
                        'add_episodes' => 'Tambah Episode',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Approve')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan untuk user')
                            ->placeholder('Akan segera diproses...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'approved',
                            'admin_notes' => $data['admin_notes'] ?? null,
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Reject')
                    ->icon('heroicon-o-x')
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === 'pending')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Alasan reject')
                            ->required()
                            ->placeholder('Anime sudah ada / tidak tersedia...'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'rejected',
                            'admin_notes' => $data['admin_notes'],
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn ($record) => $record->status === 'approved')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan')
                            ->placeholder('Anime sudah ditambahkan!'),
                    ])
                    ->action(function ($record, array $data) {
                        $record->update([
                            'status' => 'completed',
                            'admin_notes' => $data['admin_notes'] ?? 'Selesai!',
                            'processed_at' => now(),
                            'processed_by' => auth()->id(),
                        ]);
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAnimeRequests::route('/'),
            'create' => Pages\CreateAnimeRequest::route('/create'),
            'edit' => Pages\EditAnimeRequest::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::where('status', 'pending')->count() ?: null;
    }
    
    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
    }
}
