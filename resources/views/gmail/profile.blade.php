@extends('layouts.app')
@section('title', 'Gmail Profile')

@section('content')

    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Gmail Profile') }}</div>

                    <div class="card-body">
                        @if($gmailUser)
                            Connected Profile:
                            <h3>{{ $gmailUser }}</h3>
                            <a href="{{ route('gmail.disconnect') }}"  class="btn btn-primary">Disconnect</a>
                        @else
                            <a href="{{ route('gmail.connect') }}"  class="btn btn-primary">Connect</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection


{{--@extends('layouts.app')--}}

{{--@section('content')--}}
{{--<div class="py-12" style="margin: 30px; padding: 30px; border: 1px solid #d7d7d7; background-color: white">--}}
{{--<div class="max-w-7xl mx-auto sm:px-6 lg:px-8">--}}

{{--@if($gmailUser)--}}
{{--You are logged as:--}}
{{--<h3>{{ $gmailUser }}</h3>--}}
{{--<a href="{{ route('gmail.disconnect') }}">[Disconnect Gmail]</a>--}}

{{--<hr>--}}
{{--<h4>Recent:</h4><br>--}}
{{--@foreach($recentMails as $recentMail)--}}
{{--<em>{{ $recentMail->getFromName() }}</em> - {{ $recentMail->getSubject() }}<br>--}}
{{--@endforeach--}}
{{--@else--}}
{{--<a href="{{ route('gmail.connect') }}">[ Connect Gmail ]</a>--}}
{{--@endif--}}


{{--</div>--}}
{{--</div>--}}
{{--@endsection--}}
