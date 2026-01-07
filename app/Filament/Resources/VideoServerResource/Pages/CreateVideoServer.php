<?php

namespace App\Filament\Resources\VideoServerResource\Pages;

use App\Filament\Resources\VideoServerResource;
use App\Models\AdminEpisodeLog;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateVideoServer extends CreateRecord
{
    protected static string $resource = VideoServerResource::class;

    protected function afterCreate(): void
    {
        $user = auth()->user();

        // Catat semua admin (termasuk superadmin)
        if (!$user || !$user->isAdmin()) {
            return;
        }

        // Cek apakah admin ini sudah punya log untuk episode ini
        $existingLog = AdminEpisodeLog::where('user_id', $user->id)
            ->where('episode_id', $this->record->episode_id)
            ->first();

        // Jika belum ada log, buat log baru (atau update catatan)
        // Gunakan payment_rate user, refresh dulu untuk dapat data terbaru
        $user->refresh();
        $rate = $user->payment_rate ?? AdminEpisodeLog::DEFAULT_AMOUNT;
        
        AdminEpisodeLog::updateOrCreate(
            [
                'user_id' => $user->id,
                'episode_id' => $this->record->episode_id,
            ],
            [
                'amount' => $rate,
                'status' => AdminEpisodeLog::STATUS_PENDING,
                'note' => 'Menambahkan video server: ' . $this->record->server_name . ' - Rp ' . number_format($rate, 0, ',', '.'),
            ]
        );
    }

    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction(),
            $this->getCreateAndCopyFormAction(),
            $this->getCancelFormAction(),
        ];
    }

    protected function getCreateAndCopyFormAction(): Actions\Action
    {
        return Actions\Action::make('createAndCopy')
            ->label('Create & Copy')
            ->action('createAndCopy')
            ->color('success')
            ->keyBindings(['mod+shift+s']);
    }

    public function createAndCopy(): void
    {
        $this->create();
        
        $original = $this->getRecord();
        $newServer = $original->replicate();
        $newServer->server_name = $original->server_name . ' (Copy)';
        $newServer->save();

        $this->redirect(static::getResource()::getUrl('edit', ['record' => $newServer->id]));
    }
}
