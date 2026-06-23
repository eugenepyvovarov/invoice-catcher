<?php

namespace App\Http\Controllers;

use App\Jobs\CheckGmail;
use App\Models\User;
use App\Services\GmailLoadStatus;
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
        $filterId = null;
        $filtersToRun = collect();

        if ($request->get('new_filter')) {
            $filter = auth()->user()->gmailFilters()->create([
                'filter' => $request->get('new_filter'),
                'name' => $request->get('new_filter'),
            ]);
            $filterId = $filter->id;
            $filtersToRun->push($filter);
        } elseif ($request->get('filterId')) {
            $filterId = (int) $request->get('filterId');
            $filtersToRun->push(auth()->user()->gmailFilters()->findOrFail($filterId));
        } else {
            auth()->user()->gmailFilters()->orderBy('id')->chunk(100, function ($filters) use ($filtersToRun) {
                foreach ($filters as $filter) {
                    $filtersToRun->push($filter);
                }
            });
            $filterId = $filtersToRun->first()?->id;
        }

        foreach ($filtersToRun as $filter) {
            GmailLoadStatus::markQueued($filter->id, 'Queued — starting after this page loads…');
            // Runs after the HTTP response so the UI is not blocked for minutes.
            CheckGmail::dispatch($filter)->afterResponse();
        }

        return response()->json([
            'redirect_url' => route('gmail.mails', array_filter(['filterId' => $filterId, 'loading' => 1])),
            'loading' => true,
            'filter_id' => $filterId,
            'status' => $filterId ? GmailLoadStatus::get($filterId) : null,
        ]);
    }

    public function loadStatus(Request $request, $filterId)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($filterId);
        $status = GmailLoadStatus::get($filter->id);
        $status['row_count'] = $filter->gmails()->count();
        $status['active'] = GmailLoadStatus::isActive($status);

        return response()->json($status);
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

        $loadStatus = $filterId ? GmailLoadStatus::get((int) $filterId) : null;
        $loadActive = GmailLoadStatus::isActive($loadStatus)
            || $request->boolean('loading');

        return view('gmail.mails', compact('gmails', 'gmailFilters', 'filter', 'filterId', 'loadStatus', 'loadActive'));
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

        try {
            $result = GmailService::handleOAuthCallback($code);
        } catch (\Throwable $e) {
            \Log::error('Gmail OAuth callback failed', ['error' => $e->getMessage()]);
            flash()->error('Gmail authentication failed: '.$e->getMessage());

            return redirect()->route('login');
        }

        $token = $result['token'];
        $email = $result['email'] ?? ($token['email'] ?? null);

        if (! $email) {
            flash()->error('Could not determine Google account email. Ensure openid/email scopes are granted, then try again.');

            return redirect()->route('login');
        }

        $tempUserId = 'tmp_'.uniqid('', true);
        GmailService::storeToken($tempUserId, $token);

        $user = User::firstOrCreate(
            ['email' => $email],
            ['name' => $email, 'password' => null]
        );
        GmailService::moveToken($tempUserId, $user->id);

        // Ensure token is definitely stored under the real user id even if move failed
        if (! app(\App\Services\Gmail\GmailTokenStore::class)->exists($user->id)) {
            GmailService::storeToken($user->id, $token);
        }

        auth()->login($user, true);
        $request->session()->regenerate();

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
