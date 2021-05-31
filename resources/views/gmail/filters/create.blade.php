@extends('layouts.app')

@section('title', 'Add Gmail Filter')
@section('content')
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Add Gmail Filter') }}</div>

                    <div class="card-body">
                        <form method="POST" action="{{ route('gmailFilter.store') }}">
                            @csrf
                            <div class="form-group row">
                                <label for="name" class="col-md-10 col-form-label">{{ __('Name') }}*</label>

                                <div class="col-md-10">
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                    @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="form-group row">
                                <label for="filter" class="col-md-10 col-form-label">{{ __('Filter') }}
                                    <a href="https://support.google.com/mail/answer/7190" target="_blank">(?)</a>
                                *</label>

                                <div class="col-md-10">
                                    <textarea
                                            id="filter"
                                            class="form-control @error('filter') is-invalid @enderror"
                                            name="filter"
                                            placeholder="e.g. filename:ticket.pdf"
                                    >{{ old('filter') }}</textarea>

                                    @error('filter')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="regex" class="col-md-10 col-form-label">{{ __('Regex') }}</label>
                                <div class="col-md-10">
                                    <input id="regex" type="text"
                                           class="form-control @error('regex') is-invalid @enderror"
                                           name="regex"
                                           value="{{ old('regex') }}"
                                           autocomplete="regex">
                                    @error('regex')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
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