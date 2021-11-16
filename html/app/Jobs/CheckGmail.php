<?php

namespace App\Jobs;

use App\Models\GmailFilter;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckGmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $gmailFilter;

    /**
     * CheckGmail constructor.
     * @param GmailFilter $gmailFilter
     */
    public function __construct(GmailFilter $gmailFilter)
    {
        $this->gmailFilter = $gmailFilter;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        auth()->onceUsingId($this->gmailFilter->user_id);// @todo remove when gmail->saveAattacment auth issue is fixed
        $latestGmail = $this->gmailFilter->gmails()->orderBy('date', 'DESC')->first();
        $after = $latestGmail ? $latestGmail->date->format('Y/m/d') : null;
        GmailService::getMailByFilter($this->gmailFilter, null, $after);
    }
}
