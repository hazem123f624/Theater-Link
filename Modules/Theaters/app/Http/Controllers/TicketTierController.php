<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Theaters\Http\Requests\StoreTicketTierRequest;
use Modules\Theaters\Http\Requests\UpdateTicketTierRequest;
use Modules\Theaters\Http\Resources\TicketTierResource;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Models\TicketTier;
use Modules\Theaters\Services\TicketTierService;

class TicketTierController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private TicketTierService $tiers) {}

    public function index(int $show)
    {
        $show = Show::query()->findOrFail($show);
        $tiers = $show->tiers()->with('translations')->orderBy('price')->get();

        return $this->success(__('theaters::messages.tiers_retrieved'), TicketTierResource::collection($tiers));
    }

    public function store(StoreTicketTierRequest $request, int $show)
    {
        $tier = $this->tiers->create(Show::query()->findOrFail($show), $request->validated());

        return $this->success(__('theaters::messages.tier_created'), new TicketTierResource($tier), 201);
    }

    public function update(UpdateTicketTierRequest $request, int $tier)
    {
        $tier = $this->tiers->update(TicketTier::query()->findOrFail($tier), $request->validated());

        return $this->success(__('theaters::messages.tier_updated'), new TicketTierResource($tier));
    }

    public function destroy(int $tier)
    {
        return $this->success(__('theaters::messages.tier_deactivated'), new TicketTierResource($this->tiers->deactivate(TicketTier::query()->findOrFail($tier))));
    }
}
