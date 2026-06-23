<?php

namespace App\Http\Controllers;

use App\Http\Requests\GmailFilterRequest;
use App\Models\GmailFilter;

class GmailFilterController extends Controller
{
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

    public function create()
    {
        return view('gmail.filters.create');
    }

    public function store(GmailFilterRequest $request)
    {
        $filter = auth()->user()->gmailFilters()->create($request->validated());
        if ($request->get('is_default')) {
            GmailFilter::makeDefault($filter);
        }

        return redirect()->route('gmailFilter.index');
    }

    public function edit($id)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($id);

        return view('gmail.filters.edit', compact('filter'));
    }

    public function update(GmailFilterRequest $request, $id)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($id);
        $filter->update($request->validated());
        if ($request->get('is_default')) {
            GmailFilter::makeDefault($filter);
        }

        return redirect()->route('gmailFilter.index');
    }

    public function destroy($id)
    {
        $filter = auth()->user()->gmailFilters()->findOrFail($id);
        $filter->delete();

        return redirect()->route('gmailFilter.index');
    }
}
