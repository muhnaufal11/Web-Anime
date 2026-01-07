<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Support\Facades\DB;

// Find maintenance setting
$maintenance = DB::table('settings')->where('key', 'like', '%maintenance%')->get();
echo "Maintenance settings:\n";
foreach ($maintenance as $s) {
    echo "- {$s->key} = {$s->value}\n";
}

// Disable maintenance
$updated = DB::table('settings')->where('key', 'maintenance_mode')->update(['value' => '0']);
echo "\nDisabled maintenance_mode: $updated rows updated\n";

// Clear cache
\Artisan::call('cache:clear');
echo "Cache cleared\n";
