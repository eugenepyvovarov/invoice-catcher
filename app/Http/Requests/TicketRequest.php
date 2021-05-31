<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TicketRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'departure_at.date_format' => 'The :attribute does not match the format yyyy/mm/dd h:m.',
            'arrival_at.date_format' => 'The :attribute does not match the format yyyy/mm/dd h:m.',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {

        return [
            'person' => 'string|nullable',
            'from_station' => 'required|string',
            'to_station' => 'required|string',
            'train_number' => 'required|string',
            'departure_at' => 'required|date_format:Y/m/d H:i',
            'arrival_at' => 'required|date_format:Y/m/d H:i',
        ];
    }
}
