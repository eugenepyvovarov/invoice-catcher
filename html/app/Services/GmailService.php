<?php

namespace App\Services;

use App\Models\Gmail;
use App\Models\GmailFilter;
use App\Models\User;
use App\Services\Gmail\GmailApiService;
use App\Services\Gmail\GmailMessage;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class GmailService
{
    const MAX_PAGES_TO_LOAD = 1000;

    public static function api(): GmailApiService
    {
        return app(GmailApiService::class);
    }

    public static function authCheck(User $user): bool
    {
        return static::api()->isAuthenticated($user->id);
    }

    public static function getAuthUrl(): string
    {
        return static::api()->getAuthUrl();
    }

    public static function handleOAuthCallback(string $code): array
    {
        return static::api()->exchangeCode($code);
    }

    public static function storeToken(int|string $userId, array $token): void
    {
        static::api()->storeToken($userId, $token);
    }

    public static function moveToken(int|string $fromUserId, int|string $toUserId): void
    {
        static::api()->moveToken($fromUserId, $toUserId);
    }

    public static function disconnect(User $user): void
    {
        static::api()->revoke($user->id);
    }

    public static function saveData(GmailMessage $mail, GmailFilter $filter): void
    {
        if ($filter->regex && (! static::checkRegex($mail->getSubject(), $filter->regex) && ! static::checkRegex($mail->getHtmlBody(), $filter->regex))) {
            return;
        }

        $gmail = $filter->gmails()->firstOrCreate([
            'gmail_filter_id' => $filter->id,
            'mail_id' => $mail->getId(),
            'user_id' => $filter->user_id,
        ]);

        if ($gmail->wasRecentlyCreated) {
            $htmlBody = $mail->getHtmlBody();

            $gmail->fill([
                'internal_date' => $mail->getInternalDate(),
                'date' => $mail->getDate(),
                'fwd_date' => self::parseFwdDate($htmlBody),
                'labels' => $mail->getLabels(),
                'subject' => $mail->getSubject(),
                'from_name' => $mail->getFromName(),
                'from_email' => $mail->getFromEmail(),
                'to' => $mail->getTo(),
                'delivered_to' => $mail->getDeliveredTo(),
                'html_body' => $htmlBody,
                'attachments' => $mail->hasAttachments() ? static::downloadAttachments($mail, $gmail) : null,
            ]);
            $gmail->save();

            if (! $gmail->attachments) {
                static::saveBodyToPdfFile($gmail);
            }
        }
    }

    public static function downloadAttachments(GmailMessage $mail, Gmail $gmail): array
    {
        $localId = 0;
        $result = [];
        $allowed = config('gmail.allowed_extensions', ['pdf']);

        foreach ($mail->getAttachments() as $attachment) {
            $fileName = $attachment['filename'] ?? 'attachment';
            $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

            if (! in_array($extension, $allowed, true)) {
                continue;
            }

            $data = $attachment['data'] ?? '';
            if ($data === '') {
                continue;
            }

            $localId++;
            $attachmentPath = static::makeAttachmentPath($gmail, $localId);
            $fullPath = $attachmentPath.'/'.$fileName;

            Storage::disk('local')->put($fullPath, $data);

            $result[] = [
                'id' => $localId,
                'file_name' => $fileName,
                'size' => $attachment['size'] ?? strlen($data),
                'file_path' => $fullPath,
            ];
        }

        return $result;
    }

    public static function saveBodyToPdfFile(Gmail $gmail): ?string
    {
        try {
            $htmlBody = str_replace(PHP_EOL, '', (string) $gmail->html_body);
            $htmlBody = preg_replace('/<div name="messageSignatureSection">(.*?)<blockquote type="cite"/', '<div><blockquote type="cite"', $htmlBody);
            $htmlBody = preg_replace('/<div dir="ltr" class="gmail_attr">(.*?)<\/div>/', '', $htmlBody);
            $htmlBody = str_replace('[PHP_EOL]', PHP_EOL, $htmlBody);

            $pdf = App::make('snappy.pdf.wrapper')
                ->loadHTML($htmlBody)
                ->setPaper('a4')
                ->setOption('margin-bottom', 0)
                ->setOption('margin-top', 0)
                ->setOption('margin-left', 0)
                ->setOption('margin-right', 0)
                ->setOption('encoding', 'UTF-8');

            $filePath = static::makePdfBodyPath($gmail).'/'.$gmail->mail_id.'.pdf';
            Storage::disk('local')->put($filePath, $pdf->output());
            $gmail->update(['pdf_body_path' => $filePath]);

            return $filePath;
        } catch (\Exception $e) {
            Log::info(print_r([$e->getMessage(), $e->getTraceAsString()], true));

            return null;
        }
    }

    public static function makeAttachmentPath(Gmail $gmail, $localId): string
    {
        return 'gmail/'.date('Y/m/d').'/u'.$gmail->user_id.'/f'.$gmail->gmail_filter_id.'/m_'.$gmail->mail_id.'/attachments/'.$localId;
    }

    public static function makePdfBodyPath(Gmail $gmail): string
    {
        return 'gmail/'.date('Y/m/d').'/u'.$gmail->user_id.'/f'.$gmail->gmail_filter_id.'/m_'.$gmail->mail_id;
    }

    public static function getMailByFilter(GmailFilter $filter, $beforeDate = null, $afterDate = null): void
    {
        try {
            $api = static::api();
            $query = $api->buildSearchQuery($filter->filter, $beforeDate, $afterDate);
            $pageToken = null;
            $page = 0;

            do {
                $result = $api->listMessages($filter->user_id, $query, 100, $pageToken);
                foreach (static::hydrateMessageCollection($result['messages'], $filter->user_id) as $mail) {
                    static::saveData($mail, $filter);
                }

                $pageToken = $result['nextPageToken'] ?? null;
                $page++;
            } while ($pageToken && $page < static::MAX_PAGES_TO_LOAD);
        } catch (\Exception $e) {
            Log::info(print_r([$e->getMessage(), $e->getTraceAsString()], true));
        }
    }

    private static function checkRegex($str, $regex): bool
    {
        try {
            return (bool) preg_match($regex, (string) $str);
        } catch (\Exception $e) {
            Log::info(print_r([$e->getMessage(), $e->getTraceAsString()], true));
        }

        return false;
    }

    /**
     * @param  array<int, array{id: string}>  $messages
     * @return array<int, GmailMessage>
     */
    private static function hydrateMessageCollection(array $messages, int $userId): array
    {
        $hydratedMessages = [];
        $api = static::api();

        foreach ($messages as $message) {
            $messageId = is_array($message) ? ($message['id'] ?? null) : null;
            if (! $messageId) {
                continue;
            }

            try {
                $hydratedMessages[] = $api->getMessage($userId, $messageId);
            } catch (\Throwable $e) {
                Log::warning('Skipping Gmail message during hydration', [
                    'user_id' => $userId,
                    'message_id' => $messageId,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $hydratedMessages;
    }

    private static function parseFwdDate($str): ?\Illuminate\Support\Carbon
    {
        if (! $str) {
            return null;
        }

        preg_match('/class="gmail_quote"(.*?)Forwarded message(.*?)Date:(.*?)</', $str, $res);
        if (isset($res[3])) {
            try {
                return now()->parse($res[3]);
            } catch (\Exception $e) {
                return null;
            }
        }

        return null;
    }
}
