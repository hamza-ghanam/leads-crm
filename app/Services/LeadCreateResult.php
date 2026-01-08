<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketPath;

class LeadCreateResult
{
    public function __construct(
        public Ticket $lead,
        public bool $isDuplicated,
        public ?TicketPath $initialPath = null,
    ) {}
}
