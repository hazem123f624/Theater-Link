<?php

namespace Modules\Bookings\Console\Commands;

use Illuminate\Console\Command;
use Modules\Bookings\Services\BookingService;

class ExpireTickets extends Command
{
    protected $signature = 'bookings:expire-tickets';

    protected $description = 'Mark unused tickets as expired after their show ends.';

    public function handle(BookingService $bookings): int
    {
        $count = $bookings->expireTickets();

        $this->info("Expired {$count} ticket(s).");

        return self::SUCCESS;
    }
}
