<?php

namespace App\Services;

class VideoEmbedHelper
{
    /**
     * Logika Cerdas untuk Memilah URL Proxy vs URL Asli
     */
    public static function extractOriginalUrl(string $url): ?string
    {
        // 1. Cek apakah ini URL Proxy Internal (154.26...)
        if (stripos($url, '154.26.137.28') !== false || stripos($url, '/utils/player/') !== false) {
            
            // KASUS A: Tipe "Mega/Framezilla" (Punya parameter BSRC) -> WAJIB DECODE
            if (preg_match('/[?&]bsrc=([^&]+)/i', $url, $matches)) {
                $encoded = urldecode($matches[1]);
                $decoded = @base64_decode($encoded, true);
                
                // Validasi hasil decode
                if ($decoded && (strpos($decoded, 'http') === 0 || strpos($decoded, '//') === 0)) {
                    if (strpos($decoded, '//') === 0) return 'https:' . $decoded;
                    return $decoded;
                }
            }
            
            // KASUS B: Tipe "Pixel/Kamado/Pompom/Lokal" (Punya ID & TOKEN) -> BIARKAN APA ADANYA
            // Jangan di-decode! ID mereka bukan Base64 URL.
            if (strpos($url, 'token=') !== false || strpos($url, 'id=') !== false) {
                return $url; 
            }
        }
        
        return null;
    }
    
    public static function toEmbedCode(string $url, ?string $serverName = null): string
    {
        // Jika input adalah tag Iframe utuh
        if (stripos($url, '<iframe') === 0) {
            // REGEX BARU: Support kutip satu (') dan kutip dua (")
            if (preg_match('/src=["\']([^"\']+)["\']/i', $url, $matches)) {
                $srcUrl = html_entity_decode($matches[1]);
                $originalUrl = self::extractOriginalUrl($srcUrl);
                
                if ($originalUrl) {
                    return preg_replace('/src=["\'][^"\']+["\']/i', 'src="' . htmlspecialchars($originalUrl, ENT_QUOTES, 'UTF-8') . '"', $url);
                }
            }
            return $url;
        }

        $url = trim($url);
        $originalUrl = self::extractOriginalUrl($url);
        if ($originalUrl) {
            $url = $originalUrl;
        }

        // FORMAT KHUSUS: Aghanim / Lokal
        // Server ini butuh ID "picasa" agar jalan lancar
        if (stripos($url, 'aghanim.xyz') !== false) {
            return sprintf(
                '<iframe src="%s" id="picasa" frameborder="0" width="100%%" height="100%%" allowfullscreen="allowfullscreen" scrolling="no" allow="autoplay; fullscreen; encrypted-media" referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // FORMAT UMUM
        return sprintf(
            '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    public static function proxify(string $url): string
    {
        $value = trim($url);

        if (self::isEmbedCode($value)) {
            if (preg_match('/src=["\']([^"\']+)["\']/i', $value, $matches)) {
                $src = html_entity_decode($matches[1]);
                if (self::shouldProxyUrl($src)) {
                    $proxied = self::proxyUrl($src);
                    return preg_replace('/src=["\'][^"\']+["\']/i', 'src="' . htmlspecialchars($proxied, ENT_QUOTES, 'UTF-8') . '"', $value);
                }
            }
            return $value;
        }

        if (self::shouldProxyUrl($value)) {
            return self::proxyUrl($value);
        }

        return $value;
    }

    protected static function shouldProxyUrl(string $url): bool
    {
        if (empty($url)) return false;
        $targets = ['aghanim.xyz', '154.26.137.28', '/utils/player/'];
        foreach ($targets as $needle) {
            if (stripos($url, $needle) !== false) return true;
        }
        return false;
    }

    protected static function proxyUrl(string $url): string
    {
        // Kirim ke Proxy Controller (VideoProxyController)
        return route('video.proxy.external', ['url' => trim($url)]);
    }

    public static function isEmbedCode(string $url): bool
    {
        return stripos($url, '<iframe') === 0;
    }
}