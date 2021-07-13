@extends('layouts.app')
@section('title', 'Gmail Filters')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Gmail Filters') }}</div>

                    <div class="card-body">
                        <div class="float-right">
                            <a href="{{ route('gmailFilter.create') }}" class="btn btn-primary">
                                {{ __('Add Filter') }}
                            </a>
                        </div>
                        <div class="clearfix"></div>

                        <div style="margin-top: 15px">
                            <table class="table table-bordered">
                                <thead>
                                <tr>
                                    <th scope="col">Name</th>
                                    <th scope="col">Filter</th>
                                    <th scope="col">Regex</th>
                                    <th scope="col">Mails</th>
                                    <th width="90">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @if($gmailDefaultFilter))
                                <tr>
                                    <td>{{ $gmailDefaultFilter->name }}
                                        <span style="background-color: lightblue; padding: 0px 3px 0px 3px">default</span>
                                    </td>
                                    <td>{{ $gmailDefaultFilter->filter }}</td>
                                    <td>{{ $gmailDefaultFilter->regex }}</td>
                                    <td>
                                        <a href="{{ route('gmail.mails', ['filterId' => $gmailDefaultFilter->id]) }}">
                                            {{ $gmailDefaultFilter->gmails_count }}
                                        </a>
                                    </td>                                    <td>
                                        <a href="{{ route('gmailFilter.edit', $gmailDefaultFilter->id) }}" class="btn btn-sm btn-default"><i class="fas fa-edit"></i></a>
                                        <form method="POST" action="{{ route('gmailFilter.delete', $gmailDefaultFilter->id) }}" style="display:inline">
                                            <button class="btn btn-sm btn-default"
                                                    onclick="return confirm('Delete filter: {{ $gmailDefaultFilter->name }}?')"
                                                    type="submit"
                                                    title="Remove #{{ $gmailDefaultFilter->id }}"><i class="fas fa-trash-alt"></i></button>
                                            @csrf
                                        </form>
                                    </td>
                                </tr>
                                @endif
                                @foreach($gmailFilters as $gmailFilter)
                                    <tr>
                                        <td>{{ $gmailFilter->name }}</td>
                                        <td>{{ $gmailFilter->filter }}</td>
                                        <td>{{ $gmailFilter->regex }}</td>
                                        <td>
                                            <a href="{{ route('gmail.mails', ['filterId' => $gmailFilter->id]) }}">
                                                {{ $gmailFilter->gmails_count }}
                                            </a>
                                       </td>
                                        <td>
                                            <a href="{{ route('gmailFilter.edit', $gmailFilter->id) }}" class="btn btn-sm btn-default"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="{{ route('gmailFilter.delete', $gmailFilter->id) }}" style="display:inline">
                                                <button class="btn btn-sm btn-default"
                                                        onclick="return confirm('Delete filter: {{ $gmailFilter->name }}?')"
                                                        type="submit"
                                                        title="Remove #{{ $gmailFilter->id }}"><i class="fas fa-trash-alt"></i></button>
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