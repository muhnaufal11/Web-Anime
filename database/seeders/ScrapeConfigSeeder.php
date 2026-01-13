<?php

namespace Database\Seeders;

use App\Models\ScrapeConfig;
use Illuminate\Database\Seeder;

class ScrapeConfigSeeder extends Seeder
{
    public function run()
    {
        ScrapeConfig::create([
            'name' => 'Default Seasonal Sync',
            'source' => 'both',
            'sync_type' => 'both',
            'is_active' => true,
            'auto_sync' => false,
            'max_items' => 25,
            'filters' => [
                'season' => 'current',
            ],
        ]);

        ScrapeConfig::create([
            'name' => 'MAL Metadata Only',
            'source' => 'myanimelist',
            'sync_type' => 'metadata',
            'is_active' => true,
            'auto_sync' => false,
            'max_items' => 50,
        ]);

        ScrapeConfig::create([
            'name' => 'AnimeSail Episodes',
            'source' => 'animesail',
            'sync_type' => 'episodes',
            'is_active' => true,
            'auto_sync' => false,
            'max_items' => 10,
        ]);
    }
}
