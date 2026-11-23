<?php

namespace Modules\Reservations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Modules\Reservations\Http\Requests\CancelReservationRequest;
use Modules\Reservations\Http\Requests\ListReservationsRequest;
use Modules\Reservations\Http\Requests\RejectReservationRequest;
use Modules\Reservations\Http\Resources\ReservationResource;
use Modules\Reservations\Models\Reservation;
use Modules\Reservations\Services\ReservationService;

class TheaterReservationController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private ReservationService $reservations,
    ) {}

    public function index(ListReservationsRequest $request)
    {
        $tenant = $this->context->requireTenant();
        $data = $request->validated();
        $query = Reservation::query()
            ->where('theater_tenant_id', $tenant->id)
            ->with($this->relations())
            ->latest('id');

        if (! empty($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (! empty($data['search'])) {
            $search = $data['search'];
            $query->where(function ($builder) use ($search): void {
                $builder->where('reference', 'like', "%{$search}%")
                    ->orWhereHas('groupTenant', fn ($groupQuery) => $groupQuery->where('name', 'like', "%{$search}%"));
            });
        }

        $reservations = $query->paginate((int) ($data['per_page'] ?? 15));

        return $this->success(__('reservations::messages.reservations_retrieved'), data: [
            'data' => ReservationResource::collection($reservations->getCollection()),
            'pagination' => [
                'current_page' => $reservations->currentPage(),
                'last_page' => $reservations->lastPage(),
                'per_page' => $reservations->perPage(),
                'total' => $reservations->total(),
            ],
        ]);
    }

    public function show(int $reservation)
    {
        return $this->success(__('reservations::messages.reservation_retrieved'), new ReservationResource($this->owned($reservation)));
    }

    public function approve(int $reservation)
    {
        $updated = $this->reservations->approve($this->owned($reservation), $this->context->requireMember());

        return $this->success(__('reservations::messages.reservation_approved'), new ReservationResource($updated));
    }

    public function reject(RejectReservationRequest $request, int $reservation)
    {
        $updated = $this->reservations->reject(
            $this->owned($reservation),
            $this->context->requireMember(),
            $request->validated('reason')
        );

        return $this->success(__('reservations::messages.reservation_rejected'), new ReservationResource($updated));
    }

    public function cancel(CancelReservationRequest $request, int $reservation)
    {
        $updated = $this->reservations->cancelByTheater(
            $this->owned($reservation),
            $this->context->requireMember(),
            $request->validated('reason')
        );

        return $this->success(__('reservations::messages.reservation_cancelled'), new ReservationResource($updated));
    }

    private function owned(int $id): Reservation
    {
        return Reservation::query()
            ->where('theater_tenant_id', $this->context->requireTenant()->id)
            ->with($this->relations())
            ->findOrFail($id);
    }

    private function relations(): array
    {
        return ['theaterTenant.theaterProfile.logo', 'groupTenant.groupProfile.logo', 'hall', 'slot', 'requester', 'histories'];
    }
}
