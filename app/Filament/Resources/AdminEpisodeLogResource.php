<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AdminEpisodeLogResource\Pages;
use App\Models\AdminEpisodeLog;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;

class AdminEpisodeLogResource extends Resource
{
    protected static ?string $model = AdminEpisodeLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';
    protected static ?string $navigationLabel = 'Admin Performance';
    protected static ?string $navigationGroup = 'Superadmin';
    protected static ?int $navigationSort = 1;

    public static function shouldRegisterNavigation(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->isAdmin() ?? false;
    }

    public static function canCreate(): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canEdit($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function canDelete($record): bool
    {
        return auth()->user()?->isSuperAdmin() ?? false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->label('Admin')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\Select::make('episode_id')
                    ->label('Episode')
                    ->relationship('episode', 'title')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn ($record) => 'Ep ' . $record->episode_number . ' - ' . ($record->title ?? ''))
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->numeric()
                    ->default(AdminEpisodeLog::DEFAULT_AMOUNT)
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        AdminEpisodeLog::STATUS_PENDING => 'Pending',
                        AdminEpisodeLog::STATUS_APPROVED => 'Approved',
                        AdminEpisodeLog::STATUS_PAID => 'Paid',
                    ])
                    ->default(AdminEpisodeLog::STATUS_PENDING)
                    ->required(),
                Forms\Components\Textarea::make('note')
                    ->label('Catatan')
                    ->rows(2)
                    ->nullable(),
            ])->columns(2);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('updateBank')
                    ->label('Update Rekening / Metode Bayar')
                    ->icon('heroicon-o-credit-card')
                    ->color('primary')
                    ->modalHeading('Lengkapi Metode Pembayaran')
                    ->form([
                        Forms\Components\TextInput::make('bank_account_holder')
                            ->label('Atas Nama')
                            ->maxLength(100)
                            ->default(fn () => auth()->user()?->bank_account_holder),
                        Forms\Components\TextInput::make('bank_name')
                            ->label('Bank')
                            ->maxLength(100)
                            ->default(fn () => auth()->user()?->bank_name),
                        Forms\Components\TextInput::make('bank_account_number')
                            ->label('No. Rekening')
                            ->maxLength(80)
                            ->default(fn () => auth()->user()?->bank_account_number),
                        Forms\Components\Select::make('payout_method')
                            ->label('Metode Pembayaran')
                            ->options([
                                'bank' => 'Bank Transfer',
                                'ewallet' => 'E-Wallet',
                                'paypal' => 'PayPal',
                                'cash' => 'Cash',
                            ])
                            ->default(fn () => auth()->user()?->payout_method)
                            ->required(),
                        Forms\Components\TextInput::make('payout_wallet_provider')
                            ->label('Bank/Provider (e.g. BCA, DANA)')
                            ->maxLength(100)
                            ->default(fn () => auth()->user()?->payout_wallet_provider)
                            ->helperText('Isi nama bank atau penyedia e-wallet'),
                        Forms\Components\TextInput::make('payout_wallet_number')
                            ->label('Nomor Akun/Wallet')
                            ->maxLength(120)
                            ->default(fn () => auth()->user()?->payout_wallet_number),
                        Forms\Components\Textarea::make('payout_notes')
                            ->label('Catatan pembayaran')
                            ->rows(2)
                            ->maxLength(255)
                            ->default(fn () => auth()->user()?->payout_notes),
                    ])
                    ->action(function (array $data) {
                        $user = auth()->user();
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
            ])
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Admin')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('episode.title')
                    ->label('Episode')
                    ->formatStateUsing(fn ($record) => 'Ep ' . $record->episode->episode_number . ' - ' . $record->episode->title)
                    ->sortable(),
                Tables\Columns\TextColumn::make('episode.anime.title')
                    ->label('Anime')
                    ->limit(25)
                    ->tooltip(fn ($record) => $record->episode->anime->title ?? null)
                    ->sortable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Bayaran')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('Status')
                    ->colors([
                        'warning' => AdminEpisodeLog::STATUS_PENDING,
                        'info' => AdminEpisodeLog::STATUS_APPROVED,
                        'success' => AdminEpisodeLog::STATUS_PAID,
                    ])
                    ->icons([
                        'heroicon-o-clock' => AdminEpisodeLog::STATUS_PENDING,
                        'heroicon-o-check-circle' => AdminEpisodeLog::STATUS_APPROVED,
                        'heroicon-o-currency-dollar' => AdminEpisodeLog::STATUS_PAID,
                    ])
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('note')
                    ->label('Catatan')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('user.bank_account_holder')
                    ->label('Atas Nama')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.bank_name')
                    ->label('Bank')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.bank_account_number')
                    ->label('No. Rekening')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.payout_method')
                    ->label('Metode Bayar')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.payout_wallet_provider')
                    ->label('Provider/Bank')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),
                Tables\Columns\TextColumn::make('user.payout_wallet_number')
                    ->label('Akun/No')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->toggleable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        AdminEpisodeLog::STATUS_PENDING => 'Pending',
                        AdminEpisodeLog::STATUS_APPROVED => 'Approved',
                        AdminEpisodeLog::STATUS_PAID => 'Paid',
                    ]),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Admin')
                    ->relationship('user', 'name'),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Set Approved')
                    ->icon('heroicon-o-check-circle')
                    ->color('info')
                    ->requiresConfirmation()
                    ->visible(fn (AdminEpisodeLog $record) => (auth()->user()?->isSuperAdmin() ?? false) && $record->status === AdminEpisodeLog::STATUS_PENDING)
                    ->action(fn (AdminEpisodeLog $record) => $record->update(['status' => AdminEpisodeLog::STATUS_APPROVED])),
                Tables\Actions\Action::make('markPaid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Placeholder::make('amount_display')
                            ->label('Nominal')
                            ->content(fn (AdminEpisodeLog $record) => 'IDR ' . number_format($record->amount, 0, ',', '.')),
                        Forms\Components\Placeholder::make('payout_method')
                            ->label('Metode')
                            ->content(fn (AdminEpisodeLog $record) => $record->user->payout_method ?? '-'),
                        Forms\Components\Placeholder::make('bank_name')
                            ->label('Bank/Provider')
                            ->content(fn (AdminEpisodeLog $record) => $record->user->bank_name ?? $record->user->payout_wallet_provider ?? '-'),
                        Forms\Components\Placeholder::make('bank_account_number')
                            ->label('No. Rekening / Wallet')
                            ->content(fn (AdminEpisodeLog $record) => $record->user->bank_account_number ?? $record->user->payout_wallet_number ?? '-'),
                        Forms\Components\Placeholder::make('bank_account_holder')
                            ->label('Atas Nama')
                            ->content(fn (AdminEpisodeLog $record) => $record->user->bank_account_holder ?? '-'),
                        Forms\Components\Placeholder::make('payout_notes')
                            ->label('Catatan')
                            ->content(fn (AdminEpisodeLog $record) => $record->user->payout_notes ?? '-'),
                    ])
                    ->visible(fn (AdminEpisodeLog $record) => (auth()->user()?->isSuperAdmin() ?? false) && $record->status !== AdminEpisodeLog::STATUS_PAID)
                    ->action(fn (AdminEpisodeLog $record) => $record->update(['status' => AdminEpisodeLog::STATUS_PAID])),
                Tables\Actions\EditAction::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
            ])
            ->bulkActions([
                Tables\Actions\BulkAction::make('markPaid')
                    ->label('Tandai Dibayar')
                    ->icon('heroicon-o-currency-dollar')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($records) => 'Tandai Dibayar (' . $records->count() . ' log)')
                    ->modalSubheading(fn ($records) => 'Total IDR ' . number_format($records->sum('amount'), 0, ',', '.'))
                    ->form(function ($records) {
                        $methods = $records->pluck('user.payout_method')->filter()->unique()->values();
                        $banks = $records->map(fn ($r) => $r->user->bank_name ?? $r->user->payout_wallet_provider)->filter()->unique()->values();
                        $numbers = $records->map(fn ($r) => $r->user->bank_account_number ?? $r->user->payout_wallet_number)->filter()->unique()->values();
                        $holders = $records->pluck('user.bank_account_holder')->filter()->unique()->values();
                        $notes = $records->pluck('user.payout_notes')->filter()->unique()->values();

                        $list = function ($col) {
                            return $col->isEmpty() ? '-' : $col->join(', ');
                        };

                        return [
                            Forms\Components\Placeholder::make('payout_method')
                                ->label('Metode')
                                ->content(fn () => $list($methods)),
                            Forms\Components\Placeholder::make('bank_name')
                                ->label('Bank/Provider')
                                ->content(fn () => $list($banks)),
                            Forms\Components\Placeholder::make('bank_account_number')
                                ->label('No. Rekening / Wallet')
                                ->content(fn () => $list($numbers)),
                            Forms\Components\Placeholder::make('bank_account_holder')
                                ->label('Atas Nama')
                                ->content(fn () => $list($holders)),
                            Forms\Components\Placeholder::make('payout_notes')
                                ->label('Catatan')
                                ->content(fn () => $list($notes)),
                        ];
                    })
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false)
                    ->action(fn ($records) => $records->each->update(['status' => AdminEpisodeLog::STATUS_PAID])),
                Tables\Actions\DeleteBulkAction::make()
                    ->visible(fn () => auth()->user()?->isSuperAdmin() ?? false),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()
            ->with(['user', 'episode.anime']);

        $user = auth()->user();
        if ($user && !$user->isSuperAdmin()) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdminEpisodeLogs::route('/'),
            'create' => Pages\CreateAdminEpisodeLog::route('/create'),
            'edit' => Pages\EditAdminEpisodeLog::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = static::getModel()::where('status', AdminEpisodeLog::STATUS_PENDING)->count();

        return $pending > 0 ? (string) $pending : null;
    }
}
