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
                    Forms\Components\Select::make('payout_method')
                        ->label('Metode Pembayaran')
                        ->options([
                            'bank' => 'Bank Transfer',
                            'ewallet' => 'E-Wallet',
                            'paypal' => 'PayPal',
                            'cash' => 'Cash',
                        ])
                        ->default(fn () => $user?->payout_method)
                        ->required(),
                    Forms\Components\TextInput::make('payout_wallet_provider')
                        ->label('Bank/Provider (e.g. BCA, DANA)')
                        ->maxLength(100)
                        ->default(fn () => $user?->payout_wallet_provider)
                        ->helperText('Isi nama bank atau penyedia e-wallet'),
                    Forms\Components\TextInput::make('payout_wallet_number')
                        ->label('Nomor Akun/Wallet')
                        ->maxLength(120)
                        ->default(fn () => $user?->payout_wallet_number),
                    Forms\Components\Textarea::make('payout_notes')
                        ->label('Catatan pembayaran')
                        ->rows(2)
                        ->maxLength(255)
                        ->default(fn () => $user?->payout_notes),
                ])
                ->action(function (array $data) use ($user) {
                    if (!$user) {
                        return;
                    }

                    $user->update([
                        'bank_name' => $data['bank_name'] ?? null,
                        'bank_account_number' => $data['bank_account_number'] ?? null,
                        'bank_account_holder' => $data['bank_account_holder'] ?? null,
                        'payout_method' => $data['payout_method'] ?? null,
                        'payout_wallet_provider' => $data['payout_wallet_provider'] ?? null,
                        'payout_wallet_number' => $data['payout_wallet_number'] ?? null,
                        'payout_notes' => $data['payout_notes'] ?? null,
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
