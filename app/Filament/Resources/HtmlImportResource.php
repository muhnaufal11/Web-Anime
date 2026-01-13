<?php

namespace App\Filament\Resources;

use App\Filament\Resources\HtmlImportResource\Pages;
use App\Models\Anime;
use App\Models\Genre;
use App\Models\Episode;
use App\Models\VideoServer;
use App\Services\AnimeScraper;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class HtmlImportResource extends Resource
{
    protected static ?string $model = Anime::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-download';
    
    protected static ?string $navigationLabel = 'Import dari HTML';
    
    protected static ?string $navigationGroup = 'Tools';
    
    protected static ?string $slug = 'html-import';
    
    protected static ?int $navigationSort = 100;

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Judul')
                    ->searchable()
                    ->limit(50),
                Tables\Columns\TextColumn::make('episodes_count')
                    ->label('Episodes')
                    ->counts('episodes'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Imported')
                    ->dateTime('d M Y H:i'),
            ])
            ->filters([])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }
    
    public static function getRelations(): array
    {
        return [];
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHtmlImports::route('/'),
            'import' => Pages\ImportFromHtml::route('/import'),
        ];
    }
}
