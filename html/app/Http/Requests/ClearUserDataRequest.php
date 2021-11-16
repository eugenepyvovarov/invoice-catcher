<?php

namespace App\Http\Requests;

use App\Models\Gmail;
use App\Models\GmailFilter;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClearUserDataRequest extends FormRequest
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
            'models.*' => [
                Rule::in([
                    Gmail::class,
                    GmailFilter::class,
            ])]
        ];
    }
}
