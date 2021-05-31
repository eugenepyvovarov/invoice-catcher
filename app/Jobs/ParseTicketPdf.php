<?php

namespace App\Jobs;

use App\Models\Gmail;
use App\Models\Station;
use App\Models\User;
use App\Services\PdfService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ParseTicketPdf implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $gmail;
    public $attachment;

    /**
     * ParseTicketPdf constructor.
     * @param Gmail $gmail
     * @param $attachment
     */
    public function __construct(Gmail $gmail, $attachment)
    {
        $this->gmail = $gmail;
        $this->attachment = $attachment;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $pages = PdfService::parseTicketData(storage_path('app/'.$this->attachment['file_path']));
        foreach ($pages as $page) {
            $time = explode('.', $page['time']['departure_at']);

            $departureAt = now()->parse($page['valid_at'])->hour()->minute();
            $this->gmail->tickets()->create([
                'user_id' => $this->gmail->user_id,
                'person' => $page['person'],
                'from_station' => $this->detectStation($page['destinations']['from']),
                'to_station' => $this->detectStation($page['destinations']['to']),
                'train_number' => $page['train_number'],
                'departure_at' => $page['departure_at'],
                'arrival_at' => $page['arrival_at'],
                'data' => $page,
            ]);
        }
    }
    /**
     * Execute the job.
     *
     * @return void
     */
    public function detectStation($stationStr)
    {
        $stration = Station::nameOrSynonym($stationStr)->first();
        return $stration->name;
    }
}
