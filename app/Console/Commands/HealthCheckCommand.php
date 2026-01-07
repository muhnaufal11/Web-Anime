<?php

namespace App\Console\Commands;

use App\Services\WebStatusService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HealthCheckCommand extends Command
{
    protected $signature = 'health:check';
    protected $description = 'Check website health and send Discord notification if down';

    protected string $webhookUrl = 'https://discordapp.com/api/webhooks/1458066647205281833/KxSc_6QX2PV4ACkgeISX-NMm-XGJsH_bUbZBssPYgeHn0CJYhLTvQ8YByDiC-WMAYnRV';

    protected array $endpoints = [
        'main' => 'https://nipnime.my.id',
        'api' => 'https://nipnime.my.id/api/health',
    ];

    public function handle(): int
    {
        $this->info('🔍 Checking website health...');

        $results = $this->checkEndpoints();
        $allHealthy = collect($results)->every(fn($r) => $r['healthy']);
        
        $previousStatus = Cache::get('health_check_status', 'unknown');
        $currentStatus = $allHealthy ? 'healthy' : 'unhealthy';

        // Store current status
        Cache::put('health_check_status', $currentStatus, now()->addHours(1));
        Cache::put('health_check_last_run', now()->toDateTimeString(), now()->addHours(1));
        Cache::put('health_check_results', $results, now()->addHours(1));

        // Track consecutive failures
        $consecutiveFailures = Cache::get('health_check_failures', 0);

        if (!$allHealthy) {
            $consecutiveFailures++;
            Cache::put('health_check_failures', $consecutiveFailures, now()->addHours(1));

            // Only alert after 2 consecutive failures (to avoid false positives)
            if ($consecutiveFailures >= 2 && $previousStatus !== 'unhealthy') {
                $this->sendDownAlert($results);
                $this->error('❌ Website is DOWN! Discord notification sent.');
                
                // Update web status
                app(WebStatusService::class)->setStatus(
                    WebStatusService::STATUS_DOWN,
                    'Auto-detected: Website tidak merespon'
                );
            } else {
                $this->warn('⚠️ Health check failed (' . $consecutiveFailures . '/2)');
            }
        } else {
            // Reset failure counter
            Cache::put('health_check_failures', 0, now()->addHours(1));

            // If was down but now recovered
            if ($previousStatus === 'unhealthy') {
                $downtime = $this->calculateDowntime();
                $this->sendRecoveryAlert($results, $downtime);
                $this->info('✅ Website RECOVERED! Discord notification sent.');
                
                // Update web status
                app(WebStatusService::class)->setStatus(
                    WebStatusService::STATUS_ONLINE,
                    'Auto-detected: Website kembali online'
                );
            } else {
                $this->info('✅ All endpoints healthy');
            }
        }

        // Log health check
        $this->logHealthCheck($results, $currentStatus);

        return $allHealthy ? self::SUCCESS : self::FAILURE;
    }

    protected function checkEndpoints(): array
    {
        $results = [];

        foreach ($this->endpoints as $name => $url) {
            $startTime = microtime(true);
            
            try {
                $response = Http::timeout(10)->get($url);
                $responseTime = round((microtime(true) - $startTime) * 1000);
                
                $results[$name] = [
                    'url' => $url,
                    'healthy' => $response->successful(),
                    'status_code' => $response->status(),
                    'response_time' => $responseTime,
                    'error' => null,
                ];
            } catch (\Exception $e) {
                $responseTime = round((microtime(true) - $startTime) * 1000);
                
                $results[$name] = [
                    'url' => $url,
                    'healthy' => false,
                    'status_code' => 0,
                    'response_time' => $responseTime,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    protected function sendDownAlert(array $results): void
    {
        $fields = [];

        foreach ($results as $name => $result) {
            $status = $result['healthy'] ? '✅ OK' : '❌ FAIL';
            $fields[] = [
                'name' => ucfirst($name),
                'value' => $status . ' (' . $result['response_time'] . 'ms)' . 
                          ($result['error'] ? "\n```" . substr($result['error'], 0, 100) . "```" : ''),
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '⏰ Detected At',
            'value' => now()->format('Y-m-d H:i:s'),
            'inline' => false,
        ];

        $embed = [
            'title' => '🔴 WEBSITE DOWN ALERT!',
            'description' => '**nipnime.my.id** tidak dapat diakses!\n\nSistem monitoring mendeteksi website tidak merespon.',
            'color' => 0xef4444,
            'fields' => $fields,
            'footer' => [
                'text' => 'NipNime Health Monitor',
                'icon_url' => 'https://nipnime.my.id/favicon.ico',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->sendToDiscord($embed);

        // Store downtime start
        Cache::put('health_check_down_since', now()->toDateTimeString(), now()->addDays(1));
    }

    protected function sendRecoveryAlert(array $results, string $downtime): void
    {
        $fields = [];

        foreach ($results as $name => $result) {
            $fields[] = [
                'name' => ucfirst($name),
                'value' => '✅ OK (' . $result['response_time'] . 'ms)',
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '⏱️ Total Downtime',
            'value' => $downtime,
            'inline' => true,
        ];

        $fields[] = [
            'name' => '⏰ Recovered At',
            'value' => now()->format('Y-m-d H:i:s'),
            'inline' => true,
        ];

        $embed = [
            'title' => '🟢 WEBSITE RECOVERED!',
            'description' => '**nipnime.my.id** kembali online!\n\nWebsite telah pulih dan dapat diakses kembali.',
            'color' => 0x22c55e,
            'fields' => $fields,
            'footer' => [
                'text' => 'NipNime Health Monitor',
                'icon_url' => 'https://nipnime.my.id/favicon.ico',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        $this->sendToDiscord($embed);

        // Clear downtime tracking
        Cache::forget('health_check_down_since');
    }

    protected function calculateDowntime(): string
    {
        $downSince = Cache::get('health_check_down_since');
        
        if (!$downSince) {
            return 'Unknown';
        }

        $downSinceTime = \Carbon\Carbon::parse($downSince);
        $diff = $downSinceTime->diff(now());

        if ($diff->h > 0) {
            return $diff->h . ' jam ' . $diff->i . ' menit';
        } elseif ($diff->i > 0) {
            return $diff->i . ' menit ' . $diff->s . ' detik';
        } else {
            return $diff->s . ' detik';
        }
    }

    protected function sendToDiscord(array $embed): void
    {
        try {
            Http::post($this->webhookUrl, [
                'embeds' => [$embed],
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to send Discord health alert: ' . $e->getMessage());
        }
    }

    protected function logHealthCheck(array $results, string $status): void
    {
        Log::channel('daily')->info('Health Check', [
            'status' => $status,
            'results' => $results,
        ]);
    }
}
