<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AnimeResource\Pages;
use App\Filament\Resources\AnimeResource\RelationManagers;
use App\Models\Anime;
use App\Models\Genre;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Str;

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
                            ->directory('animes')
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
                            ->step(0.1)
                            ->helperText('Rating 0-10'),
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
                    ->size(60),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('genres.name')
                    ->label('Genres')
                    ->formatStateUsing(fn ($state) => $state)
                    ->listWithLineBreaks()
                    ->limitList(3)
                    ->color('primary'),
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
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
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
        ];
    }    
}
