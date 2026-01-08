<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        return $user && $user->hasAnyRole(['admin', 'super-admin']);
    }

    public function rules(): array
    {
        return [
            'campaign_name'   => ['nullable', 'string', 'max:255'],
            'full_name'       => ['required', 'string', 'max:255'],
            'email'           => ['nullable', 'email', 'max:255'],
            'phone_number'    => ['required', 'string', 'max:50'],
            'source_id'       => ['required', 'integer', 'exists:sources,id'],
            'assigned_to'     => ['required', 'integer', 'exists:users,id'],

            'preferred_time'  => ['nullable', 'string', 'max:255'], // أو date/time حسب عندك
            'remarks'         => ['nullable', 'string'],
        ];
    }
}
