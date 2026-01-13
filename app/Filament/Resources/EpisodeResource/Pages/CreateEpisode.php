<?php

namespace App\Filament\Resources\EpisodeResource\Pages;

use App\Filament\Resources\EpisodeResource;
use App\Models\AdminEpisodeLog;
use Filament\Resources\Pages\CreateRecord;

class CreateEpisode extends CreateRecord
{
    protected static string $resource = EpisodeResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        if (auth()->check()) {
            $data['created_by'] = auth()->id();
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        $user = auth()->user();

        // Catat semua admin (termasuk superadmin)
        if (!$user || !$user->isAdmin()) {
            return;
        }

        // Catat log episode yang dibuat admin
        // Gunakan payment_rate user, refresh dulu untuk dapat data terbaru
        $user->refresh();
        $rate = $user->payment_rate ?? AdminEpisodeLog::DEFAULT_AMOUNT;
        
        AdminEpisodeLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $this->record->id,
            ],
            [
                'amount' => $rate,
                'status' => AdminEpisodeLog::STATUS_PENDING,
                'note' => 'Episode baru dibuat - Rp ' . number_format($rate, 0, ',', '.'),
            ]
        );
    }
}
