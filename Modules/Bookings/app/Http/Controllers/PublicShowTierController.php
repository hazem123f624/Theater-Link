<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Bookings\Http\Resources\PublicTicketTierResource;
use Modules\Bookings\Services\BookingService;

class PublicShowTierController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private BookingService $bookings) {}

    public function show(int $show)
    {
        $record = $this->bookings->publicShow($show);

        return $this->success(__('bookings::messages.tiers_retrieved'), [
            'show' => [
                'id' => $record->id,
                'starts_at' => $record->starts_at,
                'ends_at' => $record->ends_at,
                'status' => $record->status,
            ],
            'tiers' => PublicTicketTierResource::collection($record->tiers),
        ]);
    }
}
