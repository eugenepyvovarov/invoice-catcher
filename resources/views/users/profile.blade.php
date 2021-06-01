@extends('layouts.app')
@section('title', 'Profile')

@section('content')

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('My Profile') }}</div>
                    <div class="card-body">
                        <div>Name: {{ $authUser->name }}</div>
                        <div>Email: {{ $authUser->email }}</div>
                    </div>
                </div>
                <br>
                <div class="card">
                    <div class="card-header">Settings</div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('profile.clearData') }}">
                            @csrf
                            <div class="form-check">
                                <input class="form-check-input" name="models[]" type="checkbox" value="{{ \App\Models\Gmail::class }}" id="gmail" />
                                <label class="form-check-label" for="gmail">
                                    Gmails
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="models[]" type="checkbox" value="{{ \App\Models\GmailFilter::class }}" id="filter" />
                                <label class="form-check-label" for="filter">
                                    Gmail Filters
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" name="models[]" type="checkbox" value="{{ \App\Models\GmailProfile::class }}" id="profile" />
                                <label class="form-check-label" for="profile">
                                    Gmail Profile
                                </label>
                            </div>
                            @error('models')
                            <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                            @enderror
                            <br>
                            <button type="submit" class="btn btn-danger"
                                    onclick="return confirm('Are you sure you want to clear selected local data?');"
                            >
                                {{ __('Clear data') }}
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

@endsection
