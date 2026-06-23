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
                        <form id="mail_load_form">
                            <div class="row">
                                <div class="col-md-12">
                                    Filter:
                                    <select id="filterMenu" name="filterId" class="form-control">
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
                                <div class="col-md-10 d-flex align-items-center flex-wrap" style="gap: 8px;">
                                    <button id="load_btn" type="button" class="btn btn-primary"
                                            data-url="{{ route('gmail.ajaxLoad') }}"
                                            data-status-url="{{ $filterId ? route('gmail.loadStatus', $filterId) : '' }}"
                                            @if(!empty($loadActive)) disabled @endif>
                                        {{ !empty($loadActive) ? __('Loading…') : __('Load') }}
                                    </button>
                                    <span id="loader" class="spinner-border spinner-border-sm text-primary" role="status"
                                          style="{{ !empty($loadActive) ? '' : 'display:none' }}" aria-hidden="true"></span>
                                    <small class="text-muted" id="load_hint">
                                        Large date ranges can take 1–2 minutes (Gmail rate limits + PDF/attachments).
                                        Prefer <code>after:2026/05/01 before:2026/06/01 in:anywhere</code> (YYYY/MM/DD).
                                    </small>
                                </div>
                            </div>
                            </form>
                        </div>
                        </div>

                        <div id="load_status_panel"
                             class="alert {{ !empty($loadActive) ? 'alert-info' : (isset($loadStatus['status']) && $loadStatus['status'] === 'done' ? 'alert-success' : (isset($loadStatus['status']) && $loadStatus['status'] === 'failed' ? 'alert-danger' : 'alert-secondary')) }}"
                             style="{{ !empty($loadActive) || (!empty($loadStatus['status']) && $loadStatus['status'] !== 'idle') ? '' : 'display:none' }}"
                             data-filter-id="{{ $filterId }}"
                             data-status-url="{{ $filterId ? route('gmail.loadStatus', $filterId) : '' }}"
                             data-poll="{{ !empty($loadActive) ? '1' : '0' }}">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <strong id="load_status_title">Gmail load</strong>
                                    <div id="load_status_message" class="mb-1">{{ $loadStatus['message'] ?? 'Starting…' }}</div>
                                    <small class="text-muted">
                                        Listed: <span id="load_listed_count">{{ $loadStatus['listed_count'] ?? 0 }}</span>
                                        · New this run: <span id="load_saved_count">{{ $loadStatus['saved_count'] ?? 0 }}</span>
                                        · In DB: <span id="load_row_count">{{ $gmails->total() }}</span>
                                    </small>
                                    <div id="load_status_error" class="text-danger mt-1" style="{{ !empty($loadStatus['error']) ? '' : 'display:none' }}">
                                        {{ $loadStatus['error'] ?? '' }}
                                    </div>
                                </div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="refresh_mails_btn" title="Refresh mail list">
                                    Refresh list
                                </button>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                <div id="load_progress_bar" class="progress-bar progress-bar-striped progress-bar-animated"
                                     role="progressbar" style="width: {{ !empty($loadActive) ? '100%' : '0%' }}"></div>
                            </div>
                        </div>

                        <div class="clearfix"></div>
                        <div class="row">
                            <div class="col-md-2">
                                <span>
                                  <div class="btn btn-primary dowload-button" style="cursor: pointer; margin-bottom: 10px; display: none">
                                          <i title="Download Achive" class="fa fa-download" aria-hidden="true"></i>
                                 </div>
                                </span>
                            </div>
                        </div>
                        Rows: <span id="rows_total">{{ $gmails->total() }}</span>
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
                                    <td><a href="{{ route('gmail.mailBody', $gmail->id) }}" target="_blank">{{ \Illuminate\Support\Str::limit($gmail->clean_subject, 80)}}</a></td>
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

                                    <td>{{ $gmail->clean_date?->toDateTimeString() }}</td>
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
                                  <div class="btn btn-primary dowload-button" style="cursor: pointer; display: none">
                                          <i title="Download Achive" class="fa fa-download" aria-hidden="true"></i>
                                 </div>
                                </span>
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
            var pollTimer = null;
            var lastRowCount = {{ (int) $gmails->total() }};
            var statusUrlTemplate = @json($filterId ? route('gmail.loadStatus', ['filterId' => '__ID__']) : '');

            toggleDownloadBtn();

            $(document).on('change', '#filterMenu', function () {
                if ($(this).val()) {
                    window.location.replace('{{ route('gmail.mails') }}?filterId='+$(this).val());
                } else {
                    window.location.replace('{{ route('gmail.mails') }}');
                }
            });

            $('#refresh_mails_btn').on('click', function () {
                var fid = $('#filterMenu').val() || '{{ $filterId }}';
                var url = '{{ route('gmail.mails') }}' + (fid ? ('?filterId=' + fid) : '');
                window.location.replace(url);
            });

            $('#load_btn').click(function(event) {
                event.preventDefault();
                var $btn = $(this);
                if ($btn.prop('disabled')) {
                    return;
                }

                $btn.prop('disabled', true).text('Starting…');
                $('#loader').show();
                $('#load_status_panel').show().removeClass('alert-success alert-danger alert-secondary').addClass('alert-info');
                $('#load_status_title').text('Gmail load');
                $('#load_status_message').text('Starting import in the background…');
                $('#load_status_error').hide().text('');
                $('#load_progress_bar').css('width', '100%').addClass('progress-bar-animated progress-bar-striped');

                $.ajax({
                    headers: {
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                    },
                    url: $btn.data('url'),
                    type: 'POST',
                    data: $('#mail_load_form').serializeArray(),
                    success: function(data) {
                        if (data.redirect_url) {
                            // Land on mails page with loading=1; background work continues via afterResponse
                            window.location.replace(data.redirect_url);
                            return;
                        }
                        startPolling(data.filter_id);
                    },
                    error: function(xhr) {
                        $('#loader').hide();
                        $btn.prop('disabled', false).text('Load');
                        $('#load_status_panel').removeClass('alert-info').addClass('alert-danger').show();
                        var msg = (xhr.responseJSON && (xhr.responseJSON.message || xhr.responseJSON.error)) || xhr.statusText || 'Request failed';
                        $('#load_status_message').text('Could not start load.');
                        $('#load_status_error').text(msg).show();
                        $('#load_progress_bar').removeClass('progress-bar-animated').css('width', '0%');
                    }
                });
            });

            if ($('#load_status_panel').data('poll') == '1') {
                var fid = $('#load_status_panel').data('filter-id');
                if (fid) {
                    startPolling(fid);
                }
            }

            function statusUrlFor(filterId) {
                var base = $('#load_status_panel').data('status-url');
                if (base) {
                    return base;
                }
                if (statusUrlTemplate && filterId) {
                    return statusUrlTemplate.replace('__ID__', filterId);
                }
                return null;
            }

            function startPolling(filterId) {
                var url = statusUrlFor(filterId);
                if (!url) {
                    return;
                }
                if (pollTimer) {
                    clearInterval(pollTimer);
                }
                $('#loader').show();
                $('#load_btn').prop('disabled', true).text('Loading…');
                pollOnce(url);
                pollTimer = setInterval(function () { pollOnce(url); }, 2000);
            }

            function pollOnce(url) {
                $.getJSON(url)
                    .done(function (status) {
                        applyStatus(status);
                        if (!status.active) {
                            if (pollTimer) {
                                clearInterval(pollTimer);
                                pollTimer = null;
                            }
                            $('#loader').hide();
                            $('#load_btn').prop('disabled', false).text('Load');
                            $('#load_progress_bar').removeClass('progress-bar-animated progress-bar-striped');

                            if (status.status === 'done') {
                                $('#load_progress_bar').css('width', '100%');
                                // Auto-refresh list once when finished so new rows appear without manual click
                                var fid = status.filter_id || $('#filterMenu').val();
                                var target = '{{ route('gmail.mails') }}' + (fid ? ('?filterId=' + fid + '&loaded=1') : '?loaded=1');
                                if ((status.row_count || 0) !== lastRowCount || status.saved_count > 0) {
                                    setTimeout(function () { window.location.replace(target); }, 600);
                                }
                            } else if (status.status === 'failed') {
                                $('#load_progress_bar').css('width', '100%').addClass('bg-danger');
                            }
                        } else {
                            // Soft-refresh row count in banner only
                            if (typeof status.row_count !== 'undefined') {
                                $('#load_row_count').text(status.row_count);
                                $('#rows_total').text(status.row_count);
                            }
                        }
                    })
                    .fail(function () {
                        // keep polling; transient errors are ok
                    });
            }

            function applyStatus(status) {
                if (!status) return;
                $('#load_status_panel').show();
                $('#load_status_message').text(status.message || status.status || '…');
                $('#load_listed_count').text(status.listed_count || 0);
                $('#load_saved_count').text(status.saved_count || 0);
                if (typeof status.row_count !== 'undefined') {
                    $('#load_row_count').text(status.row_count);
                }
                if (status.error) {
                    $('#load_status_error').text(status.error).show();
                } else {
                    $('#load_status_error').hide().text('');
                }

                var panel = $('#load_status_panel');
                panel.removeClass('alert-info alert-success alert-danger alert-secondary');
                if (status.active) {
                    panel.addClass('alert-info');
                    $('#load_progress_bar').addClass('progress-bar-animated progress-bar-striped bg-primary').removeClass('bg-danger').css('width', '100%');
                } else if (status.status === 'done') {
                    panel.addClass('alert-success');
                    $('#load_progress_bar').removeClass('bg-danger').addClass('bg-success').css('width', '100%');
                } else if (status.status === 'failed') {
                    panel.addClass('alert-danger');
                } else {
                    panel.addClass('alert-secondary');
                }
            }

            $('#select-all-checkbox').click(function(event) {
                if(this.checked) {
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

            $(document).on('click', '.dowload-button', function () {
                if ($(".gmail-checkbox:checkbox:checked").length > 0) {
                    $('#gmail_checkbox_form').submit();
                }
            });
        });

        function toggleDownloadBtn() {
            if ($(".gmail-checkbox:checkbox:checked").length > 0) {
                $('.dowload-button').show();
            } else {
                $('.dowload-button').hide();
            }
        }
    </script>
@endsection
