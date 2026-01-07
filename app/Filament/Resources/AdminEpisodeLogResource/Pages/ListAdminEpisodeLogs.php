<?php

namespace App\Filament\Resources\AdminEpisodeLogResource\Pages;

use App\Filament\Resources\AdminEpisodeLogResource;
use App\Filament\Resources\AdminEpisodeLogResource\Widgets\LogStats;
use App\Models\User;
use App\Services\PaymentService;
use Filament\Forms;
use Filament\Pages\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ListAdminEpisodeLogs extends ListRecords
{
    protected static string $resource = AdminEpisodeLogResource::class;

    protected function getActions(): array
    {
        $user = auth()->user();

        // Get admins with payable amount for checkbox options
        $adminOptions = [];
        $adminPayables = [];
        if ($user?->isSuperAdmin()) {
            $admins = User::admins()
                ->where('role', '!=', User::ROLE_SUPERADMIN)
                ->get();
            
            foreach ($admins as $admin) {
                $calc = $admin->calculateMonthlyPayment(now()->year, now()->month);
                if ($calc['payable'] > 0) {
                    $bank = $admin->bank_name ?? $admin->payout_wallet_provider ?? '-';
                    $accountNumber = $admin->bank_account_number ?? $admin->payout_wallet_number ?? '-';
                    $holder = $admin->bank_account_holder ?? '-';
                    
                    $adminOptions[$admin->id] = $admin->name . ' - Rp ' . number_format($calc['payable'], 0, ',', '.') . 
                        ' (' . $bank . ' - ' . $accountNumber . ' a.n ' . $holder . ')';
                    $adminPayables[$admin->id] = $calc['payable'];
                }
            }
        }

        return array_filter([
            // Action untuk proses pembayaran dengan pilih admin & upload bukti
            Actions\Action::make('processPayments')
                ->label('Proses Pembayaran')
                ->color('success')
                ->icon('heroicon-o-cash')
                ->visible(fn () => $user?->isSuperAdmin())
                ->modalHeading('Proses Pembayaran')
                ->modalWidth('xl')
                ->form([
                    Forms\Components\CheckboxList::make('admin_ids')
                        ->label('Pilih Admin yang Dibayar')
                        ->options($adminOptions)
                        ->columns(1)
                        ->required()
                        ->bulkToggleable()
                        ->helperText('Centang admin yang ingin diproses pembayarannya'),
                    
                    Forms\Components\FileUpload::make('payment_proof')
                        ->label('Upload Bukti Pembayaran')
                        ->image()
                        ->directory('payment-proofs')
                        ->visibility('public')
                        ->maxSize(5120) // 5MB
                        ->required()
                        ->helperText('Upload screenshot bukti transfer (max 5MB)'),
                    
                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Pembayaran')
                        ->rows(2)
                        ->placeholder('Catatan tambahan (opsional)'),
                ])
                ->action(function (array $data) use ($adminPayables) {
                    $selectedAdmins = $data['admin_ids'] ?? [];
                    $paymentProof = $data['payment_proof'] ?? null;
                    $notes = $data['notes'] ?? '';
                    
                    if (empty($selectedAdmins)) {
                        Notification::make()
                            ->title('Error')
                            ->danger()
                            ->body('Pilih minimal 1 admin untuk diproses')
                            ->send();
                        return;
                    }
                    
                    $service = app(PaymentService::class);
                    $result = $service->processPaymentsForAdmins($selectedAdmins, $paymentProof);
                    
                    // Send to Discord
                    $this->sendPaymentToDiscord($selectedAdmins, $paymentProof, $result, $notes);
                    
                    Notification::make()
                        ->title('Pembayaran Diproses')
                        ->success()
                        ->body("Diproses: {$result['processed']} admin. Total dibayar: Rp " . 
                               number_format($result['total_paid'], 0, ',', '.'))
                        ->send();
                }),
            
            // Action untuk lihat detail per admin (superadmin only)
            Actions\Action::make('adminSummary')
                ->label('Ringkasan Admin')
                ->color('primary')
                ->icon('heroicon-o-users')
                ->visible(fn () => $user?->isSuperAdmin())
                ->modalHeading('Ringkasan Pembayaran Admin')
                ->modalContent(function () {
                    $admins = User::admins()
                        ->where('role', '!=', User::ROLE_SUPERADMIN)
                        ->get();
                    
                    return view('filament.components.admin-payment-summary', [
                        'admins' => $admins,
                    ]);
                })
                ->modalButton('Tutup')
                ->action(fn () => null),
            
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

    /**
     * Send payment notification to Discord webhook
     */
    protected function sendPaymentToDiscord(array $adminIds, ?string $paymentProof, array $result, string $notes = ''): void
    {
        $webhookUrl = 'https://discordapp.com/api/webhooks/1458015594799431700/ywvYZN0fSqWpoDUfgtdNsmcOGCRHdLsXD1EI8c3EcdpHUwoAaoM47ZkaGfubHNviZbb0';
        
        // Build admin list
        $adminList = '';
        $admins = User::whereIn('id', $adminIds)->get();
        foreach ($admins as $admin) {
            $calc = $admin->calculateMonthlyPayment(now()->year, now()->month);
            $bank = $admin->bank_name ?? $admin->payout_wallet_provider ?? '-';
            $account = $admin->bank_account_number ?? $admin->payout_wallet_number ?? '-';
            $holder = $admin->bank_account_holder ?? '-';
            $adminList .= "• **{$admin->name}** - Rp " . number_format($calc['payable'], 0, ',', '.') . "\n";
            $adminList .= "  └ {$bank} | {$account} | {$holder}\n";
        }

        $embed = [
            'title' => '💰 Pembayaran Admin Nipnime',
            'color' => 0x00FF00, // Green
            'fields' => [
                [
                    'name' => '📅 Tanggal',
                    'value' => now()->format('d F Y H:i'),
                    'inline' => true,
                ],
                [
                    'name' => '👥 Jumlah Admin',
                    'value' => $result['processed'] . ' admin',
                    'inline' => true,
                ],
                [
                    'name' => '💵 Total Dibayar',
                    'value' => 'Rp ' . number_format($result['total_paid'], 0, ',', '.'),
                    'inline' => true,
                ],
                [
                    'name' => '📋 Detail Pembayaran',
                    'value' => $adminList ?: '-',
                    'inline' => false,
                ],
            ],
            'footer' => [
                'text' => 'Nipnime Payment System',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        if ($notes) {
            $embed['fields'][] = [
                'name' => '📝 Catatan',
                'value' => $notes,
                'inline' => false,
            ];
        }

        // If payment proof exists, add image
        if ($paymentProof) {
            $imageUrl = url('storage/' . $paymentProof);
            $embed['image'] = ['url' => $imageUrl];
        }

        try {
            Http::post($webhookUrl, [
                'embeds' => [$embed],
            ]);
        } catch (\Exception $e) {
            \Log::error('Discord webhook failed: ' . $e->getMessage());
        }
    }

    protected function getHeaderWidgets(): array
    {
        return [
            LogStats::class,
        ];
    }
}
