<?php

namespace App\Filament\Widgets;

use App\Models\Anime;
use Filament\Tables;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class LatestAnime extends BaseWidget
{
    protected static ?int $sort = 2;
    
    protected int | string | array $columnSpan = 'full';
    
    protected static ?string $heading = '🎬 Anime Terbaru Ditambahkan';

    protected function getTableQuery(): Builder
    {
        return Anime::query()
            ->latest()
            ->limit(5);
    }

    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\ImageColumn::make('poster_image')
                ->label('Poster')
                ->width(40)
                ->height(60)
                ->getStateUsing(fn ($record) => $record->poster_image ? asset('storage/' . $record->poster_image) : null),
            Tables\Columns\TextColumn::make('title')
                ->label('Judul')
                ->limit(40)
                ->searchable(),
            Tables\Columns\BadgeColumn::make('type')
                ->colors([
                    'success' => 'TV',
                    'warning' => 'Movie',
                    'primary' => 'OVA',
                ]),
            Tables\Columns\BadgeColumn::make('status')
                ->colors([
                    'success' => 'Completed',
                    'warning' => 'Ongoing',
                ]),
            Tables\Columns\TextColumn::make('episodes_count')
                ->label('Episodes')
                ->counts('episodes'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Ditambahkan')
                ->since(),
        ];
    }
    
    protected function getTableActions(): array
    {
        return [
            Tables\Actions\Action::make('view')
                ->label('Lihat')
                ->icon('heroicon-o-eye')
                ->url(fn ($record) => route('filament.resources.animes.edit', $record)),
        ];
    }
}
