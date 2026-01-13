<?php

namespace App\Filament\Resources\AnimeResource\Pages;

use App\Filament\Resources\AnimeResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAnime extends EditRecord
{
    protected static string $resource = AnimeResource::class;

    protected function getActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Simpan genres untuk di-sync nanti
        if (isset($data['genres'])) {
            $this->genres = $data['genres'];
            unset($data['genres']);
        }
        return $data;
    }

    protected function afterSave(): void
    {
        // Sync genres setelah anime disimpan
        if (isset($this->genres)) {
            $this->record->genres()->sync($this->genres);
        }
    }
}
