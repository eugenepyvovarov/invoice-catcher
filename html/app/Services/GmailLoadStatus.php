<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class GmailLoadStatus
{
    public const STATUS_IDLE = 'idle';

    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_DONE = 'done';

    public const STATUS_FAILED = 'failed';

    public static function key(int $filterId): string
    {
        return 'gmail_load_status:'.$filterId;
    }

    public static function get(int $filterId): array
    {
        return Cache::get(self::key($filterId), [
            'status' => self::STATUS_IDLE,
            'filter_id' => $filterId,
            'message' => null,
            'saved_count' => 0,
            'listed_count' => 0,
            'started_at' => null,
            'finished_at' => null,
            'error' => null,
        ]);
    }

    public static function put(int $filterId, array $data): array
    {
        $payload = array_merge(self::get($filterId), $data, ['filter_id' => $filterId]);
        // Keep done/failed briefly so UI can show success; running longer.
        $ttl = in_array($payload['status'], [self::STATUS_DONE, self::STATUS_FAILED], true) ? 300 : 3600;
        Cache::put(self::key($filterId), $payload, $ttl);

        return $payload;
    }

    public static function markQueued(int $filterId, ?string $message = null): array
    {
        return self::put($filterId, [
            'status' => self::STATUS_QUEUED,
            'message' => $message ?? 'Queued — waiting to start…',
            'saved_count' => 0,
            'listed_count' => 0,
            'started_at' => now()->toIso8601String(),
            'finished_at' => null,
            'error' => null,
        ]);
    }

    public static function markRunning(int $filterId, ?string $message = null): array
    {
        return self::put($filterId, [
            'status' => self::STATUS_RUNNING,
            'message' => $message ?? 'Fetching from Gmail…',
            'started_at' => self::get($filterId)['started_at'] ?? now()->toIso8601String(),
            'finished_at' => null,
            'error' => null,
        ]);
    }

    public static function progress(int $filterId, int $listedCount, int $savedCount, ?string $message = null): array
    {
        return self::put($filterId, [
            'status' => self::STATUS_RUNNING,
            'listed_count' => $listedCount,
            'saved_count' => $savedCount,
            'message' => $message ?? "Processing… listed {$listedCount}, saved {$savedCount} new mail(s)",
        ]);
    }

    public static function markDone(int $filterId, int $listedCount, int $savedCount): array
    {
        return self::put($filterId, [
            'status' => self::STATUS_DONE,
            'listed_count' => $listedCount,
            'saved_count' => $savedCount,
            'message' => $savedCount > 0
                ? "Done. Saved {$savedCount} new mail(s) (scanned {$listedCount})."
                : "Done. No new mails (scanned {$listedCount}).",
            'finished_at' => now()->toIso8601String(),
            'error' => null,
        ]);
    }

    public static function markFailed(int $filterId, string $error): array
    {
        return self::put($filterId, [
            'status' => self::STATUS_FAILED,
            'message' => 'Load failed.',
            'error' => $error,
            'finished_at' => now()->toIso8601String(),
        ]);
    }

    public static function isActive(?array $status): bool
    {
        if (! $status) {
            return false;
        }

        return in_array($status['status'] ?? '', [self::STATUS_QUEUED, self::STATUS_RUNNING], true);
    }
}
