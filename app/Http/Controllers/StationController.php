<?php

namespace App\Http\Controllers;

use App\Models\Station;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class StationController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tickets.create');
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function ajaxStations(Request $request)
    {
        $q = $request->get('q');
        $stations = Station::where('name', 'like', "%$q%")
            ->orWhere('short_name', 'like', "%$q%")
            ->orWhere('synonyms', 'like', "%$q%")
            ->orWhere('signature', 'like', "%$q%")
            ->get()
            ->transform(function ($item, $key) {
                return [
                    'id' => $item['name'], // @todo refactor ticket stations to relation?
                    'text' => $item['name'],
                ];
            })
            ->values();

        return ['results' => $stations];
    }


}
