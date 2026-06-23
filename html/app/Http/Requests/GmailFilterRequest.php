<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GmailFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
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
