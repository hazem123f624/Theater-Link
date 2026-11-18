<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Theaters\Http\Requests\StoreShowRequest;
use Modules\Theaters\Http\Requests\UpdateShowRequest;
use Modules\Theaters\Http\Resources\ShowResource;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Services\ShowService;

class ShowController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private ShowService $shows) {}

    public function show(int $show)
    {
        $show = Show::query()->findOrFail($show);

        return $this->success(
            __('theaters::messages.show_updated'),
            new ShowResource($show->load(['event.translations', 'hall', 'tiers.translations']))
        );
    }

    public function index(int $event)
    {
        $event = Event::query()->findOrFail($event);
        $shows = $event->shows()
            ->with(['hall', 'tiers.translations'])
            ->orderBy('starts_at')
            ->get();

        return $this->success(__('theaters::messages.shows_retrieved'), ShowResource::collection($shows));
    }

    public function store(StoreShowRequest $request, int $event)
    {
        $show = $this->shows->create(Event::query()->findOrFail($event), $request->validated());

        return $this->success(__('theaters::messages.show_created'), new ShowResource($show), 201);
    }

    public function update(UpdateShowRequest $request, int $show)
    {
        $show = $this->shows->update(Show::query()->findOrFail($show), $request->validated());

        return $this->success(__('theaters::messages.show_updated'), new ShowResource($show));
    }

    public function cancel(int $show)
    {
        return $this->success(__('theaters::messages.show_cancelled'), new ShowResource($this->shows->cancel(Show::query()->findOrFail($show))));
    }
}
