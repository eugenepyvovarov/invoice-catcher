@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Login') }}</div>
                <div class="card-body">
                    <a href="{{ route('gmail.login') }}" class="btn btn-primary">Login via Gmail</a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
