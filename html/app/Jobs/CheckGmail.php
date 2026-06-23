<?php

namespace App\Jobs;

use App\Models\GmailFilter;
use App\Services\GmailService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CheckGmail implements ShouldQueue
{
    use Queueable;

    public function __construct(public GmailFilter $gmailFilter) {}

    public function handle(): void
    {
        auth()->onceUsingId($this->gmailFilter->user_id);
        $latestGmail = $this->gmailFilter->gmails()->orderBy('date', 'DESC')->first();
        $after = $latestGmail ? $latestGmail->date->format('Y/m/d') : null;
        GmailService::getMailByFilter($this->gmailFilter, null, $after);
    }
}
