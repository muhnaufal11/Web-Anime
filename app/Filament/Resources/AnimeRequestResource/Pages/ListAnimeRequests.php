<?php

namespace App\Filament\Resources\AnimeRequestResource\Pages;

use App\Filament\Resources\AnimeRequestResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAnimeRequests extends ListRecords
{
    protected static string $resource = AnimeRequestResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
