<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Artisan;

class WebStatusService
{
    protected string $webhookUrl = 'https://discordapp.com/api/webhooks/1458066647205281833/KxSc_6QX2PV4ACkgeISX-NMm-XGJsH_bUbZBssPYgeHn0CJYhLTvQ8YByDiC-WMAYnRV';

    const STATUS_ONLINE = 'online';
    const STATUS_MAINTENANCE = 'maintenance';
    const STATUS_DOWN = 'down';
    const STATUS_DEGRADED = 'degraded';
    const STATUS_UPDATE = 'update';

    // Cache key for storing Discord message ID
    const CACHE_MESSAGE_ID = 'discord_status_message_id';

    protected array $statusConfig = [
        self::STATUS_ONLINE => [
            'emoji' => '🟢',
            'label' => 'Online',
            'color' => 0x22c55e, // green
            'description' => 'Website berjalan normal',
        ],
        self::STATUS_MAINTENANCE => [
            'emoji' => '🟡',
            'label' => 'Maintenance',
            'color' => 0xeab308, // yellow
            'description' => 'Website sedang dalam maintenance',
        ],
        self::STATUS_DOWN => [
            'emoji' => '🔴',
            'label' => 'Down',
            'color' => 0xef4444, // red
            'description' => 'Website tidak dapat diakses',
        ],
        self::STATUS_DEGRADED => [
            'emoji' => '🟠',
            'label' => 'Degraded',
            'color' => 0xf97316, // orange
            'description' => 'Website mengalami gangguan',
        ],
        self::STATUS_UPDATE => [
            'emoji' => '🔵',
            'label' => 'Update',
            'color' => 0x3b82f6, // blue
            'description' => 'Website sedang diupdate',
        ],
    ];

    public function getStatuses(): array
    {
        return $this->statusConfig;
    }

    public function getCurrentStatus(): array
    {
        $status = Cache::get('web_status', self::STATUS_ONLINE);
        $message = Cache::get('web_status_message', '');
        $updatedAt = Cache::get('web_status_updated_at', now()->toDateTimeString());
        $updatedBy = Cache::get('web_status_updated_by', 'System');

        return [
            'status' => $status,
            'config' => $this->statusConfig[$status] ?? $this->statusConfig[self::STATUS_ONLINE],
            'message' => $message,
            'updated_at' => $updatedAt,
            'updated_by' => $updatedBy,
        ];
    }

    public function setStatus(string $status, string $message = '', ?string $estimatedTime = null): bool
    {
        if (!isset($this->statusConfig[$status])) {
            return false;
        }

        $previousStatus = Cache::get('web_status', self::STATUS_ONLINE);
        $user = auth()->user();

        // Save to cache
        Cache::forever('web_status', $status);
        Cache::forever('web_status_message', $message);
        Cache::forever('web_status_updated_at', now()->toDateTimeString());
        Cache::forever('web_status_updated_by', $user?->name ?? 'System');
        
        if ($estimatedTime) {
            Cache::forever('web_status_eta', $estimatedTime);
        } else {
            Cache::forget('web_status_eta');
        }

        // Handle maintenance mode
        if ($status === self::STATUS_MAINTENANCE) {
            $this->enableMaintenanceMode($message);
        } elseif ($previousStatus === self::STATUS_MAINTENANCE && $status !== self::STATUS_MAINTENANCE) {
            $this->disableMaintenanceMode();
        }

        // Send Discord notification
        $this->sendStatusUpdate($status, $message, $previousStatus, $estimatedTime);

        return true;
    }

    protected function enableMaintenanceMode(string $message = ''): void
    {
        // Generate bypass secret
        $bypassSecret = 'bypass-' . bin2hex(random_bytes(8));
        Cache::forever('maintenance_bypass_secret', $bypassSecret);
        
        // Don't use Laravel's built-in maintenance mode, we handle it with middleware
        \Log::info('Maintenance mode enabled with bypass: ' . $bypassSecret);
    }

    protected function disableMaintenanceMode(): void
    {
        Cache::forget('maintenance_bypass_secret');
        \Log::info('Maintenance mode disabled');
    }

    public function getBypassUrl(): string
    {
        $secret = Cache::get('maintenance_bypass_secret');
        if ($secret) {
            return 'https://nipnime.my.id/' . $secret;
        }
        return '';
    }

