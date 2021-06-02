<?php
namespace App\Services;

use App\Models\Gmail;
use App\Models\GmailFilter;
use App\Models\User;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Dacastro4\LaravelGmail\Services\Message;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;

class GmailService
{
    const MAX_PAGES_TO_LOAD = 1000;

    public static function getRecentMails()
    {
        return LaravelGmail::message()->take(1)->preload()->all();
    }

    public static function authCheck(User $user)
    {
        return LaravelGmail::setUserId($user->id)->check();
    }

    public static function saveData(Message\Mail $mail, GmailFilter $filter)
    {
        if ($filter->regex && (! static::checkRegex($mail->getSubject(), $filter->regex) && ! static::checkRegex($mail->getHtmlBody(), $filter->regex))) {
            return;
        }

        $gmail = $filter->gmails()->firstOrNew([
            'gmail_filter_id' => $filter->id,
            'mail_id' => $mail->getId(),
        ]);
        if (! $gmail->id) {
            $gmail->fill([
                'user_id' => $filter->user_id,
                'internal_date' => $mail->getInternalDate(),
                'date' => $mail->getDate(),
                'labels' => $mail->getLabels(),
                'subject' => $mail->getSubject(),
                'from_name' => $mail->getFromName(),
                'from_email' => $mail->getFromEmail(),
                'to' => $mail->getTo(),
                'delivered_to' => $mail->getDeliveredTo(),
                'html_body' => $mail->getHtmlBody(),
                'attachments' => $mail->hasAttachments() ? static::downloadAttachments($mail, $filter->user_id): null // todo: check why auth fails
            ]);
            $gmail->save();

            if (! $gmail->attachments) {
                // save html pdf body
                static::saveBodyToPdfFile($gmail);
            }
        }
    }

    /**
     * @param Message\Mail $mail
     * @param $userId
     * @return array
     */
    public static function downloadAttachments(Message\Mail $mail, $userId)
    {
        $localId = 0;
        foreach ($attachments = $mail->getAttachments() as $attachment) {

            $fileName = $attachment->getFileName();

            // filter by extension
            if (! in_array(pathinfo($fileName, PATHINFO_EXTENSION), ['pdf'])) continue;

            $localId++;
            $attachmentPath = static::makeAttachmentPath($mail->getId(), $localId, $userId);

           $attachment->saveAttachmentTo($attachmentPath, $fileName);

            $result[] = [
                'id' => $localId,
                'file_name' => $fileName,
                'size' => $attachment->getSize(),
                'file_path' => $attachmentPath.'/'.$fileName,
            ];
        }

        return $result;
    }


    public static function saveBodyToPdfFile(Gmail $gmail)
    {
        $htmlBody =  $gmail->html_body;
        $htmlBody = str_replace(PHP_EOL, '', $gmail->html_body); // pdf devizion by zero issue fix
        //$htmlBody = str_replace(['cellpadding="0"', 'cellspacing="0"'], ['cellpadding="1"', 'cellspacing="1"'], $htmlBody); // pdf devizion by zero issue fix
        $htmlBody = preg_replace('/<div name="messageSignatureSection">(.*?)<blockquote type="cite"/', '<div><blockquote type="cite"', $htmlBody); // pdf devizion by zero issue fix
        $htmlBody = preg_replace('/<blockquote(.*?)>/', '<blockquote>', $htmlBody);
        $htmlBody = str_replace('[PHP_EOL]', PHP_EOL, $htmlBody);

        $pdf = App::make('snappy.pdf.wrapper')
            ->loadHTML($htmlBody)
            ->setPaper('a4')
            ->setOption('margin-bottom', 0)
            ->setOption('margin-top', 0)
            ->setOption('margin-left', 0)
            ->setOption('margin-right', 0);

        $filePath = static::makePdfBodyPath($gmail->mail_id, $gmail->user_id).'/'.$gmail->mail_id.'.pdf';

        Storage::disk('local')->put($filePath, $pdf->output());

        $gmail->update(['pdf_body_path' => $filePath]);

        return $filePath;

    }


    /**
     * @param $mailId
     * @param $localId
     * @param $userId
     * @return string
     */
    public static function makeAttachmentPath($mailId, $localId, $userId)
    {
        return 'gmail/'.date('Y/m/d').'/u'.$userId.'/m_'.$mailId.'/attachments/'.$localId;
    }

    /**
     * @param $mailId
     * @param $userId
     * @return string
     */
    public static function makePdfBodyPath($mailId, $userId)
    {
        return 'gmail/'.date('Y/m/d').'/u'.$userId.'/m_'.$mailId;
    }


    public static function getMailByFilter(GmailFilter $filter, $beforeDate = null, $afterDate = null)
    {
        try {
            $query = LaravelGmail::setUserId($filter->user_id)
                ->message();

            if ($filter) {
                $query->raw($filter->filter);
            }

            if ($beforeDate) {
                $query->before($beforeDate);
            }

            if ($afterDate) {
                $query->after($afterDate);
            }

            $messages = $query->take(100)
                ->preload()
                ->all();


            foreach ($messages as $mail) {
                static::saveData($mail, $filter);
            }

            $page = 0;
            while ($messages->hasNextPage()) {
                if ($page++ >= static::MAX_PAGES_TO_LOAD) {
                   return;
                }

                foreach ($messages->next() as $mail) {
                    static::saveData($mail, $filter);
                }
            }
        } catch (\Exception $e) { // @todo use google exception
            \Log::info(print_r($e->getMessage(), true));
            return []; // handle different errors
        }
    }

    private static function checkRegex($str, $regex)
    {
        return preg_match($regex, $str, $result);
    }


}