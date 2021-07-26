<?php

namespace App\Http\Controllers;

use App\Jobs\CheckGmail;
use App\Models\Gmail;
use App\Models\GmailFilter;
use App\Services\GmailService;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Dompdf\Dompdf;

class GmailController extends Controller
{

    /**
     * @todo add gmail auth check to middleware? with redirect
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function profile()
    {
        $gmailUser = LaravelGmail::check() ? LaravelGmail::user() : null;

        return view('gmail.profile', compact('gmailUser'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);
        $gmail->delete();
        flash()->success('Mail #'.$gmail->id.' was successfully deleted');
        return redirect()->back();
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function load(Request $request)
    {
        if (LaravelGmail::check()) {

            if ($request->get('new_filter')) {
                $filter = auth()->user()->gmailFilters()->create([
                    'filter' => $request->get('new_filter'),
                    'name' => $request->get('new_filter')
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
        }

        return redirect()->route('gmail.mails', ['filterId' => $filterId]);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function mails(Request $request)
    {
        $authUser = auth()->user();
        $gmailsQuery = $authUser->gmails();

        $gmailDefaultFilter = $authUser->gmailFilters()->where('is_default', true)->first();

        $filter = '';
        if ($filterId = $request->get('filterId')) {
            $gmailFilter = $authUser->gmailFilters()->findOrFail($request->get('filterId'));
            $filter = $gmailFilter->filter;
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

    /**
     * @param $mailId
     * @param $attachmentId
     * @return mixed
     */
    public function downloadAttachment($mailId, $attachmentId)
    {
        $gmail = auth()->user()->gmails()->findOrFail($mailId);
        if (! $attachment = $gmail->getAttachmentById($attachmentId)) {
            abort(404);
        }

        return Storage::download($attachment['file_path']);
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function mailBody($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);
        return $gmail->html_body;
    }

    /**
     * @todo move to GmailProfileController
     * @return mixed
     */
    public function connect()
    {
        return LaravelGmail::redirect();
    }

    public function disconnect()
    {
        LaravelGmail::logout();
        return redirect()->route('gmail.profile');
    }

    public function callback()
    {
        LaravelGmail::makeToken();
        $token = LaravelGmail::getAccessToken();
        auth()->user()->gmailProfiles()->firstOrCreate(['email' => $token['email']]);
        flash()->success('Profile has been successfully connected');

        return redirect()->route('gmail.profile');
    }

    public function downloadPdf($id)
    {
        $gmail = auth()->user()->gmails()->findOrFail($id);

        return Storage::download($gmail->pdf_body_path);
    }

    public function checkboxAction(Request $request)
    {
        $zipFileName = 'report.zip';
        $gmailIds = $request->get('gmailIds');

        if ($gmailIds) {
            $options = new \ZipStream\Option\Archive();
            $options->setSendHttpHeaders(true);
            $zip = new \ZipStream\ZipStream($zipFileName, $options);

            $gmails = auth()->user()->gmails()->whereIn('id', $request->get('gmailIds'))->orderBy('id')->get();
            foreach ($gmails as $gmail) {
                if ($gmail->pdf_body_path) {
                    $zip->addFileFromPath($gmail->clean_date_str.'__'.$gmail->clean_subject.'__['.$gmail->id.'].pdf', $gmail->getPdfBodyFullPath());
                }

                if ($gmail->attachments) {
                    $attachmentsCount = count($gmail->attachments);
                    foreach ($gmail->attachments as $attachment) {
                        $attahcmentIdStr = $attachmentsCount > 1 ? '.'.$attachment['id'] : '';
                        $zip->addFileFromPath($gmail->clean_date_str.'__'.Str::replaceLast('.', '__['.$gmail->id.$attahcmentIdStr.'].', $attachment['file_name']), storage_path('app/'.$attachment['file_path']));
                    }
                }
            }
            $zip->finish();
        }
    }
}
