<?php

namespace App\Services;

/**
 * Helper untuk convert URL server video menjadi iframe HTML yang bisa di-embed
 */
class VideoEmbedHelper
{
    /**
     * Extract original URL from AnimeSail proxy URL
     */
    public static function extractOriginalUrl(string $url): ?string
    {
        // Cek apakah ini URL proxy (154.26.137.28 atau /utils/player/)
        if (stripos($url, '154.26.137.28') !== false || stripos($url, '/utils/player/') !== false) {
            // Coba ambil parameter bsrc
            if (preg_match('/[?&]bsrc=([^&]+)/i', $url, $matches)) {
                $encoded = urldecode($matches[1]);
                $decoded = @base64_decode($encoded, true);
                if ($decoded) {
                    // [PENTING] Kembalikan ke HTTPS sebagai default!
                    // Server LOKAL/Acefile biasanya butuh HTTPS agar tidak dianggap "Invalid"
                    if (strpos($decoded, '//') === 0) {
                        return 'https:' . $decoded; 
                    }
                    if (preg_match('/^https?:/i', $decoded)) {
                        return $decoded;
                    }
                }
            }
            
            // Coba ambil parameter id (untuk gphoto)
            if (preg_match('/[?&]id=([^&]+)/i', $url, $matches)) {
                $encoded = urldecode($matches[1]);
                $decoded = @base64_decode($encoded, true);
                if ($decoded) {
                    return $decoded;
                }
            }
        }
        
        return null;
    }
    
    /**
     * Convert server URL to embeddable iframe HTML
     */
    public static function toEmbedCode(string $url, ?string $serverName = null): string
    {
        // Jika sudah berupa iframe HTML
        if (stripos($url, '<iframe') === 0) {
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
        
        // Coba extract url asli dulu
        $originalUrl = self::extractOriginalUrl($url);
        if ($originalUrl) {
            $url = $originalUrl;
        }

        // Aghanim.xyz (Lokal Player)
        if (stripos($url, 'aghanim.xyz') !== false && stripos($url, '/tools/lokal/') !== false) {
            return sprintf(
                '<iframe src="%s" id="picasa" frameborder="0" width="100%%" height="100%%" allowfullscreen="allowfullscreen" scrolling="no" allow="autoplay; fullscreen; encrypted-media" referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // Server Umum (Direct Embed)
        if (stripos($url, 'mp4upload.com') !== false) {
            return sprintf(
                '<iframe src="%s" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if (stripos($url, 'mixdrop.') !== false) {
            return sprintf(
                '<iframe src="%s" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if (stripos($url, 'krakenfiles.com') !== false) {
            return sprintf(
                '<iframe frameborder="0" src="%s" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        if (stripos($url, 'acefile.co') !== false) {
            return sprintf(
                '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
                htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
            );
        }

        // Default Wrapper
        return sprintf(
            '<iframe src="%s" scrolling="no" frameborder="0" width="100%%" height="100%%" allow="autoplay; fullscreen; encrypted-media" allowfullscreen referrerpolicy="no-referrer"></iframe>',
            htmlspecialchars($url, ENT_QUOTES, 'UTF-8')
        );
    }

    /**
     * Tentukan apakah perlu diproxy
     */
    public static function proxify(string $url): string
    {
        $value = trim($url);

        if (self::isEmbedCode($value)) {
            if (preg_match('/src=["\']([^"\']+)["\']/i', $value, $matches)) {
                $src = html_entity_decode($matches[1]);
                if (self::shouldProxyUrl($src)) {
                    $proxied = self::proxyUrl($src);
                    return preg_replace(
                        '/src=["\'][^"\']+["\']/i',
                        'src="' . htmlspecialchars($proxied, ENT_QUOTES, 'UTF-8') . '"',
                        $value
                    );
                }
            }
            return $value;
        }

        if (self::shouldProxyUrl($value)) {
            return self::proxyUrl($value);
        }

        return $value;
    }

    /**
     * List domain yang wajib lewat proxy
     */
    protected static function shouldProxyUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $targets = [
            'aghanim.xyz',
            '154.26.137.28',
            '/utils/player/',
        ];

        foreach ($targets as $needle) {
            if (stripos($url, $needle) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Buat URL Proxy
     */
    protected static function proxyUrl(string $url): string
    {
        // FIX: Jangan paksa HTTPS disini, biarkan apa adanya.
        // Tapi kita hapus rawurlencode() ganda agar Controller tidak bingung membacanya.
        // Laravel route() sudah otomatis meng-encode parameter.
        return route('video.proxy.external', ['url' => trim($url)]);
    }

    public static function isEmbedCode(string $url): bool
    {
        return stripos($url, '<iframe') === 0;
    }
}