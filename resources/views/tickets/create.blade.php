@extends('layouts.app')

@section('title', 'Add Ticket')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Add Ticket') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('ticket.store') }}">
                            @include('tickets._form')
                            <div class="form-group row mb-0">
                                <div class="col-md-3">
                                    <button type="submit" class="btn btn-primary">
                                        {{ __('Add') }}
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
