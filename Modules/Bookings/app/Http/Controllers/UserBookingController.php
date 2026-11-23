<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Bookings\Http\Requests\CreateBookingRequest;
use Modules\Bookings\Http\Requests\ListBookingsRequest;
use Modules\Bookings\Http\Requests\UserCancelBookingRequest;
use Modules\Bookings\Http\Resources\BookingResource;
use Modules\Bookings\Services\BookingService;
use Modules\Users\Models\User;

class UserBookingController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private BookingService $bookings) {}

    public function index(ListBookingsRequest $request)
    {
        $bookings = $this->bookings->forUser($this->user($request), $request->validated());

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

    public function store(CreateBookingRequest $request)
    {
        $booking = $this->bookings->create($this->user($request), $request->validated());

        return $this->success(__('bookings::messages.booking_created'), new BookingResource($booking), 201);
    }

    public function show(ListBookingsRequest $request, int $booking)
    {
        $record = $this->bookings->findForUser($booking, $this->user($request));

        return $this->success(__('bookings::messages.booking_retrieved'), new BookingResource($record));
    }

    public function cancel(UserCancelBookingRequest $request, int $booking)
    {
        $record = $this->bookings->findForUser($booking, $this->user($request));
        $updated = $this->bookings->cancelByUser($record, $this->user($request), $request->validated('reason'));

        return $this->success(__('bookings::messages.booking_cancelled'), new BookingResource($updated));
    }

    private function user($request): User
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403, __('messages.unauthorized'));

        return $user;
    }
}
