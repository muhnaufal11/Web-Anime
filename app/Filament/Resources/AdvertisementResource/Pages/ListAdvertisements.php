<?php

namespace App\Filament\Resources\AdvertisementResource\Pages;

use App\Filament\Resources\AdvertisementResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdvertisements extends ListRecords
{
    protected static string $resource = AdvertisementResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
    
    protected function getHeaderWidgets(): array
    {
        return [
            // Could add stats widget here
        ];
    }
}
