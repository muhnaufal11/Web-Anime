<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateAnime extends CreateRecord
{
    protected static string $resource = AnimeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Simpan genres untuk di-sync nanti
        if (isset($data['genres'])) {
            $this->genres = $data['genres'];
            unset($data['genres']);
        }
        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync genres setelah anime dibuat
        if (isset($this->genres)) {
            $this->record->genres()->sync($this->genres);
        }
    }
}
