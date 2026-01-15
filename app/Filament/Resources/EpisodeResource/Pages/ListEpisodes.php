<?php

namespace App\Filament\Resources\EpisodeResource\Pages;

use App\Filament\Resources\EpisodeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListEpisodes extends ListRecords
{
    protected static string $resource = EpisodeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getTableQuery(): Builder
    {
        return parent::getTableQuery()
            ->with(['anime:id,title']) // Eager load untuk mencegah N+1 query
            ->select(['id', 'anime_id', 'episode_number', 'title', 'slug']); // Ambil kolom yang diperlukan saja
    }

    protected function getTableRecordsPerPageSelectOptions(): array
    {
        return [10, 25, 50]; // Kurangi opsi pagination
    }

    protected function getDefaultTableRecordsPerPageSelectOption(): int
    {
        return 10; // Default 10 records per page
    }
}
