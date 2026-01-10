<?php

namespace Database\Seeders;

use App\Models\Status;
use Illuminate\Database\Seeder;

class StatusSortOrderSeeder extends Seeder
{
    public function run(): void
    {
        // Business-friendly dropdown ordering
        $order = [
            'new'            => 10,

            'follow-up'      => 20,
            'meeting'        => 30,
            'waiting'        => 40,
            'booking'        => 50,

            'no-answer'      => 60,
            'not-interested' => 70,

            'reviewed'       => 80,
            'approved'       => 90,
            'pre-approved'   => 100,
            'sold'           => 110,

            'rejected'       => 120,
            'dead'           => 130,

            're-shuffled'    => 140,
            'duplicated'     => 150,

            // Tele variants موجودة عندك، رح نخلي لها ترتيب لكن رح تنفلتر من الـ API
            'new-tele'        => 1010,
            'follow-up-tele'  => 1020,
            'meeting-tele'    => 1030,
            'waiting-tele'    => 1040,
            'booking-tele'    => 1050,
            'dead-tele'       => 1060,
        ];

        foreach ($order as $slug => $sortOrder) {
            Status::query()
                ->where('slug', $slug)
                ->update(['sort_order' => $sortOrder]);
        }

        // Best practice: أي status غير موجود بالـ map (لو انضاف لاحقاً) ما يبقى NULL
        // حتى ما يطلع فجأة فوق/يتصرف بشكل غريب في الـ dropdown
        $max = Status::query()->max('sort_order') ?? 2000;

        Status::query()
            ->whereNull('sort_order')
            ->orderBy('id')
            ->get(['id', 'slug'])
            ->each(function (Status $s) use (&$max) {
                $max += 10;
                Status::query()->whereKey($s->id)->update(['sort_order' => $max]);
            });
    }
}
