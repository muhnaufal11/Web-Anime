<?php

namespace App\Filament\Resources\VideoServerResource\Pages;

use App\Filament\Resources\VideoServerResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListVideoServers extends ListRecords
{
    protected static string $resource = VideoServerResource::class;

    protected function getActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
