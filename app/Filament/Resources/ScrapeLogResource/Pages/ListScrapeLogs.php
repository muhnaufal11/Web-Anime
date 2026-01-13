<?php

namespace App\Filament\Resources\ScrapeLogResource\Pages;

use App\Filament\Resources\ScrapeLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScrapeLogs extends ListRecords
{
    protected static string $resource = ScrapeLogResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
