@extends('layouts.app')
@section('title', 'Gmail Mails')

@section('content')
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">{{ __('Gmail Mails') }}</div>
                    <div class="card-body">
                        <div class="float-right">

                        </div>
                        <div class="row">
                        <div class="col-md-8">

                        <form action="{{ route('gmail.load') }}" method="POST">
                            <div class="row">
                                <div class="col-md-12">
                                    Filter:
                                    <select id="filterMenu" name="filterId" class="form-control">
                                        <option value="">-</option>
                                        @foreach($gmailFilters as $gmailFilter)
                                            <option
                                                    value="{{ $gmailFilter->id }}"
                                                    @if(request('filterId') == $gmailFilter->id) selected @endif
                                            > {{ \Illuminate\Support\Str::limit($gmailFilter->name, 100) }}</option>
                                        @endforeach
                                    </select>
                                   <div style="margin-top: 10px; padding: 5px 0px 5px 15px; background-color: #effaff">
                                        {{ $filter}}
                                    </div>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label for="filter" class="col-md-10 col-form-label">
                                    <a href="https://support.google.com/mail/answer/7190" target="_blank">{{ __('New Filter') }}</a>
                                    </label>
                                <div class="col-md-12">
                                    <textarea
                                            class="form-control @error('new_filter') is-invalid @enderror"
                                            name="new_filter"
                                            placeholder="e.g. filename:ticket.pdf"
                                    >{{ old('new_filter') }}</textarea>

                                    @error('filter')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                    @enderror
                                </div>
                            </div>
                            <div class="form-group row">
                                <div class="col-md-10">
                                    <button class="btn btn-primary">
                                        {{ __('Load') }}
                                    </button>
                                </div>
                            </div>
                                @csrf
                            </form>
                        </div>
                        </div>
                        <div class="clearfix"></div>
                        <br>
                        Rows: {{ $gmails->total() }}
                        <form name="checkboxForm" action="{{ route('gmail.checkboxAction') }}" method="POST" id="gmail_checkbox_form">
                            @csrf
                        </form>
                        <table class="table">
                            <thead>
                            <tr>
                                <th><input type="checkbox" id="select-all-checkbox"/></th>
                                <th>#</th>
                                <th>From</th>
                                <th>Subject</th>
                                <th>PDF</th>
                                <th>Attachments</th>
                                <th>Filters</th>
                                <th width="120px">Date</th>
                                <th width="90px">Actions</th>
                            </tr>
                            </thead>
                            <tbody>

                            @foreach($gmails as $gmail)
                                <tr>
                                    <td><input type="checkbox" class="gmail-checkbox" name="gmailIds[]" form="gmail_checkbox_form" value="{{ $gmail->id }}"/></td>
                                    <td>{{ $gmail->id }}</td>
                                    <td title="{{ $gmail->from_email }}">{{ $gmail->from_name }}</td>
                                    <td><a href="{{ route('gmail.mailBody', $gmail->id) }}" target="_blank">{{ \Illuminate\Support\Str::limit($gmail->subject, 80)}}</a></td>
                                    <td>
                                    @if ($gmail->pdf_body_path)
                                            <a  class="btn btn-sm btn-outline-primary" title="pdf body" href="{{ route('gmail.downloadPdf', $gmail->id) }}"><i class="far fa-file-pdf"></i></a>
                                        @endif
                                    </td>
                                    <td>
                                        @if($gmail->attachments)
                                            @foreach($gmail->attachments as $attachment)
                                                <a href="{{ route('gmail.downloadAttachment', ['mailId' => $gmail->id, 'attachmentId' => $attachment['id']]) }}" title="Download {{ $attachment['file_name'] }} ({{ human_filesize($attachment['size']) }})">{{ $attachment['file_name'] }}</a><br>
                                            @endforeach
                                        @endif
                                    </td>
                                    <td>
                                        @if($gmail->gmailFilter)
                                            <a href="{{ route('gmailFilter.edit', $gmail->gmailFilter->id) }}">
                                                {{ $gmail->gmailFilter->name }}
                                            </a>
                                        @endif
                                    </td>
                                    <td>{{ $gmail->date->toDateTimeString() }}</td>
                                    <td>
                                        <form id="deleteForm{{ $gmail->id }}" method="POST" action="{{ route('gmail.delete', $gmail->id) }}" style="display:inline">
                                            <button class="btn btn-sm btn-default"
                                                    onclick="return confirm('Delete mail #{{ $gmail->id }}?')"
                                                    type="submit"
                                                    form="deleteForm{{ $gmail->id }}"
                                                    title="Remove #{{ $gmail->id }}"><i class="fas fa-trash-alt"></i></button>
                                            @csrf
                                        </form>
                                    </td>
                                </tr>
                            @endforeach

                            </tbody>
                        </table>
                        <div class="row">
                            <div class="col-md-2">
                                <span style="padding-left: 10px">
                                  <div class="btn btn-primary" id="dowload_button" style="cursor: pointer; display: none">
                                          <i title="Download Achive" class="fa fa-download" aria-hidden="true"></i>
                                 </div>
                                </span>
                                {{--<select id="checkbox_action" class="form-control">--}}
                                    {{--<option></option>--}}
                                    {{--<option value="zip">Download Zip</option>--}}
                                {{--</select>--}}
                            </div>
                        </div>

                        {{ $gmails->appends(request()->input())->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('scripts')
    <script>
        $(function() {
            toggleDownloadBtn();
            $(document).on('change', '#filterMenu', function () {
                if ($(this).val()) {
                    window.location.replace('http://catcher.local/gmails?filterId='+$(this).val());
                } else {
                    window.location.replace('http://catcher.local/gmails');
                }
            });

            $('#select-all-checkbox').click(function(event) {
                if(this.checked) {
                    // Iterate each checkbox
                    $('.gmail-checkbox:checkbox').each(function() {
                        this.checked = true;
                    });
                } else {
                    $('.gmail-checkbox:checkbox').each(function() {
                        this.checked = false;
                    });
                }
                toggleDownloadBtn();
            });

            $(document).on('click', '.gmail-checkbox', function () {
                toggleDownloadBtn();
            });

            $(document).on('click', '#dowload_button', function () {
                if ($(".gmail-checkbox:checkbox:checked").length > 0) {
                    $('#gmail_checkbox_form').submit();
                }
            });
        });

        function toggleDownloadBtn() {
            if ($(".gmail-checkbox:checkbox:checked").length > 0) {
                $('#dowload_button').show();
            } else {
                $('#dowload_button').hide();
            }
        }


    </script>
@endsection