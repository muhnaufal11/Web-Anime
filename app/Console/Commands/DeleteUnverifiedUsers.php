<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use Illuminate\Support\Carbon;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users who have not verified their email after 24 hours of registration.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $count = User::whereNull('email_verified_at')
            ->where('created_at', '<', now()->subDay())
            ->delete();
        $this->info("Deleted $count unverified users.");
    }
}
