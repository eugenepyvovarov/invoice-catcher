<?php
namespace App\Services;

use App\Components\TrafikverketApi;
use App\Models\Ticket;
use Carbon\Carbon;
use \Illuminate\Support\Facades\File;
use Smalot\PdfParser\Parser;

class TicketService
{

    public static function getStatus($scheduledTime, $actualTime)
    {
        if (! $scheduledTime) {
            return 'n/a';
        }

        if ($actualTime) {
            if ($actualTime->lt($scheduledTime)) {
                if ($hours = $actualTime->diffInHours($scheduledTime, true)) {
                    return $hours.'hrs faster';
                } elseif ($minutes = $actualTime->diffInMinutes($scheduledTime, true)) {
                    return $minutes.' min faster';
                }
            } elseif ($actualTime->gt($scheduledTime)) {
                if ($hours = $actualTime->diffInHours($scheduledTime, true)) {
                    return $hours.'hrs delay';
                } elseif ($minutes = $actualTime->diffInMinutes($scheduledTime, true)) {
                    return $minutes.' min delay';
                }
            }
            return 'in time';
        } elseif (now()->gte($scheduledTime)) {
            if (now()->diffInDays($scheduledTime) > 10) {
                return 'n/a';
            }

            return 'pending update';
        }
    }

    public static function getStations()
    {
       return collect(json_decode(File::get(storage_path('app/stations.json')), true))->transform(function ($item, $key) {
           return [
               'id' => $key,
               'text' => $item,
           ];
       })->values();
    }
}