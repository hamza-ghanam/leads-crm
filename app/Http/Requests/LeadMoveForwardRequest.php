<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeadMoveForwardRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Authorization is handled by LeadAccess in controller/service
        return true;
    }

    public function rules(): array
    {
        return [
            'status_id'   => ['required', 'integer', 'gt:0', 'exists:statuses,id'],
            'assigned_to' => ['nullable', 'integer', 'gt:0', 'exists:users,id'],
            'comment'     => ['required', 'string'],

            // Booking fields (conditionally validated in service as well)
            'client_unit'      => ['nullable', 'string'],
            'client_price'     => ['nullable', 'numeric', 'gt:0'],
            'client_project'   => ['nullable', 'string'],
            'client_developer' => ['nullable', 'string'],

            // Follow-up reminder
            'reminder_datetime' => ['nullable', 'date', 'after:now'],

            // Meeting range (web-compatible)
            'meeting_range' => ['nullable', 'string'],
        ];
    }
}
