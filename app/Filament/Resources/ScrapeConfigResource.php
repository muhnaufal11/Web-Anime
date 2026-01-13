<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ScrapeConfigResource\Pages;
use App\Filament\Resources\ScrapeConfigResource\RelationManagers;
use App\Models\ScrapeConfig;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ScrapeConfigResource extends Resource
{
    protected static ?string $model = ScrapeConfig::class;

    protected static ?string $navigationIcon = 'heroicon-o-download';
    
    protected static ?string $navigationGroup = 'Scraping';
    
    protected static ?string $navigationLabel = 'Scrape Configs';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Card::make()->schema([
                    Forms\Components\TextInput::make('name')
                        ->required()
                        ->maxLength(255)
                        ->label('Configuration Name'),
                    
                    Forms\Components\Select::make('source')
                        ->options([
                            'myanimelist' => 'MyAnimeList',
                            'animesail' => 'AnimeSail',
                            'both' => 'Both Sources',
                        ])
                        ->required()
                        ->default('both')
                        ->label('Data Source'),
                    
                    Forms\Components\Select::make('sync_type')
                        ->options([
                            'metadata' => 'Metadata Only',
                            'episodes' => 'Episodes Only',
                            'both' => 'Both',
                        ])
                        ->required()
                        ->default('both')
                        ->label('Sync Type'),
                    
                    Forms\Components\Toggle::make('is_active')
                        ->default(true)
                        ->label('Active'),
                    
                    Forms\Components\Toggle::make('auto_sync')
                        ->default(false)
                        ->label('Auto Sync')
                        ->reactive()
                        ->helperText('Enable automatic synchronization'),
                    
                    Forms\Components\TextInput::make('schedule')
                        ->maxLength(255)
                        ->label('Schedule (Cron)')
                        ->helperText('Example: 0 0 * * * (daily at midnight)')
                        ->hidden(fn ($get) => !$get('auto_sync')),
                    
                    Forms\Components\TextInput::make('max_items')
                        ->numeric()
                        ->default(50)
                        ->minValue(1)
                        ->maxValue(100)
                        ->label('Max Items Per Sync'),
                    
                    Forms\Components\KeyValue::make('filters')
                        ->label('Filters')
                        ->helperText('Add custom filters (e.g., genre: action)'),
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'primary' => 'myanimelist',
                        'success' => 'animesail',
                        'warning' => 'both',
                    ]),
                
                Tables\Columns\BadgeColumn::make('sync_type')
                    ->colors([
                        'info' => 'metadata',
                        'warning' => 'episodes',
                        'success' => 'both',
                    ]),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\IconColumn::make('auto_sync')
                    ->boolean(),
                
                Tables\Columns\TextColumn::make('max_items')
                    ->label('Max Items'),
                
                Tables\Columns\TextColumn::make('last_run_at')
                    ->dateTime()
                    ->sortable()
                    ->label('Last Run'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'myanimelist' => 'MyAnimeList',
                        'animesail' => 'AnimeSail',
                        'both' => 'Both',
                    ]),
                
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All configs')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\Action::make('run_sync')
                    ->label('Run Sync')
                    ->icon('heroicon-o-refresh')
                    ->color('success')
                    ->action(function (ScrapeConfig $record) {
                        \Artisan::call('anime:sync', [
                            '--config' => $record->id,
                            '--source' => $record->source,
                            '--type' => $record->sync_type,
                            '--limit' => $record->max_items,
                        ]);
                        
                        $record->update(['last_run_at' => now()]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Sync Started')
                            ->success()
                            ->body('Anime sync has been queued.')
                            ->send();
                    })
                    ->requiresConfirmation(),
                
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
            'index' => Pages\ListScrapeConfigs::route('/'),
            'create' => Pages\CreateScrapeConfig::route('/create'),
            'edit' => Pages\EditScrapeConfig::route('/{record}/edit'),
        ];
    }    
}
