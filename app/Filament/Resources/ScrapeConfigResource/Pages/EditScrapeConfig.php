<?php

namespace App\Filament\Resources\ScrapeConfigResource\Pages;

use App\Filament\Resources\ScrapeConfigResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditScrapeConfig extends EditRecord
{
    protected static string $resource = ScrapeConfigResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
