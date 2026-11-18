<?php

namespace Modules\Theaters\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Http\Traits\HttpResponsesTrait;
use App\Services\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Modules\Theaters\Http\Requests\StoreHallRequest;
use Modules\Theaters\Http\Requests\UpdateHallRequest;
use Modules\Theaters\Http\Resources\HallResource;
use Modules\Theaters\Models\Hall;
use Modules\Theaters\Services\HallService;

class HallController extends Controller
{
    use HttpResponsesTrait;

    public function __construct(
        private TenantContext $context,
        private HallService $halls,
    ) {}

    public function index(Request $request)
    {
        $perPage = max(1, min((int) $request->query('per_page', 15), 100));
        $query = Hall::query()->withCount('schedules')->latest('id');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->string('search').'%');
        }

        if ($request->filled('active')) {
            $query->where('is_active', $request->boolean('active'));
        }

        $halls = $query->paginate($perPage);

        return $this->success(__('theaters::messages.halls_retrieved'), data: [
            'data' => HallResource::collection($halls->getCollection()),
            'pagination' => [
                'current_page' => $halls->currentPage(),
                'last_page' => $halls->lastPage(),
                'per_page' => $halls->perPage(),
                'total' => $halls->total(),
            ],
        ]);
    }

    public function store(StoreHallRequest $request)
    {
        $hall = $this->halls->create($this->context->requireTenant(), $request->validated());

        return $this->success(__('theaters::messages.hall_created'), new HallResource($hall), 201);
    }

    public function show(int $hall)
    {
        $hall = Hall::query()->findOrFail($hall);

        return $this->success(__('theaters::messages.hall_retrieved'), new HallResource($hall->loadCount('schedules')));
    }

    public function update(UpdateHallRequest $request, int $hall)
    {
        $hall = $this->halls->update(Hall::query()->findOrFail($hall), $request->validated());

        return $this->success(__('theaters::messages.hall_updated'), new HallResource($hall));
    }

    public function destroy(int $hall)
    {
        $this->halls->delete(Hall::query()->findOrFail($hall));

        return $this->success(__('theaters::messages.hall_deleted'));
    }
}
