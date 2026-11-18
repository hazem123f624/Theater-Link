<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use Modules\Theaters\Http\Requests\StoreHallScheduleRequest;
use Modules\Theaters\Http\Requests\UpdateHallScheduleRequest;
use Modules\Theaters\Http\Resources\HallScheduleResource;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Models\HallSchedule;
use Modules\Theaters\Services\HallScheduleService;

class HallScheduleController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(private HallScheduleService $schedules) {}

    public function index(int $hall)
    {
        $hall = Hall::query()->findOrFail($hall);
        $schedules = $hall->schedules()->orderBy('day_of_week')->orderBy('starts_at')->get();

        return $this->success(__('theaters::messages.schedules_retrieved'), HallScheduleResource::collection($schedules));
    }

    public function store(StoreHallScheduleRequest $request, int $hall)
    {
        $schedule = $this->schedules->create(Hall::query()->findOrFail($hall), $request->validated());

        return $this->success(__('theaters::messages.schedule_created'), new HallScheduleResource($schedule), 201);
    }

    public function update(UpdateHallScheduleRequest $request, int $schedule)
    {
        $schedule = $this->schedules->update(HallSchedule::query()->findOrFail($schedule), $request->validated());

        return $this->success(__('theaters::messages.schedule_updated'), new HallScheduleResource($schedule));
    }

    public function destroy(int $schedule)
    {
        $this->schedules->delete(HallSchedule::query()->findOrFail($schedule));

        return $this->success(__('theaters::messages.schedule_deleted'));
    }
}
