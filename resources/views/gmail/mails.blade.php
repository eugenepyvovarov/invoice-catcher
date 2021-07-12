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
                            <a href="{{ route('gmail.load', ['filter' => request('filter')]) }}" class="btn btn-primary">
                                {{ __('Load') }}
                            </a>
                        </div>
                        <div class="float-left">
                            <div class="row">
                                <div class="col-md-12">
                                    Filter:
                                    <select id="filterMenu" class="form-control">
                                        <option value="{{ route('gmail.mails') }}">-</option>
                                        @foreach($gmailFilters as $gmailFilter)
                                            <option value="{{ route('gmail.mails', ['filter' => $gmailFilter->id]) }}"
                                                    @if(request('filter') == $gmailFilter->id) selected @endif
                                            >{{ $gmailFilter->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
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
                    window.location.replace($(this).val());
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