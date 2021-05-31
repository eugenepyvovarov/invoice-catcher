@extends('layouts.app')
@section('title', 'Tickets')
@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header"><a href="{{ route('ticket.index') }}">{{ __('Tickets') }}</a></div>
                    <div class="card-body">
                        <div class="float-right">
                            <a href="{{ route('ticket.create', ['filter' => request('filter')]) }}" class="btn btn-primary">
                            {{ __('Add Ticket') }}
                            </a>
                        </div>
                        <div class="clearfix"></div>
                        <div style="margin-top: 15px">
                            <table class="table">
                                <thead>
                                <tr>
                                    <th scope="col">Person</th>
                                    <th scope="col">Train #</th>
                                    <th scope="col">From<br> Station</th>
                                    <th scope="col">Scheduled<br> Departure Time</th>
                                    <th scope="col">Departed<br> Time</th>
                                    <th scope="col">To<br> Station</th>
                                    <th scope="col">Scheduled<br> Arrival Time</th>
                                    <th scope="col">Arrived<br> Time</th>
                                    <th width="130px">Actions</th>

                                </tr>
                                </thead>
                                <tbody>
                                @foreach($tickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->person }}</td>
                                        <td>{{ $ticket->train_number }}</td>
                                        <td>{{ $ticket->from_station }}</td>
                                        <td>{{ $ticket->departure_at }}</td>
                                        <td>
                                            {{ $ticket->departed_at }}
                                            @if ($ticket->departed_at)
                                                <br>
                                            @endif
                                            {{ $ticket->departure_status }}
                                        </td>
                                        <td>{{ $ticket->to_station }}</td>
                                       <td>{{ $ticket->arrival_at }}</td>

                                        <td>
                                            {{ $ticket->arrived_at }}
                                            @if ($ticket->arrived_at)
                                                <br>
                                            @endif
                                            {{ $ticket->arrival_status }}
                                        </td>
                                            <td>
                                                @if (! $ticket->departed_at || ! $ticket->arrived_at)
                                                    <a href="{{ route('ticket.checkTrafikverket', ['id' => $ticket->id]) }}"
                                                       title="Check ticket status"
                                                       class="btn btn-sm btn-default">
                                                        <i class="fas fa-sync"></i>
                                                    </a>
                                                @endif
                                                <a href="{{ route('ticket.edit', $ticket->id) }}"
                                                   title="Edit ticket"
                                                   class="btn btn-sm btn-default"><i class="fas fa-edit"></i></a>
                                                <form method="POST" action="{{ route('ticket.delete', $ticket->id) }}" style="display:inline">
                                                    <button class="btn btn-sm btn-default"
                                                            onclick="return confirm('Delete ticket #: {{ $ticket->id }}?')"
                                                            type="submit"
                                                            title="Remove #{{ $ticket->id }}"><i class="fas fa-trash-alt"></i></button>
                                                    @csrf
                                                </form>
                                            </td>
                                    </tr>
                                @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection