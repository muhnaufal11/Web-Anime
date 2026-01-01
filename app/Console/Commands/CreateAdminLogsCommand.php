<?php

namespace App\Console\Commands;

use App\Jobs\CreateAdminEpisodeLogs;
use Illuminate\Console\Command;

class CreateAdminLogsCommand extends Command
{
    protected $signature = 'admin:create-logs {--user= : Specific user ID (optional)}';

    protected $description = 'Create admin episode logs for all admins or specific user';

    public function handle()
    {
        $userId = $this->option('user');

        $this->info('🔄 Creating admin episode logs...');

        CreateAdminEpisodeLogs::dispatch($userId);

        $this->info('✅ Job dispatched! Logs will be created in background.');
    }
}
