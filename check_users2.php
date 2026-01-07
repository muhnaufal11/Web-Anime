<?php
/**
 * Check user birth_date field
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== All Users with birth_date ===\n";
$users = App\Models\User::all();
foreach ($users as $user) {
    $age = $user->birth_date ? \Carbon\Carbon::parse($user->birth_date)->age : 'N/A';
    $isAdult = $user->isAdult() ? 'YES' : 'NO';
    echo sprintf(
        "ID: %d | Email: %s | birth_date: %s | Age: %s | isAdult: %s\n",
        $user->id,
        $user->email,
        $user->birth_date ?? 'NOT SET',
        $age,
        $isAdult
    );
}
