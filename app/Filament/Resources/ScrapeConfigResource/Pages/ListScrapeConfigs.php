<?php

namespace App\Filament\Resources\ScrapeConfigResource\Pages;

use App\Filament\Resources\ScrapeConfigResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListScrapeConfigs extends ListRecords
{
    protected static string $resource = ScrapeConfigResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
