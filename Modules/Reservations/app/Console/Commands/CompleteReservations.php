<?php

namespace Modules\Reservations\Console\Commands;

use Illuminate\Console\Command;
use Modules\Reservations\Services\ReservationService;

class CompleteReservations extends Command
{
    protected $signature = 'reservations:complete';

    protected $description = 'Mark approved reservations as completed after their end time.';

    public function handle(ReservationService $reservations): int
    {
        $count = $reservations->completeFinishedReservations();

        $this->info("Completed {$count} reservation(s).");

        return self::SUCCESS;
    }
}
