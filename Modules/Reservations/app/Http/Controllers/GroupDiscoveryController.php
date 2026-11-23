<?php

namespace Modules\Reservations\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Illuminate\Http\Request;
use Modules\Theaters\Http\Requests\PublicAvailabilityRequest;
use Modules\Theaters\Http\Resources\HallSlotResource;
use Modules\Theaters\Http\Resources\PublicHallResource;
use Modules\Theaters\Http\Resources\PublicTheaterResource;
use Modules\Theaters\Services\PublicCatalogService;

class GroupDiscoveryController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private PublicCatalogService $catalog) {}

    public function theaters(Request $request)
    {
        $theaters = $this->catalog->theaters(max(1, min((int) $request->query('per_page', 15), 100)));

        return $this->success(__('reservations::messages.theaters_retrieved'), data: [
            'data' => PublicTheaterResource::collection($theaters->getCollection()),
            'pagination' => [
                'current_page' => $theaters->currentPage(),
                'last_page' => $theaters->lastPage(),
                'per_page' => $theaters->perPage(),
                'total' => $theaters->total(),
            ],
        ]);
    }

    public function theater(int $theater)
    {
        return $this->success(__('reservations::messages.theater_retrieved'), new PublicTheaterResource($this->catalog->theater($theater)));
    }

    public function halls(int $theater)
    {
        $record = $this->catalog->theater($theater);

        return $this->success(__('reservations::messages.theater_retrieved'), PublicHallResource::collection($record->halls));
    }

    public function availability(PublicAvailabilityRequest $request, int $hall)
    {
        $hallModel = $this->catalog->findHall($hall);
        $slots = $this->catalog->availability($hallModel, $request->validated('from'), $request->validated('to'));

        return $this->success(__('reservations::messages.availability_retrieved'), HallSlotResource::collection($slots));
    }
}
