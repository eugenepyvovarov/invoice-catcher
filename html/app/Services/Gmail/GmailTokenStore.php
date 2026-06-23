<?php

namespace App\Services\Gmail;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

class GmailTokenStore
{
    public function pathForUser(int|string $userId): string
    {
        $prefix = config('gmail.credentials_file_name', 'gmail-json');

        return "gmail/tokens/{$prefix}-{$userId}.json";
    }

    public function exists(int|string $userId): bool
    {
        return Storage::disk('local')->exists($this->pathForUser($userId));
    }

    public function get(int|string $userId): ?array
    {
        $path = $this->pathForUser($userId);
        if (! Storage::disk('local')->exists($path)) {
            return null;
        }

        $raw = Storage::disk('local')->get($path);

        if (config('gmail.allow_json_encrypt')) {
            try {
                $raw = Crypt::decryptString($raw);
            } catch (\Throwable) {
                // fall through and try as plain JSON (legacy/unencrypted)
            }
        }

        $token = json_decode($raw, true);

        return is_array($token) ? $token : null;
    }

    public function put(int|string $userId, array $token): void
    {
        $json = json_encode($token);
        if (config('gmail.allow_json_encrypt')) {
            $json = Crypt::encryptString($json);
        }

        Storage::disk('local')->put($this->pathForUser($userId), $json);
    }

    public function delete(int|string $userId): void
    {
        $path = $this->pathForUser($userId);
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    public function move(int|string $fromUserId, int|string $toUserId): void
    {
        $from = $this->pathForUser($fromUserId);
        $to = $this->pathForUser($toUserId);

        if (! Storage::disk('local')->exists($from)) {
            return;
        }

        Storage::disk('local')->delete($to);
        Storage::disk('local')->move($from, $to);
    }
}
