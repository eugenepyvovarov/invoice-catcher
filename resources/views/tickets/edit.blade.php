@extends('layouts.app')

@section('title', 'Edit Ticket')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Edit Ticket') }}</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('ticket.update', $ticket->id) }}">
                            @include('tickets._form')
                            <div class="form-group row mb-0">
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Update') }}
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
