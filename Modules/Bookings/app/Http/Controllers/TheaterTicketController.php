<?php

namespace Modules\Bookings\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Bookings\Http\Requests\ValidateTicketRequest;
use Modules\Bookings\Http\Resources\TicketResource;
use Modules\Bookings\Services\BookingService;

class TheaterTicketController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private BookingService $bookings,
    ) {}

    public function validate(ValidateTicketRequest $request)
    {
        $ticket = $this->bookings->validateTicket(
            $this->context->requireTenant(),
            $this->context->requireMember(),
            $request->validated('qr_token'),
        );

        return $this->success(__('bookings::messages.ticket_validated'), new TicketResource($ticket));
    }
}
