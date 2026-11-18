<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Theaters\Http\Requests\PublicAvailabilityRequest;
use Modules\Theaters\Http\Requests\PublicEventListRequest;
use Modules\Theaters\Http\Resources\EventCategoryResource;
use Modules\Theaters\Http\Resources\HallSlotResource;
use Modules\Theaters\Http\Resources\PublicEventResource;
use Modules\Theaters\Http\Resources\PublicHallResource;
use Modules\Theaters\Http\Resources\PublicTheaterResource;
use Modules\Theaters\Models\EventCategory;
use Modules\Theaters\Services\PublicCatalogService;

class PublicCatalogController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private PublicCatalogService $catalog) {}

    public function theaters()
    {
        $theaters = $this->catalog->theaters(max(1, min((int) request()->query('per_page', 15), 100)));

        return $this->success(__('theaters::messages.public_theaters_retrieved'), data: [
            'data' => PublicTheaterResource::collection($theaters->getCollection()),
            'pagination' => [
                'current_page' => $theaters->currentPage(),
                'last_page' => $theaters->lastPage(),
                'per_page' => $theaters->perPage(),
                'total' => $theaters->total(),
            ],
        ]);
    }

    public function theater(int $id)
    {
        return $this->success(__('theaters::messages.public_theater_retrieved'), new PublicTheaterResource($this->catalog->theater($id)));
    }

    public function halls(int $id)
    {
        $theater = $this->catalog->theater($id);

        return $this->success(__('theaters::messages.public_theater_retrieved'), PublicHallResource::collection($theater->halls));
    }

    public function availability(PublicAvailabilityRequest $request, int $hall)
    {
        $hallModel = $this->catalog->findHall($hall);
        $slots = $this->catalog->availability($hallModel, $request->validated('from'), $request->validated('to'));

        return $this->success(__('theaters::messages.availability_retrieved'), HallSlotResource::collection($slots));
    }

    public function events(PublicEventListRequest $request)
    {
        $perPage = max(1, min((int) ($request->validated('per_page') ?? 15), 100));
        $events = $this->catalog->events($request->validated(), $perPage);

        return $this->success(__('theaters::messages.public_events_retrieved'), data: [
            'data' => PublicEventResource::collection($events->getCollection()),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function event(string $slug)
    {
        return $this->success(__('theaters::messages.public_event_retrieved'), new PublicEventResource($this->catalog->event($slug)));
    }

    public function categories()
    {
        $categories = EventCategory::query()
            ->where('is_active', true)
            ->with('translations')
            ->orderBy('slug')
            ->get();

        return $this->success(__('theaters::messages.public_events_retrieved'), EventCategoryResource::collection($categories));
    }
}
