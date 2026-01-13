<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapeConfig extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'source',
        'sync_type',
        'is_active',
        'auto_sync',
        'schedule',
        'max_items',
        'filters',
        'last_run_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_sync' => 'boolean',
        'filters' => 'array',
        'last_run_at' => 'datetime',
    ];

    public function logs()
    {
        return $this->hasMany(ScrapeLog::class);
    }

    public function latestLog()
    {
        return $this->hasOne(ScrapeLog::class)->latestOfMany();
    }
}
