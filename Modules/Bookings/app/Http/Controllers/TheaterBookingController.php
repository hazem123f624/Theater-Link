<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Bookings\Http\Requests\ListBookingsRequest;
use Modules\Bookings\Http\Requests\TheaterCancelBookingRequest;
use Modules\Bookings\Http\Resources\BookingResource;
use Modules\Bookings\Services\BookingService;

class TheaterBookingController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private BookingService $bookings,
    ) {}

    public function index(ListBookingsRequest $request)
    {
        $bookings = $this->bookings->forTheater($this->context->requireTenant(), $request->validated());

        return $this->success(__('bookings::messages.bookings_retrieved'), data: [
            'data' => BookingResource::collection($bookings->getCollection()),
            'pagination' => [
                'current_page' => $bookings->currentPage(),
                'last_page' => $bookings->lastPage(),
                'per_page' => $bookings->perPage(),
                'total' => $bookings->total(),
            ],
        ]);
    }

    public function show(int $booking)
    {
        $record = $this->bookings->findForTheater($booking, $this->context->requireTenant());

        return $this->success(__('bookings::messages.booking_retrieved'), new BookingResource($record));
    }

    public function cancel(TheaterCancelBookingRequest $request, int $booking)
    {
        $record = $this->bookings->findForTheater($booking, $this->context->requireTenant());
        $updated = $this->bookings->cancelByTheater(
            $record,
            $this->context->requireMember(),
            $request->validated('reason')
        );

        return $this->success(__('bookings::messages.booking_cancelled'), new BookingResource($updated));
    }
}
