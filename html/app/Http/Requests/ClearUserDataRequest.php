<?php

namespace App\Http\Requests;

use App\Models\Gmail;
use App\Models\GmailFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClearUserDataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'models.*' => [
                Rule::in([
                    Gmail::class,
                    GmailFilter::class,
                ]),
            ],
        ];
    }
}
