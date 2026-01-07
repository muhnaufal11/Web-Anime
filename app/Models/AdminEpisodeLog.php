<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdminEpisodeLog extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_PAID = 'paid';

    // Amount constants
    public const AMOUNT_SYNC = 500;        // Untuk sync dari embed/HTML
    public const AMOUNT_UPLOAD = 2500;     // Untuk upload video manual (Server Admin)
    public const DEFAULT_AMOUNT = 500;     // Default (backward compatibility)

    protected $fillable = [
        'user_id',
        'episode_id',
        'amount',
        'status',
        'note',
        'rejection_reason',
        'paid_at',
        'payment_month',
    ];

    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'paid_at' => 'datetime',
        'payment_month' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function episode(): BelongsTo
    {
        return $this->belongsTo(Episode::class);
    }
}
