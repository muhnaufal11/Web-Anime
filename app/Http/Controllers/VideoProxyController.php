<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProxyController extends Controller
{
    /**
     * Proxy request ke AnimeSail player internal
     */
    public function proxyAnimeSail(Request $request)
    {
        $playerType = $request->route('playerType');
        $query = $request->getQueryString();

        if (empty($playerType) || empty($query)) {
            return response('Invalid request', 400);
        }

        $upstreamUrl = sprintf(
            'https://154.26.137.28/utils/player/%s/?%s',
            urlencode($playerType),
            $query
        );

        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 15,
                'allow_redirects' => true,
            ])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => 'https://acefile.co/', // Pura-pura datang dari Acefile
                'Origin' => 'https://acefile.co'
            ])
            ->get($upstreamUrl);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*')
                ->header('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');

        } catch (\Exception $e) {
            Log::error("Video proxy error for {$playerType}: " . $e->getMessage());
            return response('Upstream server error', 502);
        }
    }

    /**
     * Proxy untuk Aghanim, Acefile, dll (External)
     * FITUR BARU: Auto-Referer Spoofing untuk bypass "Invalid Domain"
     */
    public function proxyExternal(Request $request)
    {
        $url = $request->input('url');
        
        if (empty($url)) {
            return response('URL is empty', 400);
        }

        // 1. Pastikan Protokol Ada (Default HTTP agar kompatibel server lama)
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        // 2. SIAPKAN PENYAMARAN (REFERER SPOOFING)
        // Kita ambil domain dari target URL (misal: aghanim.xyz)
        // Lalu kita pasang sebagai Referer. Jadi server mengira kita browsing dari dalam websitenya sendiri.
        $parsedUrl = parse_url($url);
        $scheme = $parsedUrl['scheme'] ?? 'http';
        $host = $parsedUrl['host'] ?? '';
        $fakeOrigin = "$scheme://$host"; // Contoh: http://aghanim.xyz

        try {
            $response = Http::withOptions([
                'verify' => false, // Bypass SSL Error
                'timeout' => 20,
                'allow_redirects' => true
            ])
            ->withHeaders([
                // Header Penyamaran Wajib:
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Referer' => $fakeOrigin . '/', // "Saya datang dari website kamu lho"
                'Origin' => $fakeOrigin,
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9',
                'Sec-Fetch-Dest' => 'iframe',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'cross-site',
            ])
            ->get($url);

            // 3. Kirim balik hasilnya ke user
            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type') ?? 'text/html')
                ->header('Access-Control-Allow-Origin', '*') // Izinkan browser user memuat ini
                ->header('X-Frame-Options', 'ALLOWALL'); // Hapus larangan iframe

        } catch (\Exception $e) {
            Log::error("Video proxy external error for {$url}: " . $e->getMessage());
            return response("Proxy Error: " . $e->getMessage(), 502);
        }
    }
}