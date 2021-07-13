<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GmailFilterRequest extends FormRequest
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
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => ['required'],
            'filter' => ['nullable'],
            'regex' => ['string', 'nullable'],
            'is_default' => ['boolean', 'nullable'],
            'reload_data' => ['boolean', 'nullable'],
        ];
    }
}
