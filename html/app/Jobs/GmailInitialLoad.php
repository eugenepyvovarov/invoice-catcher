<?php

namespace App\Jobs;

use App\Models\GmailFilter;
use App\Services\GmailService;
use Illuminate\Foundation\Bus\Dispatchable;

class GmailInitialLoad
{
    use Dispatchable;

    public function __construct(public GmailFilter $gmailFilter) {}

    public function handle(): void
    {
        auth()->onceUsingId($this->gmailFilter->user_id);

        $latestGmail = $this->gmailFilter->gmails()->orderBy('date')->first();
        $before = $latestGmail && $latestGmail->date
            ? $latestGmail->date->copy()->addDay()->format('Y/m/d')
            : null;
        GmailService::getMailByFilter($this->gmailFilter, $before);
    }
}
