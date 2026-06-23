<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClearUserDataRequest;

class UserController extends Controller
{
    public function profile()
    {
        $authUser = auth()->user();

        return view('users.profile', compact('authUser'));
    }

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