    public function sendStatusUpdate(string $status, string $message = '', ?string $previousStatus = null, ?string $estimatedTime = null): void
    {
        $config = $this->statusConfig[$status] ?? $this->statusConfig[self::STATUS_ONLINE];
        $user = auth()->user();

        $fields = [
            [
                'name' => '📊 Status',
                'value' => $config['emoji'] . ' **' . $config['label'] . '**',
                'inline' => true,
            ],
            [
                'name' => '🌐 Website',
                'value' => '[nipnime.my.id](https://nipnime.my.id)',
                'inline' => true,
            ],
        ];

        if ($previousStatus && $previousStatus !== $status) {
            $prevConfig = $this->statusConfig[$previousStatus] ?? $this->statusConfig[self::STATUS_ONLINE];
            $fields[] = [
                'name' => '📝 Perubahan',
                'value' => $prevConfig['emoji'] . ' ' . $prevConfig['label'] . ' → ' . $config['emoji'] . ' ' . $config['label'],
                'inline' => false,
            ];
        }

        if ($message) {
            $fields[] = [
                'name' => '💬 Keterangan',
                'value' => $message,
                'inline' => false,
            ];
        }

        if ($estimatedTime) {
            $fields[] = [
                'name' => '⏱️ Estimasi Selesai',
                'value' => $estimatedTime,
                'inline' => true,
            ];
        }

        $fields[] = [
            'name' => '👤 Diupdate oleh',
            'value' => $user?->name ?? 'System',
            'inline' => true,
        ];

        $embed = [
            'title' => $config['emoji'] . ' Web Status: ' . $config['label'],
            'description' => $config['description'],
            'color' => $config['color'],
            'fields' => $fields,
            'footer' => [
                'text' => 'NipNime Status System • Last updated',
                'icon_url' => 'https://nipnime.my.id/favicon.ico',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            // Check if we have an existing message to edit
            $existingMessageId = Cache::get(self::CACHE_MESSAGE_ID);
            
            if ($existingMessageId) {
                // Try to edit existing message
                $editUrl = $this->webhookUrl . '/messages/' . $existingMessageId;
                $response = Http::patch($editUrl, [
                    'embeds' => [$embed],
                ]);
                
                if ($response->successful()) {
                    \Log::info('Discord status message edited: ' . $existingMessageId);
                    return;
                }
                
                // If edit failed, send new message
                \Log::warning('Failed to edit Discord message, sending new one');
            }
            
            // Send new message and store its ID
            $response = Http::post($this->webhookUrl . '?wait=true', [
                'embeds' => [$embed],
            ]);
            
            if ($response->successful()) {
                $data = $response->json();
                if (isset($data['id'])) {
                    Cache::forever(self::CACHE_MESSAGE_ID, $data['id']);
                    \Log::info('New Discord status message sent: ' . $data['id']);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Failed to send Discord status update: ' . $e->getMessage());
        }
    }

    public function sendCustomAnnouncement(string $title, string $message, string $type = 'info'): void
    {
        $colors = [
            'info' => 0x3b82f6,
            'success' => 0x22c55e,
            'warning' => 0xeab308,
            'error' => 0xef4444,
        ];

        $emojis = [
            'info' => 'ℹ️',
            'success' => '✅',
            'warning' => '⚠️',
            'error' => '❌',
        ];

        $user = auth()->user();

        $embed = [
            'title' => ($emojis[$type] ?? 'ℹ️') . ' ' . $title,
            'description' => $message,
            'color' => $colors[$type] ?? 0x3b82f6,
            'fields' => [
                [
                    'name' => '🌐 Website',
                    'value' => '[nipnime.my.id](https://nipnime.my.id)',
                    'inline' => true,
                ],
                [
                    'name' => '👤 Diumumkan oleh',
                    'value' => $user?->name ?? 'System',
                    'inline' => true,
                ],
            ],
            'footer' => [
                'text' => 'NipNime Announcement',
                'icon_url' => 'https://nipnime.my.id/favicon.ico',
            ],
            'timestamp' => now()->toIso8601String(),
        ];

        try {
            Http::post($this->webhookUrl, [
                'embeds' => [$embed],
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to send Discord announcement: ' . $e->getMessage());
        }
    }
}
