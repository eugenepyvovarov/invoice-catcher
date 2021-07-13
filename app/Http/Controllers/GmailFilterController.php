<?php

namespace App\Http\Controllers;

use App\Http\Requests\GmailFilterRequest;
use App\Jobs\GmailInitialLoad;
use App\Models\GmailFilter;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class GmailFilterController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $gmailFilters = auth()->user()->gmailFilters()
            ->where('is_default', false)
            ->withCount('gmails')
            ->latest('id')
            ->get();
        $gmailDefaultFilter = auth()->user()->gmailFilters()
            ->where('is_default', true)
            ->withCount('gmails')
            ->first();
        return view('gmail.filters.index', compact('gmailFilters', 'gmailDefaultFilter'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('gmail.filters.create');
    }

    /**
     * @param GmailFilterRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(GmailFilterRequest $request)
    {

       $filter = auth()->user()->gmailFilters()->create($request->validated());
        if ($request->get('is_default')) {
            GmailFilter::makeDefault($filter);
        }
        return redirect()->route('gmailFilter.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($id);
        return view('gmail.filters.edit', compact('filter'));
    }

    /**
     * @param GmailFilterRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(GmailFilterRequest $request, $id)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($id);
        $filter->update($request->validated());
        if ($request->get('is_default')) {
            GmailFilter::makeDefault($filter);
        }
        return redirect()->route('gmailFilter.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($id);
        $filter->delete();
        return redirect()->route('gmailFilter.index');
    }
}
