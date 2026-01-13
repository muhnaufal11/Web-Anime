<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ScrapeLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'scrape_config_id',
        'source',
        'type',
        'status',
        'items_processed',
        'items_created',
        'items_updated',
        'items_failed',
        'message',
        'errors',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'errors' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public function config()
    {
        return $this->belongsTo(ScrapeConfig::class, 'scrape_config_id');
    }
}
