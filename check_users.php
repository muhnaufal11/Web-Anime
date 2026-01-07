<?php
/**
 * Check all users and their adult status
 */

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "=== All Users ===\n";
$users = App\Models\User::all();
foreach ($users as $user) {
    $age = $user->birthday ? \Carbon\Carbon::parse($user->birthday)->age : 'N/A';
    $isAdult = method_exists($user, 'isAdult') ? ($user->isAdult() ? 'YES' : 'NO') : 'method missing';
    echo sprintf(
        "ID: %d | Email: %s | Birthday: %s | Age: %s | isAdult: %s\n",
        $user->id,
        $user->email,
        $user->birthday ?? 'NOT SET',
        $age,
        $isAdult
    );
}

echo "\n=== Check if logged in users exist in session (active sessions) ===\n";
$sessions = DB::table('sessions')->get();
echo "Total sessions: " . $sessions->count() . "\n";

foreach ($sessions as $session) {
    if ($session->user_id) {
        $user = App\Models\User::find($session->user_id);
        if ($user) {
            $age = $user->birthday ? \Carbon\Carbon::parse($user->birthday)->age : 'N/A';
            $isAdult = method_exists($user, 'isAdult') ? ($user->isAdult() ? 'YES' : 'NO') : 'N/A';
            echo sprintf("Session for user: %s | Age: %s | isAdult: %s\n", $user->email, $age, $isAdult);
        }
    }
}
