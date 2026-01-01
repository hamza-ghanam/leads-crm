<?php

namespace App\Http\Controllers;

use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Throwable;

class StatusController extends Controller
{
    public function __construct()
    {
        $this->middleware('role:super-admin');
    }

    public function index(Request $request)
    {
        $statuses = Status::reassignable()->orderBy('id')->get();
        return view('statuses.durations', compact('statuses'));
    }

    /**
     * @throws Throwable
     */
    public function saveDurations(Request $request)
    {
        $validated = $request->validate([
            'statuses' => ['required', 'array'],
            'statuses.*.id' => ['required', 'integer', 'exists:statuses,id'],
            'statuses.*.duration' => ['nullable', 'integer', 'min:1'],
            'statuses.*.unit' => ['required', 'in:hour,day', 'required_with:statuses.*.duration'],
        ]);

        $payload = $validated['statuses'];

        $ids = collect($payload)->pluck('id')->unique()->values();

        $allowedIds = Status::reassignable()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->all();


        DB::transaction(function () use ($payload, $allowedIds) {
            $allowedIds = array_flip($allowedIds);

            foreach ($payload as $row) {
                $id = (int)$row['id'];

                if (!isset($allowedIds[$id])) {
                    continue;
                }

                if (!filled($row['duration'] ?? null) || !filled($row['unit'] ?? null)) {
                    Status::where('id', $id)->update([
                        'duration' => null,
                    ]);
                    continue;
                }

                $value = (int)$row['duration']
                    . ($row['unit'] === 'hour' ? 'h' : 'd');

                Status::where('id', $id)->update([
                    'duration' => $value, // مثال: 3h أو 4d
                ]);
            }

        });

        return redirect()
            ->route('settings.status')
            ->with('success', 'Status durations updated successfully.');
    }
}
