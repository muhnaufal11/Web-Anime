<?php

namespace App\Http\Controllers;

use App\Models\VideoServer;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Http\Request;

class PlayerProxyController extends Controller
{
    public function show(string $token)
    {
        try {
            $serverId = Crypt::decryptString($token);
            $server = VideoServer::findOrFail($serverId);
            $embed = $server->embed_url;
            $type = $this->getType($embed);

            return response()->view('player-proxy', [
                'embed' => $embed,
                'type' => $type,
            ])->header('X-Frame-Options', 'ALLOWALL');
        } catch (\Throwable $e) {
            abort(404);
        }
    }

    private function getType(string $embed): string
    {
        if (str_contains($embed, '<iframe')) {
            return 'html';
        }
        $lower = strtolower($embed);
        if (str_ends_with($lower, '.m3u8')) return 'm3u8';
        if (str_ends_with($lower, '.mp4')) return 'mp4';
        return 'url';
    }
}
