@csrf
<div class="form-group row">
    <label for="person" class="col-md-10 col-form-label">{{ __('Person') }}</label>
    <div class="col-md-10">
        <input id="person" type="text" class="form-control @error('person') is-invalid @enderror" name="person" value="{{ old('person', $ticket->person) }}" autofocus>
        @error('person')
        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
        @enderror
    </div>
</div>
<div class="form-group row">
    <label for="train_number" class="col-md-10 col-form-label">{{ __('Train Number') }}*</label>
    <div class="col-md-10">
        <input id="train_number" type="text" class="form-control @error('train_number') is-invalid @enderror" name="train_number" value="{{ old('train_number', $ticket->train_number) }}" required autofocus>
        @error('train_number')
        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
        @enderror
    </div>
</div>
<div class="form-group row">
    <label for="from_station" class="col-md-10 col-form-label">{{ __('From Station') }}*</label>
    <div class="col-md-10">
        <select required class="form-control select2-ajax-stations @error('from_station') is-invalid @enderror" name="from_station" id="from_station">
            <option value="{{ old('from_station', $ticket->from_station) }}" selected>{{ old('from_station', $ticket->from_station) }}</option>
        </select>
        @error('from_station')
        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
        @enderror
    </div>
</div>
<div class="form-group row">
    <label for="departure_at" class="col-md-10 col-form-label">{{ __('Scheduled Departure Time') }}*</label>
    <div class="col-md-10">
        <input id="departure_at" type="text" placeholder="yyyy/mm/dd H:m" class="form-control mask-date-time  @error('departure_at') is-invalid @enderror" name="departure_at" value="{{ old('departure_at', $ticket->departure_at) }}"  required autofocus>
        @error('departure_at')
        <span class="invalid-feedback" role="alert">
            <strong>{{ $message }}</strong>
        </span>
        @enderror
    </div>
</div>
<div class="form-group row">
    <label for="to_station" class="col-md-10 col-form-label">{{ __('To Station') }}*</label>
    <div class="col-md-10">
        <select required class="form-control select2-ajax-stations @error('to_station') is-invalid @enderror" name="to_station" id="to_station">
            <option value="{{ old('to_station', $ticket->to_station) }}" selected>{{ old('to_station', $ticket->to_station) }}</option>
        </select>
        @error('to_station')
        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
        @enderror
    </div>
</div>
<div class="form-group row">
    <label for="arrival_at" class="col-md-10 col-form-label">{{ __('Scheduled Arrival Time') }}*</label>
    <div class="col-md-10">
        <input id="arrival_at" type="text" placeholder="yyyy/mm/dd H:m" class="form-control mask-date-time  @error('arrival_at') is-invalid @enderror" name="arrival_at" value="{{ old('arrival_at', $ticket->arrival_at) }}" required autofocus>
        @error('arrival_at')
        <span class="invalid-feedback" role="alert">
          <strong>{{ $message }}</strong>
                                    </span>
        @enderror
    </div>
</div>