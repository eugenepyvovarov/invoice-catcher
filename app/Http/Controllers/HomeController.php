<?php

namespace App\Http\Controllers;

use App\Components\TrafikverketApi;
use App\Jobs\CheckGmail;
use App\Jobs\CheckTicketArrival;
use App\Jobs\GmailInitialLoad;
use App\Jobs\ParseTicketPdf;
use App\Models\Gmail;
use App\Models\Station;
use App\Models\Ticket;
use App\Services\GmailService;
use App\Services\PdfService;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function test()
    {


     // return Station::where('name2', 'Sthlm Central')->first();
       // Station::query()->update(['synonyms' => null]);exit;
        $res =  json_decode(file_get_contents(storage_path('app/stations_sj.se.json')), true);
        $res2 = [];
        foreach ($res as $item) {
//            if (isset($item['synonyms'][5])) {
//                print_r( $item['synonyms']);
//             //   echo $item['synonyms'][10].PHP_EOL;
//            }
           // $res2[$item['synonyms'][0] ?? $item['name']] = $item['name'];
            $synonyms = collect($item['synonyms'])
                ->add($item['name'])
                ->toArray();
//            Station::where('name', $item['name'])->update([
//                'synonyms' => $item['synonyms']
//            ]);

            foreach ($synonyms as $synonym) {

                $synonymModified = strtolower($synonym);
                $synonymModified = str_replace(' station', '', $synonymModified);
                $synonymModified = str_replace(' SKOLA', '', $synonymModified);

//                if (Str::contains($synonym, 'BENGTSFORS')) {
//                    dd([$synonyms, $synonymModified]);
//                }
                Station::whereIn('name', [$synonym, $synonymModified])->orWhereIn('short_name', [$synonym, $synonymModified])->update([
                    'synonyms' => $item['synonyms']
                ]);
            }
        }
        exit;

        $stations = Station::whereNull('name2')->get();
        foreach ($stations as $station) {
            foreach ($res2 as $key => $item) {
                if (Str::contains($item, $station->name)) {
                    $station->update(['name2' => $key]);
                    echo  $key.PHP_EOL;
                }
            }
        }

      //  return$res2;
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        return view('home');
    }
}
