<?php

require __DIR__.'/vendor/autoload.php';

$app = require_once __DIR__.'/bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use App\Models\ContactMessage;
use Illuminate\Support\Str;

$updated = 0;
$messages = ContactMessage::whereNull('view_token')->orWhere('view_token', '')->get();

foreach ($messages as $message) {
    $message->view_token = Str::random(48);
    $message->save();
    $updated++;
}

echo "Updated {$updated} contact messages with view_token.\n";
