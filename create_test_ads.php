<?php

use App\Models\Advertisement;
use Illuminate\Support\Facades\Artisan;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

// Clear existing test ads
Advertisement::where('name', 'like', '%(Test)%')->delete();

// Header Bottom Ad
Advertisement::create([
    'name' => 'Header Banner (Test)',
    'position' => 'header_bottom',
    'type' => 'html',
    'size' => 'responsive',
    'code' => '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 2px dashed #4a5568; border-radius: 12px; padding: 20px; text-align: center; margin: 0 auto; max-width: 728px;">
        <p style="color: #718096; font-size: 14px; margin: 0;">📢 IKLAN HEADER</p>
        <p style="color: #4a5568; font-size: 12px; margin: 5px 0 0 0;">728x90 - Leaderboard</p>
    </div>',
    'is_active' => true,
    'show_on_mobile' => true,
    'show_on_desktop' => true,
    'order' => 1,
]);

// Sidebar Top Ad
Advertisement::create([
    'name' => 'Sidebar Top (Test)',
    'position' => 'sidebar_top',
    'type' => 'html',
    'size' => '300x250',
    'code' => '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 2px dashed #4a5568; border-radius: 12px; padding: 50px 20px; text-align: center;">
        <p style="color: #718096; font-size: 14px; margin: 0;">📢 IKLAN SIDEBAR</p>
        <p style="color: #4a5568; font-size: 12px; margin: 5px 0 0 0;">300x250 - Rectangle</p>
    </div>',
    'is_active' => true,
    'show_on_mobile' => false,
    'show_on_desktop' => true,
    'order' => 1,
]);

// Sidebar Bottom Ad  
Advertisement::create([
    'name' => 'Sidebar Bottom (Test)',
    'position' => 'sidebar_bottom',
    'type' => 'html',
    'size' => '300x250',
    'code' => '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 2px dashed #4a5568; border-radius: 12px; padding: 50px 20px; text-align: center;">
        <p style="color: #718096; font-size: 14px; margin: 0;">📢 IKLAN SIDEBAR</p>
        <p style="color: #4a5568; font-size: 12px; margin: 5px 0 0 0;">300x250 - Rectangle</p>
    </div>',
    'is_active' => true,
    'show_on_mobile' => false,
    'show_on_desktop' => true,
    'order' => 2,
]);

// Content Top Ad
Advertisement::create([
    'name' => 'Content Top (Test)',
    'position' => 'content_top',
    'type' => 'html',
    'size' => 'responsive',
    'code' => '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 2px dashed #4a5568; border-radius: 12px; padding: 20px; text-align: center; margin: 0 auto; max-width: 970px;">
        <p style="color: #718096; font-size: 14px; margin: 0;">📢 IKLAN KONTEN ATAS</p>
        <p style="color: #4a5568; font-size: 12px; margin: 5px 0 0 0;">970x90 - Large Leaderboard</p>
    </div>',
    'is_active' => true,
    'show_on_mobile' => true,
    'show_on_desktop' => true,
    'order' => 1,
]);

// Content Bottom Ad
Advertisement::create([
    'name' => 'Content Bottom (Test)',
    'position' => 'content_bottom',
    'type' => 'html',
    'size' => 'responsive',
    'code' => '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 2px dashed #4a5568; border-radius: 12px; padding: 20px; text-align: center; margin: 0 auto; max-width: 970px;">
        <p style="color: #718096; font-size: 14px; margin: 0;">📢 IKLAN KONTEN BAWAH</p>
        <p style="color: #4a5568; font-size: 12px; margin: 5px 0 0 0;">970x90 - Large Leaderboard</p>
    </div>',
    'is_active' => true,
    'show_on_mobile' => true,
    'show_on_desktop' => true,
    'order' => 1,
]);

// Footer Ad
Advertisement::create([
    'name' => 'Footer Banner (Test)',
    'position' => 'footer',
    'type' => 'html',
    'size' => 'responsive',
    'code' => '<div style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 100%); border: 2px dashed #4a5568; border-radius: 12px; padding: 20px; text-align: center; margin: 0 auto; max-width: 728px;">
        <p style="color: #718096; font-size: 14px; margin: 0;">📢 IKLAN FOOTER</p>
        <p style="color: #4a5568; font-size: 12px; margin: 5px 0 0 0;">728x90 - Leaderboard</p>
    </div>',
    'is_active' => true,
    'show_on_mobile' => true,
    'show_on_desktop' => true,
    'order' => 1,
]);

echo "✅ Test ads created successfully!\n";
echo "Total ads: " . Advertisement::count() . "\n";
