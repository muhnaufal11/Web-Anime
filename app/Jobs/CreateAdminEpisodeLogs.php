<?php

namespace App\Jobs;

use App\Models\Episode;
use App\Models\User;
use App\Models\AdminEpisodeLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateAdminEpisodeLogs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $userId;

    public function __construct($userId = null)
    {
        $this->userId = $userId;
    }

    public function handle(): void
    {
        $query = User::admins();

        if ($this->userId) {
            $query->where('id', $this->userId);
        }

        $admins = $query->get();

        foreach ($admins as $admin) {
            // Get all episodes without logs for this admin
            $episodes = Episode::whereDoesntHave('adminEpisodeLogs', function ($q) use ($admin) {
                $q->where('user_id', $admin->id);
            })->get();

            foreach ($episodes as $episode) {
                AdminEpisodeLog::firstOrCreate(
                    [
                        'user_id' => $admin->id,
                        'episode_id' => $episode->id,
                    ],
                    [
                        'amount' => AdminEpisodeLog::DEFAULT_AMOUNT,
                        'status' => AdminEpisodeLog::STATUS_PENDING,
                    ]
                );
            }

            \Log::info("Created AdminEpisodeLogs for user {$admin->name} - {$episodes->count()} episodes");
        }
    }
}
