<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable;

    public const ROLE_USER = 'user';
    public const ROLE_ADMIN = 'admin';           // Legacy - akan dihapus
    public const ROLE_ADMIN_UPLOAD = 'admin_upload'; // Hanya bisa upload manual
    public const ROLE_ADMIN_SYNC = 'admin_sync';     // Hanya bisa sync server
    public const ROLE_SUPERADMIN = 'superadmin';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    // Admin levels (1-5, with 0 = unlimited for superadmin)
    public const LEVEL_UNLIMITED = 0;
    public const LEVEL_1 = 1;
    public const LEVEL_2 = 2;
    public const LEVEL_3 = 3;
    public const LEVEL_4 = 4;
    public const LEVEL_5 = 5;
    
    // Default monthly limits per level & role (dalam Rupiah) - Hard Mode
    // Uploader: Rate ~Rp 10k/item
    // Syncer: Rate ~Rp 1.5k/item
    public const DEFAULT_LIMITS = [
        self::ROLE_ADMIN_SYNC => [
            self::LEVEL_1 => 300000,    // Level 1: 200 sync × 1.5k = 300k
            self::LEVEL_2 => 525000,    // Level 2: 350 sync × 1.5k = 525k
            self::LEVEL_3 => 750000,    // Level 3: 500 sync × 1.5k = 750k
            self::LEVEL_4 => 1050000,   // Level 4: 700 sync × 1.5k = 1.05jt
            self::LEVEL_5 => 1350000,   // Level 5: 900 sync × 1.5k = 1.35jt
            self::LEVEL_UNLIMITED => null,
        ],
        self::ROLE_ADMIN_UPLOAD => [
            self::LEVEL_1 => 500000,    // Level 1: 50 upload × 10k = 500k
            self::LEVEL_2 => 800000,    // Level 2: 80 upload × 10k = 800k
            self::LEVEL_3 => 1100000,   // Level 3: 110 upload × 10k = 1.1jt
            self::LEVEL_4 => 1400000,   // Level 4: 140 upload × 10k = 1.4jt
            self::LEVEL_5 => 1800000,   // Level 5: 180 upload × 10k = 1.8jt
            self::LEVEL_UNLIMITED => null,
        ],
    ];

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'avatar',
        'bio',
        'phone',
        'gender',
        'birth_date',
        'location',
        'is_admin',
        'role',
        'bank_name',
        'bank_account_number',
        'bank_account_holder',
        'payout_method',
        'payout_wallet_provider',
        'payout_wallet_number',
        'payout_notes',
        'payment_rate',
        'admin_level',
        'monthly_limit',
        'rollover_balance',
        'admin_start_date',
        'admin_notes',
        'consecutive_success_months',
        'last_evaluation_date',
    ];

    protected $attributes = [
        'role' => self::ROLE_USER,
        'payment_rate' => 500, // Default payment rate
        'admin_level' => 1,    // Default level 1 (baru)
        'rollover_balance' => 0,
        'consecutive_success_months' => 0, // Default 0 bulan berturut
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'birth_date' => 'date',
        'admin_start_date' => 'date',
        'last_evaluation_date' => 'date',
        'role' => 'string',
        'admin_level' => 'integer',
        'monthly_limit' => 'integer',
        'rollover_balance' => 'integer',
        'consecutive_success_months' => 'integer',
    ];

    /**
     * Determine if the user can access Filament admin panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->isAdmin();
    }

    /**
     * Alternative method name for Filament compatibility.
     */
    public function canAccessFilament(): bool
    {
        return $this->isAdmin();
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::ROLE_SUPERADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN,
            self::ROLE_ADMIN_UPLOAD,
            self::ROLE_ADMIN_SYNC,
            self::ROLE_SUPERADMIN
        ], true);
    }

    public function isAdminUpload(): bool
    {
        return $this->role === self::ROLE_ADMIN_UPLOAD;
    }

    public function isAdminSync(): bool
    {
        return $this->role === self::ROLE_ADMIN_SYNC;
    }

    public function canUploadVideo(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN_UPLOAD,
            self::ROLE_SUPERADMIN
        ], true);
    }

    public function canSyncServer(): bool
    {
        return in_array($this->role, [
            self::ROLE_ADMIN_SYNC,
            self::ROLE_SUPERADMIN
        ], true);
    }

    public function scopeAdmins($query)
    {
        return $query->whereIn('role', [
            self::ROLE_ADMIN,
            self::ROLE_ADMIN_UPLOAD,
            self::ROLE_ADMIN_SYNC,
            self::ROLE_SUPERADMIN
        ]);
    }

    public function getIsAdminAttribute($value): bool
    {
        return (bool) ($value ?? false) || $this->isAdmin();
    }

    public function setRoleAttribute($value): void
    {
        $this->attributes['role'] = $value;
        $this->attributes['is_admin'] = in_array($value, [
            self::ROLE_ADMIN,
            self::ROLE_ADMIN_UPLOAD,
            self::ROLE_ADMIN_SYNC,
            self::ROLE_SUPERADMIN
        ], true);
    }
    
    /**
     * Boot the model
     */
    protected static function boot()
    {
        parent::boot();
        
        // Auto-update pending logs when payment_rate changes
        static::updating(function (User $user) {
            if ($user->isDirty('payment_rate')) {
                $newRate = $user->payment_rate;
                // Update all pending logs with new rate
                AdminEpisodeLog::where('user_id', $user->id)
                    ->where('status', AdminEpisodeLog::STATUS_PENDING)
                    ->update(['amount' => $newRate]);
            }
        });
    }
    
    /**
     * Get the comments for the user.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
    
    /**
     * Get the watch histories for the user.
     */
    public function watchHistories(): HasMany
    {
        return $this->hasMany(WatchHistory::class);
    }

    public function createdEpisodes(): HasMany
    {
        return $this->hasMany(Episode::class, 'created_by');
    }

    public function adminEpisodeLogs(): HasMany
    {
        return $this->hasMany(AdminEpisodeLog::class);
    }

    /**
     * Get the effective monthly limit for this admin
     */
    public function getMonthlyLimit(): ?int
    {
        // Superadmin has no limit
        if ($this->isSuperAdmin()) {
            return null;
        }
        
        // If custom limit is set, use it
        if ($this->monthly_limit !== null) {
            return $this->monthly_limit;
        }
        
        // Use default limit based on role and level
        $role = $this->role;
        $level = $this->admin_level ?? self::LEVEL_1;
        
        // Unlimited level
        if ($level === self::LEVEL_UNLIMITED) {
            return null;
        }
        
        return self::DEFAULT_LIMITS[$role][$level] ?? 500000;
    }

    /**
     * Get admin level label
     */
    public function getAdminLevelLabel(): string
    {
        return match($this->admin_level) {
            self::LEVEL_UNLIMITED => 'Unlimited',
            self::LEVEL_5 => 'Level 5 (Master)',
            self::LEVEL_4 => 'Level 4 (Expert)',
            self::LEVEL_3 => 'Level 3 (Pro)',
            self::LEVEL_2 => 'Level 2 (Senior)',
            default => 'Level 1 (Baru)',
        };
    }

    /**
     * Calculate total unpaid earnings for a specific month (pending + approved)
     */
    public function getUnpaidEarningsForMonth(int $year, int $month): int
    {
        return $this->adminEpisodeLogs()
            ->whereIn('status', [AdminEpisodeLog::STATUS_PENDING, AdminEpisodeLog::STATUS_APPROVED])
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');
    }

    /**
     * Calculate total APPROVED earnings for a specific month (only approved, ready to pay)
     */
    public function getApprovedEarningsForMonth(int $year, int $month): int
    {
        return $this->adminEpisodeLogs()
            ->where('status', AdminEpisodeLog::STATUS_APPROVED)
            ->whereYear('created_at', $year)
            ->whereMonth('created_at', $month)
            ->sum('amount');
    }

    /**
     * Get total unpaid earnings (all time) - pending + approved
     */
    public function getTotalUnpaidEarnings(): int
    {
        return $this->adminEpisodeLogs()
            ->whereIn('status', [AdminEpisodeLog::STATUS_PENDING, AdminEpisodeLog::STATUS_APPROVED])
            ->sum('amount');
    }

    /**
     * Get total pending earnings (all time)
     */
    public function getTotalPendingEarnings(): int
    {
        return $this->adminEpisodeLogs()
            ->where('status', AdminEpisodeLog::STATUS_PENDING)
            ->sum('amount');
    }

    /**
     * Get total paid earnings (all time)
     */
    public function getTotalPaidEarnings(): int
    {
        return $this->adminEpisodeLogs()
            ->where('status', AdminEpisodeLog::STATUS_PAID)
            ->sum('amount');
    }

    /**
     * Calculate payable amount for this month (considering limit and rollover)
     * Only counts APPROVED status (not pending)
     * Returns array with: payable, rollover_to_next, total_unpaid
     */
    public function calculateMonthlyPayment(int $year, int $month): array
    {
        // Get APPROVED earnings only for this month (pending must be approved first)
        $approvedThisMonth = $this->getApprovedEarningsForMonth($year, $month);
        $rolloverFromPrevious = $this->rollover_balance ?? 0;
        $totalAvailable = $approvedThisMonth + $rolloverFromPrevious;
        
        $limit = $this->getMonthlyLimit();
        
        // No limit - pay everything
        if ($limit === null) {
            return [
                'approved_this_month' => $approvedThisMonth,
                'rollover_from_previous' => $rolloverFromPrevious,
                'total_available' => $totalAvailable,
                'limit' => null,
                'payable' => $totalAvailable,
                'rollover_to_next' => 0,
            ];
        }
        
        // Has limit
        $payable = min($totalAvailable, $limit);
        $rolloverToNext = max(0, $totalAvailable - $limit);
        
        return [
            'approved_this_month' => $approvedThisMonth,
            'rollover_from_previous' => $rolloverFromPrevious,
            'total_available' => $totalAvailable,
            'limit' => $limit,
            'payable' => $payable,
            'rollover_to_next' => $rolloverToNext,
        ];
    }

    /**
     * Check if admin has completed 3 months evaluation period
     */
    public function hasCompletedEvaluationPeriod(): bool
    {
        if (!$this->admin_start_date) {
            return false;
        }
        
        return $this->admin_start_date->diffInMonths(now()) >= 3;
    }

    /**
     * Get days until next payday (25th)
     */
    public function getDaysUntilPayday(): int
    {
        $today = now();
        $payday = $today->copy()->day(25);
        
        // If today is past 25th, next payday is next month
        if ($today->day > 25) {
            $payday->addMonth();
        }
        
        return $today->diffInDays($payday);
    }

    /**
     * Check if today is payday
     */
    public function isPayday(): bool
    {
        return now()->day === 25;
    }

    /**
     * Check if user is 18 years or older
     */
    public function isAdult(): bool
    {
        if (!$this->birth_date) {
            return false; // No birth date = assume not adult (for safety)
        }

        return $this->birth_date->age >= 18;
    }

    /**
     * Get user's age
     */
    public function getAge(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }
}
