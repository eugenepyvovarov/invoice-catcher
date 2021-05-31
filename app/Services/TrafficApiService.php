<?php
namespace App\Services;

use App\Components\TrafikverketApi;
use App\Models\Station;
use App\Models\Ticket;
use Carbon\Carbon;
use Smalot\PdfParser\Parser;

class TrafficApiService
{
    /**
     * @param Ticket $ticket
     * @return Carbon
     */
    public static function getTrainArrivedTime(Ticket $ticket)
    {
        $response = (new TrafikverketApi())->getTrainArrivalDeparture(TrafikverketApi::ACTIVITY_TYPE_ARRIVAL, $ticket->train_number, $ticket->stationTo->signature, $ticket->arrival_at);
        if (isset($response['RESPONSE']['RESULT'][0]['TrainAnnouncement'][0]['TimeAtLocation'])) {
              $date = $response['RESPONSE']['RESULT'][0]['TrainAnnouncement'][0]['TimeAtLocation'];
            return ['date_time' => Carbon::parse($date)];
        }
    }

    /**
     * @param Ticket $ticket
     * @return array
     */
    public static function getTrainDepartureTime(Ticket $ticket)
    {
        $response = (new TrafikverketApi())->getTrainArrivalDeparture(TrafikverketApi::ACTIVITY_TYPE_DEPARTURE, $ticket->train_number, $ticket->stationFrom->signature, $ticket->departure_at);
        if (isset($response['RESPONSE']['RESULT'][0]['TrainAnnouncement'][0]['TimeAtLocation'])) {
              $date = $response['RESPONSE']['RESULT'][0]['TrainAnnouncement'][0]['TimeAtLocation'];
            return ['date_time' => Carbon::parse($date)];
        }
    }
}