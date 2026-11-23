<?php

namespace Modules\Reservations\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reservations\Services\ReservationService;

class ExpireReservationHolds extends Command
{
    protected $signature = 'reservations:expire-holds';

    protected $description = 'Expire pending reservation holds past their response window.';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->expirePendingHolds();

        $this->info("Expired {$count} reservation hold(s).");

        return self::SUCCESS;
    }
}
