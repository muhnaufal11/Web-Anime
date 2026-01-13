<?php

/**
 * Check Current User Session
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║   CHECK SESSIONS                                           ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// Clear all sessions
DB::table('sessions')->truncate();

echo "✅ All sessions cleared!\n\n";
echo "📝 Please:\n";
echo "   1. Close browser completely\n";
echo "   2. Reopen browser\n";
echo "   3. Go to: http://localhost/\n";
echo "   4. Login with: naufalrabbani146@gmail.com\n";
echo "   5. Try access: http://localhost/admin\n\n";
