<?php

namespace App\Jobs;

use App\Components\TrafikverketApi;
use App\Models\Ticket;
use App\Services\TrafficApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CheckTicketDeparture implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private $ticket;

    /**
     * CheckTicketArrival constructor.
     * @param Ticket $ticket
     */
    public function __construct(Ticket $ticket)
    {
        $this->ticket = $ticket;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $this->ticket->update(['departure_checked_at' => now()]);

        $response = TrafficApiService::getTrainDepartureTime($this->ticket);
        if (! empty($response['date_time'])) {
            $this->ticket->update(['departed_at' => $response['date_time']]);
        }
    }
}
