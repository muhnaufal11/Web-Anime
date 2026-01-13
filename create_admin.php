<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\User;

$user = User::firstOrCreate(
    ['email' => 'admin@example.com'],
    [
        'name' => 'Admin',
        'password' => bcrypt('password'),
        'role' => User::ROLE_ADMIN,
        'is_admin' => true,
    ]
);

echo "✓ Admin user created: {$user->email}\n";
?>
