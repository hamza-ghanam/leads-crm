<?php

namespace App\Adapters;

use Illuminate\Http\Request;

class LeadsFilterAdapter
{
    public static function fromApi(Request $request): array
    {
        return [
            // Web expects these exact keys
            'status' => $request->query('status', 'all'),

            // web uses 'sale' as user_id
            'sale' => $request->query('assigned_to', 'all'),

            // date range
            'from' => $request->query('date_from'),
            'to' => $request->query('date_to'),

            // other filters the helper expects
            'platform' => $request->query('platform'),
            'source' => $request->query('source'),

            // fallback defaults if helper relies on them
            'search' => $request->query('search'),
        ];
    }
}
