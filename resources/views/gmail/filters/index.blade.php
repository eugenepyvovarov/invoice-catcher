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
                                    <th width="140px">Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                @foreach($filters as $filter)
                                    <tr>
                                        <td>{{ $filter->name }}</td>
                                        <td>{{ $filter->filter }}</td>
                                        <td>{{ $filter->regex }}</td>
                                        <td>{{ $filter->gmails_count }}</td>
                                        <td>
                                            <a href="{{ route('gmail.mails', ['filterId' => $filter->id]) }}" class="btn btn-sm btn-default"><i class="fas fa-eye"></i></a>
                                            <a href="{{ route('gmailFilter.edit', $filter->id) }}" class="btn btn-sm btn-default"><i class="fas fa-edit"></i></a>
                                            <form method="POST" action="{{ route('gmailFilter.delete', $filter->id) }}" style="display:inline">
                                                <button class="btn btn-sm btn-default"
                                                        onclick="return confirm('Delete filter: {{ $filter->name }}?')"
                                                        type="submit"
                                                        title="Remove #{{ $filter->id }}"><i class="fas fa-trash-alt"></i></button>
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