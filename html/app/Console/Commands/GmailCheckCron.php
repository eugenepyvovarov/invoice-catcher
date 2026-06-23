<?php

namespace App\Console\Commands;

use App\Jobs\CheckGmail;
use App\Models\GmailFilter;
use Illuminate\Console\Command;

class GmailCheckCron extends Command
{
    protected $signature = 'gmail:check';

    protected $description = 'Dispatch Gmail check jobs for all filters';

    public function handle(): int
    {
        GmailFilter::chunk(100, function ($filters) {
            foreach ($filters as $filter) {
                CheckGmail::dispatchSync($filter); // CLI/cron: run inline, not afterResponse
            }
        });

        $this->info('Gmail check jobs dispatched.');

        return self::SUCCESS;
    }
}
