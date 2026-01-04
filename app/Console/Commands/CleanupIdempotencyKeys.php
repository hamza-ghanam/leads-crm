<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CleanupIdempotencyKeys extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'idempotency:cleanup';

    /**
     * The console command description.
     */
    protected $description = 'Delete idempotency keys older than 24 hours';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $deleted = DB::table('idempotency_keys')
            ->where('created_at', '<', now()->subHours(24))
            ->delete();

        $this->info("Deleted {$deleted} expired idempotency keys.");

        return Command::SUCCESS;
    }
}
