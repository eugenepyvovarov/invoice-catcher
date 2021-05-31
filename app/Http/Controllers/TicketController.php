<?php

namespace App\Http\Controllers;

use App\Http\Requests\TicketRequest;
use App\Jobs\CheckTicketArrival;
use App\Jobs\CheckTicketDeparture;
use App\Models\Ticket;
use App\Services\GmailService;
use App\Services\TrafficApiService;
use Dacastro4\LaravelGmail\Facade\LaravelGmail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TicketController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $ticketsQuery = auth()->user()->tickets();

        if ($request->get('gmailId')) {
            $ticketsQuery->where('gmail_id', $request->get('gmailId'));
        }
        $tickets = $ticketsQuery->with('gmail')->get();

        return view('tickets.index', compact('tickets'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $ticket = new Ticket();
        return view('tickets.create', compact('ticket'));
    }

    /**
     * @param GmailFilterRequest $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(TicketRequest $request)
    {
        auth()->user()->tickets()->create($request->validated());
        return redirect()->route('ticket.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $ticket = auth()->user()->tickets()->findOrFail($id);
        return view('tickets.edit', compact('ticket'));
    }

    /**
     * @param GmailFilterRequest $request
     * @param $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function update(TicketRequest $request, $id)
    {
        $ticket = auth()->user()->tickets()->findOrFail($id);
        $ticket->update($request->validated());
        flash()->success('Ticket #'.$ticket->id.' was successfully updated');
        return redirect()->route('ticket.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $ticket = auth()->user()->tickets()->findOrFail($id);
        $ticket->delete();
        flash()->success('Ticket #'.$ticket->id.' was successfully deleted');

        return redirect()->route('ticket.index');

    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function checkTrafikverket($id)
    {
        $ticket = auth()->user()->tickets()->findOrFail($id);

        if (! $ticket->departed_at) {
            CheckTicketDeparture::dispatch($ticket);

            $time = TrafficApiService::getTrainDepartureTime($ticket);
            if (isset($time['date_time'])) {
                flash()->success('Departure time for train #'.$ticket->train_number.' is '.$time['date_time']);
            } else {
                flash()->warning('Departure time  not found');
            }
        }

        if (! $ticket->arrived_at) {
            CheckTicketArrival::dispatch($ticket);

            $time = TrafficApiService::getTrainArrivedTime($ticket);
            if (isset($time['date_time'])) {
                flash()->success('Arrival time for train #'.$ticket->train_number.' is '.$time['date_time']);
            } else {
                flash()->warning('Arrival time  not found');
            }
        }

        return back();
    }

}
