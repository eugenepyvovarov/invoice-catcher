<?php

namespace App\Console\Commands;

use App\Components\TrafikverketApi;
use App\Models\Station;
use Illuminate\Console\Command;

class LoadStations extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stations:load';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Get list of all stations and save to database';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $items = collect((new TrafikverketApi())->getStations())->flatten(4)->toArray();
        foreach ($items as $item) {
            $station = Station::firstOrNew(['signature' => $item['LocationSignature']]);
            $station->fill([
                'name' => $item['AdvertisedLocationName'],
                'synonym' => $item['AdvertisedShortLocationName'],
            ]);
            $station->save();
        }
        $this->info('Stations: '.count($items));
    }
}
