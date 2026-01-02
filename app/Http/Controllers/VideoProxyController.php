<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProxyController extends Controller
{
    /**
     * Proxy untuk AnimeSail (Internal IP) - Tetap sama
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
                'timeout' => 5,
            ])
            ->get($upstreamUrl);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return response("Proxy Error: " . $e->getMessage(), 502);
        }
    }

    /**
     * Proxy External (Aghanim, Acefile, dll)
     * FITUR BARU: Stealth Mode (Penyamaran Browser Lengkap)
     */
    public function proxyExternal(Request $request)
    {
        $url = $request->input('url');
        
        if (empty($url)) {
            return response('URL is empty', 400);
        }

        // 1. Pastikan Protokol Ada
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        // 2. Siapkan Penyamaran (Spoofing)
        $parsed = parse_url($url);
        $scheme = $parsed['scheme'] ?? 'http';
        $host   = $parsed['host'] ?? '';
        
        if (empty($host)) {
            return response("Invalid Host", 400);
        }

        // Trik: Gunakan HTTPS di Referer/Origin walau targetnya HTTP (supaya lebih dipercaya)
        $fakeOrigin = "https://" . $host; 

        try {
            // 3. Request dengan Header Browser Asli (Stealth Mode)
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 10,         
                'connect_timeout' => 5,
                'allow_redirects' => true,
                'cookies' => true, // Wajib: Aktifkan Cookies
            ])
            ->withHeaders([
                // Identitas Browser (Chrome Windows)
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                
                // Penyamaran Asal Request
                'Referer' => $fakeOrigin . '/',
                'Origin' => $fakeOrigin,
                
                // Header Standar Browser (Supaya tidak dikira bot)
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.9,id;q=0.8',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
                'Upgrade-Insecure-Requests' => '1',
                
                // Header Keamanan Fetch (Penting buat Cloudflare!)
                'Sec-Fetch-Dest' => 'iframe',
                'Sec-Fetch-Mode' => 'navigate',
                'Sec-Fetch-Site' => 'cross-site',
                'Sec-Fetch-User' => '?1',
                'Sec-Ch-Ua' => '"Not_A Brand";v="8", "Chromium";v="120", "Google Chrome";v="120"',
                'Sec-Ch-Ua-Mobile' => '?0',
                'Sec-Ch-Ua-Platform' => '"Windows"',
            ])
            ->get($url);

            // 4. Kirim Balik Hasil
            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type') ?? 'text/html')
                ->header('Access-Control-Allow-Origin', '*') 
                ->header('X-Frame-Options', 'ALLOWALL'); // Hapus larangan iframe

        } catch (\Exception $e) {
            Log::error("Proxy Fail [{$url}]: " . $e->getMessage());
            return response("Gagal memuat video (Blocked/Timeout).", 502);
        }
    }
}