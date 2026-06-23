<?php

namespace App\Http\Controllers;

use App\Jobs\CheckGmail;
use App\Models\User;
use App\Services\GmailService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipStream\ZipStream;

class GmailController extends Controller
{
    public function destroy($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);
        $gmail->delete();
        flash()->success('Mail #'.$gmail->id.' was successfully deleted');

        return redirect()->back();
    }

    public function ajaxLoad(Request $request)
    {
        if ($request->get('new_filter')) {
            $filter = auth()->user()->gmailFilters()->create([
                'filter' => $request->get('new_filter'),
                'name' => $request->get('new_filter'),
            ]);
            $filterId = $filter->id;

            CheckGmail::dispatch($filter);
        } elseif ($request->get('filterId')) {
            $filterId = $request->get('filterId');

            $filter = auth()->user()->gmailFilters()->findOrFail($filterId);
            CheckGmail::dispatch($filter);
        } else {
            $filterId = null;
            auth()->user()->gmailFilters()->chunk(100, function ($filters) {
                foreach ($filters as $filter) {
                    CheckGmail::dispatch($filter);
                }
            });
        }

        return response()->json(['redirect_url' => route('gmail.mails', ['filterId' => $filterId])]);
    }

    public function mails(Request $request)
    {
        $authUser = auth()->user();
        $gmailsQuery = $authUser->gmails();

        $gmailDefaultFilter = $authUser->gmailFilters()->where('is_default', true)->first();

        $filter = '';
        $filterId = null;
        if ($request->get('filterId')) {
            $gmailFilter = $authUser->gmailFilters()->findOrFail($request->get('filterId'));
            $filter = $gmailFilter->filter;
            $filterId = $gmailFilter->id;
        } elseif ($gmailDefaultFilter) {
            $filterId = $gmailDefaultFilter->id;
        } elseif ($gmailFilter = $authUser->gmailFilters()->latest('id')->first()) {
            $filterId = $gmailFilter->id;
        }

        $gmails = $gmailsQuery->where('gmail_filter_id', $filterId)
            ->with(['gmailFilter'])
            ->orderBy('date', 'desc')
            ->paginate(500);

        $gmailFilters = $authUser->gmailFilters()->where('is_default', false)->latest()->limit(10)->get();

        if ($gmailDefaultFilter) {
            $gmailFilters->prepend($gmailDefaultFilter);
        }

        return view('gmail.mails', compact('gmails', 'gmailFilters', 'filter'));
    }

    public function downloadAttachment($mailId, $attachmentId)
    {
        $gmail = auth()->user()->gmails()->findOrFail($mailId);
        if (! $attachment = $gmail->getAttachmentById($attachmentId)) {
            abort(404);
        }

        return Storage::disk('local')->download($attachment['file_path']);
    }

    public function mailBody($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);

        return $gmail->html_body;
    }

    public function login()
    {
        return redirect()->away(GmailService::getAuthUrl());
    }

    public function disconnect()
    {
        GmailService::disconnect(auth()->user());
        auth()->logout();

        return redirect()->route('home');
    }

    public function callback(Request $request)
    {
        if ($request->get('error')) {
            flash()->error('Gmail authentication failed: '.$request->get('error'));

            return redirect()->route('login');
        }

        $code = $request->get('code');
        if (! $code) {
            flash()->error('Missing OAuth authorization code');

            return redirect()->route('login');
        }

        $result = GmailService::handleOAuthCallback($code);
        $token = $result['token'];
        $email = $result['email'] ?? ($token['email'] ?? null);

        if (! $email) {
            flash()->error('Could not determine Google account email');

            return redirect()->route('login');
        }

        $tempUserId = 'tmp_'.uniqid();
        GmailService::storeToken($tempUserId, $token);

        $user = User::firstOrCreate(['email' => $email]);
        GmailService::moveToken($tempUserId, $user->id);

        auth()->login($user, true);

        flash()->success('Gmail profile has been successfully authenticated');

        return redirect()->route('gmail.mails');
    }

    public function downloadPdf($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);

        return Storage::disk('local')->download($gmail->pdf_body_path, $gmail->pdf_body_file_name);
    }

    public function checkboxAction(Request $request)
    {
        $gmailIds = $request->get('gmailIds');

        if (! $gmailIds) {
            return response('No mails selected', 400);
        }

        $zipFileName = 'report.zip';
        $zip = new ZipStream(outputName: $zipFileName, sendHttpHeaders: true);

        $gmails = auth()->user()->gmails()->whereIn('id', $gmailIds)->orderBy('id')->get();
        foreach ($gmails as $gmail) {
            if ($gmail->pdf_body_path && Storage::disk('local')->exists($gmail->pdf_body_path)) {
                $zip->addFileFromPath(
                    $gmail->pdf_body_file_name,
                    Storage::disk('local')->path($gmail->pdf_body_path)
                );
            }

            if ($gmail->attachments) {
                $attachmentsCount = count($gmail->attachments);
                foreach ($gmail->attachments as $attachment) {
                    if (empty($attachment['file_path']) || ! Storage::disk('local')->exists($attachment['file_path'])) {
                        continue;
                    }
                    $attachmentIdStr = $attachmentsCount > 1 ? '.'.$attachment['id'] : '';
                    $name = $gmail->clean_date_str.'__'.Str::replaceLast('.', '__['.$gmail->id.$attachmentIdStr.'].', $attachment['file_name']);
                    $zip->addFileFromPath($name, Storage::disk('local')->path($attachment['file_path']));
                }
            }
        }

        $zip->finish();

        return response('', 200);
    }
}
