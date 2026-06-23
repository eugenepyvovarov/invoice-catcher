<?php

namespace App\Jobs;

use App\Models\GmailFilter;
use App\Services\GmailLoadStatus;
use App\Services\GmailService;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

/**
 * Dispatched after the HTTP response so the browser is not blocked during long Gmail imports.
 */
class CheckGmail
{
    use Dispatchable;

    public function __construct(public GmailFilter $gmailFilter) {}

    public function handle(): void
    {
        @set_time_limit(0);
        @ini_set('max_execution_time', '0');

        $filterId = $this->gmailFilter->id;
        GmailLoadStatus::markRunning($filterId, 'Connecting to Gmail…');

        try {
            auth()->onceUsingId($this->gmailFilter->user_id);
            $latestGmail = $this->gmailFilter->gmails()->orderBy('date', 'DESC')->first();
            $after = $latestGmail && $latestGmail->date
                ? $latestGmail->date->format('Y/m/d')
                : null;

            $stats = GmailService::getMailByFilter($this->gmailFilter, null, $after, $filterId);
            GmailLoadStatus::markDone(
                $filterId,
                $stats['listed_count'] ?? 0,
                $stats['saved_count'] ?? 0
            );
        } catch (\Throwable $e) {
            Log::error('CheckGmail failed', [
                'filter_id' => $filterId,
                'error' => $e->getMessage(),
            ]);
            GmailLoadStatus::markFailed($filterId, $e->getMessage());
        }
    }
}
