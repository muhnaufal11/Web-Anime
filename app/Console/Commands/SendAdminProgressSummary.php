<?php

namespace App\Console\Commands;

use App\Services\AdminProgressService;
use Illuminate\Console\Command;

class SendAdminProgressSummary extends Command
{
    protected $signature = 'admin:progress-summary';
    protected $description = 'Send admin progress summary to Discord';

    public function handle(AdminProgressService $service): int
    {
        $this->info('Sending admin progress summary to Discord...');
        
        $service->sendProgressSummary();
        
        $this->info('Summary sent successfully!');
        
        return Command::SUCCESS;
    }
}
