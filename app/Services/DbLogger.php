<?php

namespace App\Services;

use App\Models\DbLog;
use Illuminate\Http\Request;

class DbLogger
{
    public static function log(
        string $level,
        string $message,
        ?string $category = null,
        ?string $action = null,
        array $context = [],
        Request $request = null
    ): void {
        try {
            $request ??= request();

            DbLog::create([
                'level'      => $level,
                'category'   => $category,
                'action'     => $action,
                'message'    => $message,
                'context'    => $context,
                'meta'       => [
                    'user_agent' => $request->userAgent(),
                ],
                'user_id'    => optional($request->user())->id,
                'ip_address' => $request->ip(),
                'route'      => optional($request->route())->getName(),
                'method'     => $request->method(),
            ]);
        } catch (\Throwable $e) {
            // لا تفجّر النظام بسبب log
        }
    }
}
