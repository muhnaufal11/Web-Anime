<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$servers = \App\Models\VideoServer::with('episode')->get();
echo "=== Video Servers ===\n";
foreach($servers as $s) {
    echo "ID: {$s->id} | Server: {$s->server_name} | Episode: {$s->episode->title} | Active: {$s->is_active}\n";
    echo "URL: {$s->embed_url}\n";
    echo "---\n";
}
?>
