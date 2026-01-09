<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadUpdateRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'full_name'      => ['sometimes', 'string', 'max:255'],
            'phone_number'   => ['sometimes', 'string', 'max:20'],
            'email'          => ['nullable', 'email'],
            'campaign_name'  => ['nullable', 'string'],
            'source_id'      => ['nullable', 'exists:sources,id'],
            'preferred_time' => ['nullable', 'string'],
            'remarks'        => ['nullable', 'string'],
            'extra_data'     => ['nullable', 'array'],
        ];
    }
}
