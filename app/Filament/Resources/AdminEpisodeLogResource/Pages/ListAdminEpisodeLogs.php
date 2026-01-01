<?php

namespace App\Filament\Resources\AdminEpisodeLogResource\Pages;

use App\Filament\Resources\AdminEpisodeLogResource;
use App\Filament\Resources\AdminEpisodeLogResource\Widgets\LogStats;
use Filament\Forms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdminEpisodeLogs extends ListRecords
{
    protected static string $resource = AdminEpisodeLogResource::class;

    protected function getHeaderActions(): array
    {
        $user = auth()->user();

        return array_filter([
            Actions\Action::make('updateBank')
                ->label('Update Rekening')
                ->color('primary')
                ->icon('heroicon-o-credit-card')
                ->visible(fn () => $user !== null)
                ->form([
                    Forms\Components\TextInput::make('bank_account_holder')
                        ->label('Atas Nama')
                        ->maxLength(100)
                        ->default(fn () => $user?->bank_account_holder),
                    Forms\Components\TextInput::make('bank_name')
                        ->label('Bank')
                        ->maxLength(100)
                        ->default(fn () => $user?->bank_name),
                    Forms\Components\TextInput::make('bank_account_number')
                        ->label('No. Rekening')
                        ->maxLength(80)
                        ->default(fn () => $user?->bank_account_number),
                ])
                ->action(function (array $data) use ($user) {
                    if (!$user) {
                        return;
                    }

                    $user->update([
                        'bank_name' => $data['bank_name'] ?? null,
                        'bank_account_number' => $data['bank_account_number'] ?? null,
                        'bank_account_holder' => $data['bank_account_holder'] ?? null,
                    ]);
                }),
            $user && $user->isSuperAdmin() ? Actions\CreateAction::make() : null,
        ]);
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LogStats::class,
        ];
    }
}
