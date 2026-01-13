<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'anime_id',
        'day_of_week',
        'broadcast_time',
        'next_episode_date',
        'timezone',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'next_episode_date' => 'date',
        'is_active' => 'boolean',
    ];

    /**
     * Get the anime that this schedule belongs to.
     */
    public function anime(): BelongsTo
    {
        return $this->belongsTo(Anime::class);
    }

    /**
     * Get formatted day of week in Indonesian
     */
    public function getDayInIndonesian(): string
    {
        $days = [
            'Monday' => 'Senin',
            'Tuesday' => 'Selasa',
            'Wednesday' => 'Rabu',
            'Thursday' => 'Kamis',
            'Friday' => 'Jumat',
            'Saturday' => 'Sabtu',
            'Sunday' => 'Minggu',
        ];

        return $days[$this->day_of_week] ?? $this->day_of_week;
    }

    /**
     * Scope to get active schedules
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to get schedules by day
     */
    public function scopeByDay($query, string $day)
    {
        return $query->where('day_of_week', $day);
    }
}
