<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VideoServerResource\Pages;
use App\Filament\Resources\VideoServerResource\RelationManagers;
use App\Models\VideoServer;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class VideoServerResource extends Resource
{
    protected static ?string $model = VideoServer::class;

    protected static ?string $navigationIcon = 'heroicon-o-collection';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('server_name')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Textarea::make('embed_url')
                    ->label('Embed URL')
                    ->required()
                    ->rows(3),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required(),
                Forms\Components\Toggle::make('is_default')
                    ->label('Default Server')
                    ->helperText('Server ini akan dipilih pertama kali saat user membuka video')
                    ->default(false),
                Forms\Components\Select::make('episode_id')
                    ->relationship('episode', 'title')
                    ->searchable()
                    ->preload()
                    ->placeholder('Cari & pilih episode')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('server_name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('episode.title')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('episode.anime.title')
                    ->label('Anime')
                    ->searchable()
                    ->limit(25),
                Tables\Columns\BadgeColumn::make('source')
                    ->colors([
                        'success' => 'manual',
                        'primary' => 'sync',
                    ]),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\IconColumn::make('is_default')
                    ->boolean()
                    ->label('Default')
                    ->trueIcon('heroicon-o-star')
                    ->falseIcon('heroicon-o-minus')
                    ->trueColor('warning'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->options([
                        'manual' => 'Admin Upload',
                        'sync' => 'Sync',
                    ]),
                Tables\Filters\TernaryFilter::make('is_default')
                    ->label('Default Server'),
            ])
            ->actions([
                Tables\Actions\Action::make('set_default')
                    ->label('Set Default')
                    ->icon('heroicon-o-star')
                    ->color('warning')
                    ->visible(fn (VideoServer $record) => !$record->is_default)
                    ->action(function (VideoServer $record) {
                        // Remove default from other servers of same episode
                        VideoServer::where('episode_id', $record->episode_id)
                            ->where('id', '!=', $record->id)
                            ->update(['is_default' => false]);
                        
                        // Set this as default
                        $record->update(['is_default' => true]);
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Default server updated')
                            ->success()
                            ->body("{$record->server_name} is now the default server")
                            ->send();
                    }),
                Tables\Actions\Action::make('duplicate')
                    ->label('Copy')
                    ->icon('heroicon-o-duplicate')
                    ->color('success')
                    ->action(function (VideoServer $record) {
                        $newServer = $record->replicate();
                        $newServer->server_name = $record->server_name . ' (Copy)';
                        $newServer->is_default = false;
                        $newServer->save();
                        
                        return redirect(static::getUrl('edit', ['record' => $newServer->id]));
                    })
                    ->requiresConfirmation()
                    ->modalHeading('Duplikasi Video Server')
                    ->modalSubheading('Server akan dicopy dengan nama "(Copy)" di belakang')
                    ->modalButton('Ya, Copy Server'),
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
            'index' => Pages\ListVideoServers::route('/'),
            'create' => Pages\CreateVideoServer::route('/create'),
            'edit' => Pages\EditVideoServer::route('/{record}/edit'),
        ];
    }    
}
