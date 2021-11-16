<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClearUserDataRequest;
use App\Jobs\CheckGmail;
use App\Models\GmailFilter;
use App\Services\GmailService;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class UserController extends Controller
{

    /**
     * @todo add gmail auth check to middleware? with redirect
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function profile()
    {
        $authUser = auth()->user();
        return view('users.profile', compact('authUser'));
    }

    /**
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View
     */
    public function clearData(ClearUserDataRequest $request)
    {
        $authUser = auth()->user();

        if ($request->get('models')) {
            foreach ($request->get('models') as $model) {
                foreach ($model::where('user_id', $authUser->id)->get() as $item) {
                    $item->delete();
                }
            }
            flash()->success('Selected data has been cleared');
        } else {
            flash()->warning('No data selected');
        }

        return back();
    }
}
