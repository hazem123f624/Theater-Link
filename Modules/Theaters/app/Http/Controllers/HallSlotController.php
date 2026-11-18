<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Modules\Theaters\Http\Requests\ListHallSlotsRequest;
use Modules\Theaters\Http\Requests\StoreHallBlockRequest;
use Modules\Theaters\Http\Resources\HallBlockResource;
use Modules\Theaters\Http\Resources\HallSlotResource;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallBlock;
use Modules\Theaters\Models\HallTimeSlot;
use Modules\Theaters\Services\HallSlotService;

class HallSlotController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private HallSlotService $slots,
    ) {}

    public function index(ListHallSlotsRequest $request, int $hall)
    {
        $hall = Hall::query()->findOrFail($hall);
        $query = HallTimeSlot::query()
            ->where('hall_id', $hall->id)
            ->with(['hall.tenant.theaterProfile'])
            ->orderBy('starts_at');

        if ($request->filled('from')) {
            $query->where('starts_at', '>=', $request->date('from')->startOfDay());
        }

        if ($request->filled('to')) {
            $query->where('ends_at', '<=', $request->date('to')->endOfDay());
        }

        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        $slots = $query->limit(1000)->get();

        return $this->success(__('theaters::messages.slots_retrieved'), HallSlotResource::collection($slots));
    }

    public function blocks(int $hall)
    {
        $hall = Hall::query()->findOrFail($hall);
        $blocks = $hall->blocks()->latest('starts_at')->get();

        return $this->success(__('theaters::messages.slots_retrieved'), HallBlockResource::collection($blocks));
    }

    public function block(StoreHallBlockRequest $request, int $hall)
    {
        $block = $this->slots->block(Hall::query()->findOrFail($hall), $request->validated(), $this->context->requireMember()->id);

        return $this->success(__('theaters::messages.block_created'), new HallBlockResource($block), 201);
    }

    public function destroyBlock(int $block)
    {
        $this->slots->deleteBlock(HallBlock::query()->findOrFail($block));

        return $this->success(__('theaters::messages.block_deleted'));
    }

    public function generate(int $hall)
    {
        $count = $this->slots->generateForHall(Hall::query()->findOrFail($hall));

        return $this->success(__('theaters::messages.slots_retrieved'), ['generated' => $count]);
    }
}
