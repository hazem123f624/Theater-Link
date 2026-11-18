<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Modules\Theaters\Http\Requests\StoreEventRequest;
use Modules\Theaters\Http\Requests\UpdateEventRequest;
use Modules\Theaters\Http\Resources\EventResource;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Services\EventService;

class EventController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private EventService $events) {}

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $query = Event::query()
            ->with(['translations', 'category.translations', 'poster'])
            ->withCount('shows')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->whereHas('translations', fn ($translation) => $translation->where('title', 'like', "%{$search}%"));
        }

        $events = $query->paginate($perPage);

        return $this->success(__('theaters::messages.events_retrieved'), data: [
            'data' => EventResource::collection($events->getCollection()),
            'pagination' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    public function store(StoreEventRequest $request, TenantContext $context)
    {
        $event = $this->events->create($context->requireTenant(), $request->validated(), $request->file('poster'));

        return $this->success(__('theaters::messages.event_created'), new EventResource($event), 201);
    }

    public function show(int $event)
    {
        $event = Event::query()->findOrFail($event);

        return $this->success(
            __('theaters::messages.event_retrieved'),
            new EventResource($event->load([
                'translations', 'category.translations', 'poster',
                'shows.hall', 'shows.tiers.translations',
            ]))
        );
    }

    public function update(UpdateEventRequest $request, int $event)
    {
        $event = $this->events->update(Event::query()->findOrFail($event), $request->validated(), $request->file('poster'));

        return $this->success(__('theaters::messages.event_updated'), new EventResource($event));
    }

    public function publish(int $event)
    {
        return $this->success(__('theaters::messages.event_published'), new EventResource($this->events->publish(Event::query()->findOrFail($event))));
    }

    public function unpublish(int $event)
    {
        return $this->success(__('theaters::messages.event_unpublished'), new EventResource($this->events->unpublish(Event::query()->findOrFail($event))));
    }

    public function cancel(int $event)
    {
        return $this->success(__('theaters::messages.event_cancelled'), new EventResource($this->events->cancel(Event::query()->findOrFail($event))));
    }
}
