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
                'attachments' => $mail->hasAttachments() ? static::downloadAttachments($mail, $gmail): null // todo: check why auth fails
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
     * @param GmailFilter $filter
     * @return array
     */
    public static function downloadAttachments(Message\Mail $mail, Gmail $gmail)
    {
        $localId = 0;
        $result = [];
        foreach ($attachments = $mail->getAttachments() as $attachment) {

            $fileName = $attachment->getFileName();

            // filter by extension
            if (! in_array(pathinfo($fileName, PATHINFO_EXTENSION), config('gmail.allowed_extensions'))) continue;

            $localId++;
            $attachmentPath = static::makeAttachmentPath($gmail, $localId);

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
        try {
            $htmlBody =  $gmail->html_body;
            $htmlBody = str_replace(PHP_EOL, '', $gmail->html_body); // pdf devizion by zero issue fix
            //$htmlBody = str_replace(['cellpadding="0"', 'cellspacing="0"'], ['cellpadding="1"', 'cellspacing="1"'], $htmlBody); // pdf devizion by zero issue fix
            $htmlBody = preg_replace('/<div name="messageSignatureSection">(.*?)<blockquote type="cite"/', '<div><blockquote type="cite"', $htmlBody); // pdf devizion by zero issue fix
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
            \Log::info(print_r([$e->getMessage(), $e->getTraceAsString()], true));
        }

    }

    /**
     * @param Gmail $gmail
     * @param $localId
     * @return string
     */
    public static function makeAttachmentPath(Gmail $gmail, $localId)
    {
        return 'gmail/'.date('Y/m/d').'/u'.$gmail->user_id.'/f'.$gmail->gmail_filter_id.'/m_'.$gmail->mail_id.'/attachments/'.$localId;
    }

    /**
     * @param Gmail $gmail
     * @return string
     */
    public static function makePdfBodyPath(Gmail $gmail)
    {
        return 'gmail/'.date('Y/m/d').'/u'.$gmail->user_id.'/f'.$gmail->gmail_filter_id.'/m_'.$gmail->mail_id;
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
            \Log::info(print_r([$e->getMessage(), $e->getTraceAsString()], true));
            return []; // handle different errors
        }
    }

    private static function checkRegex($str, $regex)
    {
        try {
             return preg_match($regex, $str, $result);
        } catch (\Exception $e) {
            \Log::info(print_r([$e->getMessage(), $e->getTraceAsString()], true));
        }

        return false;
    }

    /**
     * @param $str
     * @return \Illuminate\Support\Carbon|null
     */
    private static function parseFwdDate($str)
    {
        preg_match('/class="gmail_quote"(.*?)Forwarded message(.*?)Date:(.*?)</', $str, $res);
        if (isset($res[3])) {
            try {
                return now()->parse($res[3]);
            } catch (\Exception $e) {
                return null;
            }
        }
        return  null;
    }


}
