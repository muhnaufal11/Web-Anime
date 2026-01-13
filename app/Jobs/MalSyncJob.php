<?php

namespace App\Jobs;

use App\Services\MyAnimeListService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;

class MalSyncJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 900;

    private array $payload;
    private int $userId;

    public function __construct(int $userId, array $payload)
    {
        $this->userId = $userId;
        $this->payload = $payload;
    }

    public function handle(): void
    {
        $key = $this->cacheKey();
        $this->writeState($key, 0, 'running', [$this->logEntry('🚀 Starting sync process...')]);

        try {
            $limit = !empty($this->payload['limit']) ? (int) $this->payload['limit'] : null;
            $this->append($key, '📋 Type: ' . $this->payload['syncType']);
            if ($limit) {
                $this->append($key, '🔢 Limit: ' . $limit);
            } else {
                $this->append($key, '🔢 Fetching all available anime...');
            }
            if (!$this->payload['downloadImages']) {
                $this->append($key, '⚡ Skip downloading images (faster)');
            } else {
                $this->append($key, '🖼️ Will download poster images');
            }
            if ($this->payload['syncType'] === 'search' && $this->payload['searchQuery']) {
                $this->append($key, '🔍 Searching: ' . $this->payload['searchQuery']);
            }
            if ($this->payload['syncType'] === 'mal_id' && !empty($this->payload['malId'])) {
                $this->append($key, '🔢 MAL ID: ' . $this->payload['malId']);
            }
            if ($this->payload['syncType'] === 'seasonal') {
                if ($this->payload['season'] === 'all') {
                    $this->append($key, '📅 Season: All Seasons');
                } elseif ($this->payload['season']) {
                    $this->append($key, '📅 Season: ' . $this->payload['season']);
                }
                if ($this->payload['year']) {
                    $this->append($key, '📆 Year: ' . $this->payload['year']);
                }
            }

            $this->writeProgress($key, 25, '⏳ Connecting to MyAnimeList API...');
            $this->writeProgress($key, 50, '📡 Fetching anime data...');

            /** @var MyAnimeListService $malService */
            $malService = app(MyAnimeListService::class);
            $syncType = $this->payload['syncType'];

            if ($syncType === 'top') {
                $animeList = $malService->fetchTopAnime($limit);
            } elseif ($syncType === 'seasonal') {
                $season = ($this->payload['season'] === 'all') ? null : $this->payload['season'];
                $animeList = $malService->fetchSeasonalAnime(
                    $this->payload['year'],
                    $season,
                    $limit
                );
            } elseif ($syncType === 'mal_id') {
                // Fetch single anime by MAL ID
                $malId = (int) $this->payload['malId'];
                $animeData = $malService->getAnimeDetails($malId);
                if ($animeData) {
                    $animeList = [$animeData];
                } else {
                    throw new \Exception("Anime dengan MAL ID {$malId} tidak ditemukan.");
                }
            } else {
                $animeList = $malService->searchAnime($this->payload['searchQuery'], $limit);
            }

            $this->writeProgress($key, 75, '💾 Saving to database...');
            $result = $this->processListWithRetry($key, $animeList, $this->payload['downloadImages']);

            $this->writeState($key, 100, 'done');
        } catch (\Throwable $e) {
            $this->append($key, '❌ Error: ' . $e->getMessage());
            $this->writeState($key, 0, 'error', [], $e->getMessage());
            throw $e;
        }
    }

    private function cacheKey(): string
    {
        return 'mal_sync:' . $this->userId;
    }

    private function writeState(string $key, int $progress, string $status, array $logs = [], ?string $error = null): void
    {
        $existing = Cache::get($key, ['logs' => []]);
        if (empty($logs)) {
            $logs = $existing['logs'] ?? [];
        }

        Cache::put($key, [
            'progress' => $progress,
            'status' => $status,
            'logs' => $logs,
            'error' => $error,
        ], now()->addMinutes(30));
    }

    private function writeProgress(string $key, int $progress, string $message): void
    {
        $state = Cache::get($key, ['logs' => [], 'status' => 'running', 'progress' => 0]);
        $state['progress'] = $progress;
        $state['logs'][] = $this->logEntry($message);
        Cache::put($key, $state, now()->addMinutes(30));
    }

    private function append(string $key, string $message): void
    {
        $state = Cache::get($key, ['logs' => [], 'status' => 'running', 'progress' => 0]);
        $state['logs'][] = $this->logEntry($message);
        Cache::put($key, $state, now()->addMinutes(30));
    }

    private function processListWithRetry(string $key, array $animeList, bool $downloadImages): array
    {
        $synced = [];
        $failed = [];
        $total = count($animeList);

        if ($total === 0) {
            $this->append($key, '⚠️ Tidak ada data dari API untuk parameter yang dipilih. Coba ganti season/tahun atau turunkan limit.');
            $this->writeProgress($key, 100, '✅ Sync completed (no data)');
            return [
                'synced' => [],
                'failed' => [],
                'total' => 0,
                'success_count' => 0,
                'failed_count' => 0,
            ];
        }

        foreach ($animeList as $index => $malData) {
            $maxRetries = 3;
            $attempt = 0;
            $success = false;

            while ($attempt < $maxRetries && !$success) {
                $attempt++;
                try {
                    $anime = app(MyAnimeListService::class)->syncAnime($malData, $downloadImages);
                    $synced[] = [
                        'id' => $anime->id,
                        'title' => $anime->title,
                        'status' => 'success',
                    ];
                    $success = true;
                } catch (\Exception $e) {
                    if ($attempt < $maxRetries) {
                        $this->append($key, '🔁 Retry (' . $attempt . '/' . $maxRetries . '): ' . ($malData['title'] ?? 'Unknown'));
                        usleep(500000); // Wait 500ms before retry
                    } else {
                        $failed[] = [
                            'title' => $malData['title'] ?? 'Unknown',
                            'error' => $e->getMessage(),
                        ];
                    }
                }
            }

            $progress = 55 + intval((($index + 1) / $total) * 40);
            $this->writeProgress($key, min($progress, 95), '🔄 Syncing anime... (' . ($index + 1) . '/' . $total . ')');

            // rate limit between items
            if ($index < $total - 1) {
                usleep(350000);
            }
        }

        // Finalize logs
        $this->append($key, '');
        $this->append($key, "📊 Total: {$total} | Success: " . count($synced) . " | Failed: " . count($failed));

        if (!empty($synced)) {
            $this->append($key, '🎬 Anime berhasil ditambahkan:');
            foreach ($synced as $item) {
                $this->append($key, '   ✓ ' . $item['title']);
            }
        }

        if (!empty($failed)) {
            $this->append($key, '❌ Gagal menambahkan:');
            foreach ($failed as $fail) {
                $this->append($key, '   ✗ ' . $fail['title'] . ': ' . $fail['error']);
            }
        }

        $this->writeProgress($key, 100, '✅ Sync completed successfully!');

        return [
            'synced' => $synced,
            'failed' => $failed,
            'total' => $total,
            'success_count' => count($synced),
            'failed_count' => count($failed),
        ];
    }

    private function logEntry(string $message): array
    {
        return [
            'time' => now()->format('H:i:s'),
            'message' => $message,
        ];
    }
}
