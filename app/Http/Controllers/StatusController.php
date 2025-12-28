<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class StatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin');
    }

    public function index(Request $request)
    {
        $statuses = Status::query()
            ->where('slug', 'not like', '%-tele')
            ->orderBy('id')
            ->get();

        return view('statuses.durations', compact('statuses'));
    }

    public function saveDurations(Request $request)
    {
        $validated = $request->validate([
            'statuses' => ['required', 'array'],
            'statuses.*.id' => ['required', 'integer', 'exists:statuses,id'],
            'statuses.*.duration' => ['nullable', 'integer', 'min:1'],
            'statuses.*.unit' => ['required', 'in:hour,day'],
        ]);

        $payload = $validated['statuses'];

        $ids = collect($payload)->pluck('id')->unique()->values();

        $allowedIds = Status::query()
            ->whereIn('id', $ids)
            ->where('slug', 'not like', '%-tele')
            ->pluck('id')
            ->all();

        DB::transaction(function () use ($payload, $allowedIds) {
            $allowedIds = array_flip($allowedIds);

            foreach ($payload as $row) {
                $id = (int) $row['id'];

                if (! isset($allowedIds[$id])) {
                    continue;
                }

                if (empty($row['duration']) || empty($row['unit'])) {
                    Status::where('id', $id)->update([
                        'duration' => null,
                    ]);
                    continue;
                }

                $value = (int) $row['duration']
                    . ($row['unit'] === 'hour' ? 'h' : 'd');

                Status::where('id', $id)->update([
                    'duration' => $value, // مثال: 3h أو 4d
                ]);
            }

        });

        return redirect()
            ->route('statuses.durations')
            ->with('success', 'Status durations updated successfully.');
    }
}
