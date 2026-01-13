<?php

namespace App\Filament\Resources\AdminEpisodeLogResource\Pages;

use App\Filament\Resources\AdminEpisodeLogResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdminEpisodeLog extends EditRecord
{
    protected static string $resource = AdminEpisodeLogResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
