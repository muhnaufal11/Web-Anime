<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class VideoProxyController extends Controller
{
    /**
     * 1. Proxy Internal (AnimeSail)
     * Tetap gunakan Guzzle di sini karena koneksi ke IP 154... biasanya stabil.
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
                'timeout' => 5, // Timeout pendek agar tidak hang
            ])->get($upstreamUrl);

            return response($response->body(), $response->status())
                ->header('Content-Type', $response->header('Content-Type'))
                ->header('Access-Control-Allow-Origin', '*');

        } catch (\Exception $e) {
            return response("Internal Proxy Error: " . $e->getMessage(), 200);
        }
    }

    /**
     * 2. Proxy External (Aghanim/Acefile) - VERSI NATIVE PHP (ANTI-CRASH)
     * Menggunakan file_get_contents native untuk bypass Guzzle Crash (Error 502)
     */
    public function proxyExternal(Request $request)
    {
        $url = $request->input('url');

        // Validasi URL
        if (empty($url)) {
            return response("Error: URL Kosong", 200);
        }
        
        // Auto-Protocol: Tambahkan http:// jika tidak ada
        if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
            $url = "http://" . $url;
        }

        // Siapkan Headers Penyamaran (Spoofing)
        $parsed = parse_url($url);
        $host = $parsed['host'] ?? '';
        $fakeOrigin = "https://" . $host;

        // Konfigurasi Stream Context (Pengganti Guzzle)
        $options = [
            "http" => [
                "method" => "GET",
                "header" => implode("\r\n", [
                    "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) Chrome/120.0.0.0 Safari/537.36",
                    "Referer: $fakeOrigin/",
                    "Origin: $fakeOrigin",
                    "Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8",
                    "Connection: close" // PENTING: Tutup koneksi segera biar server tidak hang
                ]),
                "timeout" => 15,        // Beri waktu 15 detik
                "ignore_errors" => true // Supaya status 403/404/500 tidak dianggap error PHP
            ],
            "ssl" => [
                "verify_peer" => false,
                "verify_peer_name" => false,
            ]
        ];

        try {
            // Eksekusi Request secara Native (Lebih aman dari crash)
            $context = stream_context_create($options);
            $content = @file_get_contents($url, false, $context);

            // Cek jika gagal total (koneksi putus/dns error)
            if ($content === false) {
                $error = error_get_last();
                return response("Proxy Gagal (Native): " . ($error['message'] ?? 'Unknown Connection Error'), 200);
            }

            // Sukses! Return hasil apa adanya
            // Kita paksa status 200 agar Cloudflare tidak menampilkan halaman error 502
            return response($content, 200)
                ->header('Content-Type', 'text/html')
                ->header('Access-Control-Allow-Origin', '*')
                ->header('X-Frame-Options', 'ALLOWALL');

        } catch (\Throwable $e) {
            // Tangkap Fatal Error (Throwable) yang biasanya bikin 502
            Log::error("Proxy Native Crash: " . $e->getMessage());
            return response("System Error (Native): " . $e->getMessage(), 200);
        }
    }
}