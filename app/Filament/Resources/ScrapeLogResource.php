<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapeLogResource\Pages;
use App\Filament\Resources\ScrapeLogResource\RelationManagers;
use App\Models\ScrapeLog;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScrapeLogResource extends Resource
{
    protected static ?string $model = ScrapeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-document-text';
    
    protected static ?string $navigationGroup = 'Scraping';
    
    protected static ?string $navigationLabel = 'Scrape Logs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\Select::make('scrape_config_id')
                        ->relationship('config', 'name')
                        ->label('Configuration'),
                    
                    Forms\Components\Select::make('source')
                        ->options([
                            'myanimelist' => 'MyAnimeList',
                            'animesail' => 'AnimeSail',
                        ])
                        ->required(),
                    
                    Forms\Components\Select::make('type')
                        ->options([
                            'metadata' => 'Metadata',
                            'episodes' => 'Episodes',
                            'full' => 'Full Sync',
                        ])
                        ->required(),
                    
                    Forms\Components\Select::make('status')
                        ->options([
                            'running' => 'Running',
                            'success' => 'Success',
                            'failed' => 'Failed',
                            'partial' => 'Partial',
                        ])
                        ->default('running'),
                    
                    Forms\Components\TextInput::make('items_processed')
                        ->numeric()
                        ->default(0),
                    
                    Forms\Components\TextInput::make('items_created')
                        ->numeric()
                        ->default(0),
                    
                    Forms\Components\TextInput::make('items_updated')
                        ->numeric()
                        ->default(0),
                    
                    Forms\Components\TextInput::make('items_failed')
                        ->numeric()
                        ->default(0),
                    
                    Forms\Components\Textarea::make('message')
                        ->maxLength(65535),
                    
                    Forms\Components\KeyValue::make('errors')
                        ->label('Errors'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('config.name')
                    ->label('Config')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'primary' => 'myanimelist',
                        'success' => 'animesail',
                    ]),
                
                Tables\Columns\BadgeColumn::make('type')
                    ->colors([
                        'info' => 'metadata',
                        'warning' => 'episodes',
                        'success' => 'full',
                    ]),
                
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'running',
                        'success' => 'success',
                        'danger' => 'failed',
                        'secondary' => 'partial',
                    ]),
                
                Tables\Columns\TextColumn::make('items_processed')
                    ->label('Processed')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('items_created')
                    ->label('Created')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('items_updated')
                    ->label('Updated')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('items_failed')
                    ->label('Failed')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('started_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Started'),
                
                Tables\Columns\TextColumn::make('completed_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Completed'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'myanimelist' => 'MyAnimeList',
                        'animesail' => 'AnimeSail',
                    ]),
                
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'running' => 'Running',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'partial' => 'Partial',
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ])
            ->defaultSort('created_at', 'desc');
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
            'index' => Pages\ListScrapeLogs::route('/'),
        ];
    }    
}
