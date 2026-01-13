<?php

namespace App\Observers;

use App\Models\AdminEpisodeLog;
use App\Services\AdminProgressService;

class AdminEpisodeLogObserver
{
    protected AdminProgressService $progressService;

    public function __construct(AdminProgressService $progressService)
    {
        $this->progressService = $progressService;
    }

    /**
     * Handle the AdminEpisodeLog "created" event.
     */
    public function created(AdminEpisodeLog $log): void
    {
        // Send activity update to Discord
        $this->progressService->sendActivityUpdate($log);
    }

    /**
     * Handle the AdminEpisodeLog "updated" event.
     */
    public function updated(AdminEpisodeLog $log): void
    {
        // Optional: send notification when status changes to approved/paid
        if ($log->isDirty('status')) {
            // Can add notification for status changes here if needed
        }
    }
}
