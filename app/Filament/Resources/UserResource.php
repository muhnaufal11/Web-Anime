<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Resources\Form;
use Filament\Resources\Resource;
use Filament\Resources\Table;
use Filament\Tables;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Builder;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';
    
    protected static ?string $navigationGroup = 'User Management';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Akun')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('Nama')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => filled($state) ? Hash::make($state) : null)
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->minLength(8)
                            ->helperText('Kosongkan jika tidak ingin mengubah password'),
                    ])->columns(2),
                
                Forms\Components\Section::make('Profil')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar')
                            ->label('Avatar')
                            ->image()
                            ->directory('avatars')
                            ->imageResizeMode('cover')
                            ->imageCropAspectRatio('1:1')
                            ->imageResizeTargetWidth('200')
                            ->imageResizeTargetHeight('200'),
                        Forms\Components\Textarea::make('bio')
                            ->label('Bio')
                            ->maxLength(500)
                            ->rows(3),
                        Forms\Components\TextInput::make('phone')
                            ->label('Nomor Telepon')
                            ->tel()
                            ->maxLength(20),
                        Forms\Components\Select::make('gender')
                            ->label('Jenis Kelamin')
                            ->options([
                                'male' => 'Laki-laki',
                                'female' => 'Perempuan',
                            ]),
                        Forms\Components\DatePicker::make('birth_date')
                            ->label('Tanggal Lahir'),
                        Forms\Components\TextInput::make('location')
                            ->label('Lokasi')
                            ->maxLength(255),
                    ])->columns(2),
                
                Forms\Components\Section::make('Role & Status')
                    ->schema([
                        Forms\Components\Select::make('role')
                            ->label('Role')
                            ->options([
                                User::ROLE_USER => 'User',
                                User::ROLE_ADMIN_UPLOAD => 'Admin Upload (Video Manual)',
                                User::ROLE_ADMIN_SYNC => 'Admin Sync (Sync Server)',
                                User::ROLE_SUPERADMIN => 'Superadmin (Full Access)',
                            ])
                            ->default(User::ROLE_USER)
                            ->helperText('Admin Upload: hanya bisa upload video manual. Admin Sync: hanya bisa sync server.')
                            ->visible(fn () => auth()->user()?->isSuperAdmin())
                            ->required()
                            ->reactive(),
                        Forms\Components\Placeholder::make('role_readonly')
                            ->label('Role')
                            ->content(fn ($record) => match($record?->role) {
                                User::ROLE_ADMIN_UPLOAD => 'Admin Upload',
                                User::ROLE_ADMIN_SYNC => 'Admin Sync',
                                User::ROLE_SUPERADMIN => 'Superadmin',
                                User::ROLE_ADMIN => 'Admin (Legacy)',
                                default => 'User',
                            })
                            ->visible(fn () => !auth()->user()?->isSuperAdmin()),
                        Forms\Components\TextInput::make('payment_rate')
                            ->label('Rate Bayaran per Episode')
                            ->numeric()
                            ->default(500)
                            ->suffix('IDR')
                            ->helperText('Bayaran untuk setiap episode yang di-sync/upload. Default: 500')
                            ->visible(fn () => auth()->user()?->isSuperAdmin())
                            ->required(),
                        Forms\Components\Placeholder::make('payment_rate_readonly')
                            ->label('Rate Bayaran')
                            ->content(fn ($record) => 'IDR ' . number_format($record?->payment_rate ?? 500, 0, ',', '.'))
                            ->visible(fn () => !auth()->user()?->isSuperAdmin()),
                    ]),
                
                // Section khusus sistem limit pembayaran - hanya untuk superadmin dan admin
                Forms\Components\Section::make('Sistem Limit Pembayaran')
                    ->description('Pengaturan batas pencairan bulanan sesuai Pasal 3 kontrak')
                    ->schema([
                        Forms\Components\Select::make('admin_level')
                            ->label('Level Admin')
                            ->options([
                                User::LEVEL_1 => 'Level 1 (Baru)',
                                User::LEVEL_2 => 'Level 2 (Senior)',
                                User::LEVEL_3 => 'Level 3 (Pro)',
                                User::LEVEL_4 => 'Level 4 (Expert)',
                                User::LEVEL_5 => 'Level 5 (Master)',
                                User::LEVEL_UNLIMITED => 'Unlimited - Tanpa Batas',
                            ])
                            ->default(User::LEVEL_1)
                            ->helperText(function ($get) {
                                $role = $get('role');
                                if ($role === User::ROLE_ADMIN_SYNC) {
                                    return 'Lv1: 300k | Lv2: 525k | Lv3: 750k | Lv4: 1.05jt | Lv5: 1.35jt (Target: 200→350→500→700→900 sync)';
                                } elseif ($role === User::ROLE_ADMIN_UPLOAD) {
                                    return 'Lv1: 500k | Lv2: 800k | Lv3: 1.1jt | Lv4: 1.4jt | Lv5: 1.8jt (Target: 50→80→110→140→180 upload)';
                                }
                                return 'Pilih role admin terlebih dahulu';
                            })
                            ->reactive()
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\TextInput::make('consecutive_success_months')
                            ->label('Bulan Berturut-turut Sukses')
                            ->numeric()
                            ->default(0)
                            ->helperText('Butuh 6 bulan berturut-turut untuk naik level (Hard Mode)')
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\TextInput::make('monthly_limit')
                            ->label('Custom Limit Bulanan (Opsional)')
                            ->numeric()
                            ->suffix('IDR')
                            ->placeholder('Kosongkan untuk pakai default')
                            ->helperText('Isi jika ingin override limit default berdasarkan level')
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\TextInput::make('rollover_balance')
                            ->label('Saldo Rollover')
                            ->numeric()
                            ->default(0)
                            ->suffix('IDR')
                            ->helperText('Saldo yang belum dibayar dari bulan lalu (sistem rollover)')
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\DatePicker::make('admin_start_date')
                            ->label('Tanggal Mulai Kerja')
                            ->helperText('Untuk menghitung masa evaluasi 3 bulan')
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Evaluasi')
                            ->rows(2)
                            ->helperText('Catatan internal tentang kinerja admin')
                            ->visible(fn () => auth()->user()?->isSuperAdmin()),
                        
                        // Placeholder untuk info pembayaran
                        Forms\Components\Placeholder::make('payment_info')
                            ->label('Info Pembayaran Bulan Ini')
                            ->content(function ($record) {
                                if (!$record || !$record->isAdmin()) {
                                    return 'N/A';
                                }
                                
                                $calc = $record->calculateMonthlyPayment(now()->year, now()->month);
                                $limit = $calc['limit'] ? 'Rp ' . number_format($calc['limit'], 0, ',', '.') : 'Unlimited';
                                
                                return view('filament.components.payment-info', [
                                    'unpaid' => $calc['approved_this_month'],
                                    'rollover' => $calc['rollover_from_previous'],
                                    'total' => $calc['total_available'],
                                    'limit' => $limit,
                                    'payable' => $calc['payable'],
                                    'nextRollover' => $calc['rollover_to_next'],
                                    'daysUntilPayday' => $record->getDaysUntilPayday(),
                                ]);
                            })
                            ->visible(fn ($record) => $record && $record->isAdmin()),
                    ])
                    ->visible(fn ($record, $get) => 
                        auth()->user()?->isSuperAdmin() && 
                        in_array($get('role') ?? $record?->role, [User::ROLE_ADMIN_UPLOAD, User::ROLE_ADMIN_SYNC, User::ROLE_SUPERADMIN])
                    )
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\ImageColumn::make('avatar')
                    ->label('Avatar')
                    ->circular()
                    ->url(fn ($record) => $record->avatar ? asset('storage/' . $record->avatar) : null)
                    ->getStateUsing(fn ($record) => $record->avatar ? asset('storage/' . $record->avatar) : null),
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('role')
                    ->label('Role')
                    ->colors([
                        'gray' => User::ROLE_USER,
                        'success' => User::ROLE_ADMIN,
                        'primary' => User::ROLE_ADMIN_UPLOAD,
                        'info' => User::ROLE_ADMIN_SYNC,
                        'warning' => User::ROLE_SUPERADMIN,
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        User::ROLE_ADMIN_UPLOAD => 'Upload',
                        User::ROLE_ADMIN_SYNC => 'Sync',
                        User::ROLE_SUPERADMIN => 'Super',
                        User::ROLE_ADMIN => 'Admin',
                        default => 'User',
                    })
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_rate')
                    ->label('Rate')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 500, 0, ',', '.'))
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\BadgeColumn::make('admin_level')
                    ->label('Level')
                    ->formatStateUsing(fn ($state, $record) => match($state) {
                        User::LEVEL_UNLIMITED => '∞',
                        User::LEVEL_5 => 'Lv.5',
                        User::LEVEL_4 => 'Lv.4',
                        User::LEVEL_3 => 'Lv.3',
                        User::LEVEL_2 => 'Lv.2',
                        default => 'Lv.1',
                    })
                    ->colors([
                        'success' => User::LEVEL_UNLIMITED,
                        'danger' => User::LEVEL_5,
                        'primary' => User::LEVEL_4,
                        'info' => User::LEVEL_3,
                        'secondary' => User::LEVEL_2,
                        'warning' => User::LEVEL_1,
                    ])
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('monthly_limit_display')
                    ->label('Limit/bln')
                    ->getStateUsing(fn ($record) => $record->getMonthlyLimit())
                    ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 0, ',', '.') : '∞')
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('rollover_balance')
                    ->label('Rollover')
                    ->formatStateUsing(fn ($state) => $state > 0 ? 'Rp ' . number_format($state, 0, ',', '.') : '-')
                    ->color(fn ($record) => ($record->rollover_balance ?? 0) > 0 ? 'warning' : null)
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('created_episodes_count')
                    ->label('Episode Dibuat')
                    ->counts('createdEpisodes')
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('admin_episode_logs_sum_amount')
                    ->label('Total Bayaran')
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state ?? 0, 0, ',', '.'))
                    ->sortable()
                    ->visible(fn () => auth()->user()?->isSuperAdmin()),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar')
                    ->dateTime('d M Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('comments_count')
                    ->label('Komentar')
                    ->counts('comments')
                    ->sortable(),
                Tables\Columns\TextColumn::make('watch_histories_count')
                    ->label('Riwayat')
                    ->counts('watchHistories')
                    ->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('role')
                    ->options([
                        User::ROLE_USER => 'User',
                        User::ROLE_ADMIN => 'Admin',
                        User::ROLE_SUPERADMIN => 'Superadmin',
                    ]),
                Tables\Filters\Filter::make('has_avatar')
                    ->label('Punya Avatar')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('avatar')),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleAdmin')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->label(fn ($record) => $record->isAdmin() ? 'Turunkan ke User' : 'Jadikan Admin')
                    ->icon(fn ($record) => $record->isAdmin() ? 'heroicon-o-shield-exclamation' : 'heroicon-o-shield-check')
                    ->color(fn ($record) => $record->isAdmin() ? 'danger' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn ($record) => $record->isAdmin() ? 'Hapus Role Admin?' : 'Jadikan Admin?')
                    ->modalSubheading(fn ($record) => $record->isAdmin()
                        ? 'User ini tidak akan bisa mengakses admin panel lagi.'
                        : 'User ini akan mendapat akses ke admin panel.')
                    ->action(function ($record) {
                        if ($record->isSuperAdmin() || $record->id === auth()->id()) {
                            throw new \Exception('Tidak bisa mengubah role superadmin atau akun sendiri.');
                        }

                        $newRole = $record->isAdmin() ? User::ROLE_USER : User::ROLE_ADMIN;
                        $record->update([
                            'role' => $newRole,
                            'is_admin' => $newRole !== User::ROLE_USER,
                        ]);
                    }),
                Tables\Actions\DeleteAction::make()
                    ->before(function ($record) {
                        // Prevent deleting own account
                        if ($record->id === auth()->id()) {
                            throw new \Exception('Tidak bisa menghapus akun sendiri!');
                        }
                    }),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make()
                    ->before(function ($records) {
                        // Prevent deleting own account in bulk
                        if ($records->contains('id', auth()->id())) {
                            throw new \Exception('Tidak bisa menghapus akun sendiri!');
                        }
                    }),
                Tables\Actions\BulkAction::make('makeAdmin')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->label('Jadikan Admin')
                    ->icon('heroicon-o-shield-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(function ($record) {
                        if ($record->isSuperAdmin()) {
                            return;
                        }
                        $record->update([
                            'role' => User::ROLE_ADMIN,
                            'is_admin' => true,
                        ]);
                    })),
                Tables\Actions\BulkAction::make('removeAdmin')
                    ->visible(fn () => auth()->user()?->isSuperAdmin())
                    ->label('Hapus Role Admin')
                    ->icon('heroicon-o-shield-exclamation')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn ($records) => $records->each(function ($record) {
                        if ($record->id !== auth()->id() && !$record->isSuperAdmin()) {
                            $record->update([
                                'role' => User::ROLE_USER,
                                'is_admin' => false,
                            ]);
                        }
                    })),
            ]);
    }
    
    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()?->isSuperAdmin()) {
            $query->withCount('createdEpisodes')
                ->withSum('adminEpisodeLogs as admin_episode_logs_sum_amount', 'amount');
        }

        return $query;
    }
    
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
    
    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }
}
