<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Advertisement extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'position',
        'type',
        'code',
        'image_path',
        'link',
        'size',
        'is_active',
        'order',
        'start_date',
        'end_date',
        'pages',
        'show_on_mobile',
        'show_on_desktop',
        'impressions',
        'clicks',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'show_on_mobile' => 'boolean',
        'show_on_desktop' => 'boolean',
        'start_date' => 'datetime',
        'end_date' => 'datetime',
        'pages' => 'array',
    ];

    // Posisi yang tersedia
    public const POSITIONS = [
        'header_top' => 'Header Atas (Di atas navbar)',
        'header_bottom' => 'Header Bawah (Di bawah navbar)',
        'sidebar_top' => 'Sidebar Atas',
        'sidebar_bottom' => 'Sidebar Bawah',
        'content_top' => 'Konten Atas',
        'content_middle' => 'Konten Tengah',
        'content_bottom' => 'Konten Bawah',
        'footer' => 'Footer',
        'popup' => 'Popup',
        'floating' => 'Floating/Sticky',
        'video_overlay' => 'Video Player Overlay (dengan timer)',
        'video_before' => 'Sebelum Video',
        'video_after' => 'Setelah Video',
    ];

    // Tipe iklan
    public const TYPES = [
        'adsense' => 'Google AdSense',
        'custom' => 'HTML Custom',
        'image' => 'Gambar + Link',
        'html' => 'HTML/JavaScript',
    ];

    // Ukuran standar
    public const SIZES = [
        '728x90' => 'Leaderboard (728x90)',
        '970x90' => 'Large Leaderboard (970x90)',
        '320x100' => 'Large Mobile Banner (320x100)',
        '300x250' => 'Medium Rectangle (300x250)',
        '336x280' => 'Large Rectangle (336x280)',
        '300x600' => 'Half Page (300x600)',
        '160x600' => 'Wide Skyscraper (160x600)',
        '320x50' => 'Mobile Banner (320x50)',
        'responsive' => 'Responsive',
        'custom' => 'Custom',
    ];

    // Scope: Iklan aktif
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(function ($q) {
                $q->whereNull('start_date')
                    ->orWhere('start_date', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('end_date')
                    ->orWhere('end_date', '>=', now());
            });
    }

    // Scope: Berdasarkan posisi
    public function scopePosition($query, $position)
    {
        return $query->where('position', $position);
    }

    // Scope: Untuk halaman tertentu
    public function scopeForPage($query, $page)
    {
        return $query->where(function ($q) use ($page) {
            $q->whereNull('pages')
                ->orWhereJsonContains('pages', $page)
                ->orWhereJsonContains('pages', 'all');
        });
    }

    // Scope: Berdasarkan device
    public function scopeForDevice($query)
    {
        $isMobile = request()->header('User-Agent') && 
                    preg_match('/Mobile|Android|iPhone/i', request()->header('User-Agent'));
        
        if ($isMobile) {
            return $query->where('show_on_mobile', true);
        }
        
        return $query->where('show_on_desktop', true);
    }

    // Get ads by position with caching
    public static function getByPosition($position, $page = 'all')
    {
        $cacheKey = "ads_{$position}_{$page}";
        
        return Cache::remember($cacheKey, 300, function () use ($position, $page) {
            return self::active()
                ->position($position)
                ->forPage($page)
                ->forDevice()
                ->orderBy('order')
                ->get();
        });
    }

    // Increment impression
    public function incrementImpression()
    {
        $this->increment('impressions');
    }

    // Increment click
    public function incrementClick()
    {
        $this->increment('clicks');
    }

    // Get CTR (Click Through Rate)
    public function getCtrAttribute()
    {
        if ($this->impressions === 0) {
            return 0;
        }
        return round(($this->clicks / $this->impressions) * 100, 2);
    }

    // Clear ads cache
    public static function clearCache()
    {
        $positions = array_keys(self::POSITIONS);
        $pages = ['all', 'home', 'detail', 'watch', 'search', 'movie', 'schedule', 'anime', 'request'];
        
        foreach ($positions as $position) {
            foreach ($pages as $page) {
                Cache::forget("ads_{$position}_{$page}");
            }
        }
        
        // Also clear any pattern-based cache if using tagged cache
        try {
            Cache::flush(); // Nuclear option - clear all cache
        } catch (\Exception $e) {
            // If flush not supported, continue silently
        }
    }

    // Boot method to clear cache on changes
    protected static function booted()
    {
        static::saved(function () {
            self::clearCache();
        });

        static::deleted(function () {
            self::clearCache();
        });
    }

    // Render the ad HTML
    public function render()
    {
        switch ($this->type) {
            case 'adsense':
                return $this->code;
            
            case 'image':
                $img = '<img src="' . asset('storage/' . $this->image_path) . '" alt="' . e($this->name) . '" class="w-full h-auto">';
                if ($this->link) {
                    return '<a href="' . e($this->link) . '" target="_blank" rel="noopener" onclick="trackAdClick(' . $this->id . ')">' . $img . '</a>';
                }
                return $img;
            
            case 'custom':
            case 'html':
                return $this->code;
            
            default:
                return '';
        }
    }
}
