<?php

namespace App\Http\Controllers;

use App\Http\Traits\HttpResponsesTrait;
use App\Services\Notifications\Models\AppNotification;
use Illuminate\Http\Request;
use Modules\Tenancy\Resources\NotificationResource;

class NotificationController extends Controller
{
    use HttpResponsesTrait;

    public function index(Request $request)
    {
        $user = $request->user();
        $query = AppNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->latest();

        $perPage = max(1, min((int) $request->query('per_page', 20), 100));
        $notifications = $query->paginate($perPage);

        return $this->success(__('messages.data_retrieved_successfully'), data: [
            'data' => NotificationResource::collection($notifications->getCollection()),
            'unread_count' => AppNotification::query()
                ->where('notifiable_type', $user->getMorphClass())
                ->where('notifiable_id', $user->getKey())
                ->whereNull('read_at')
                ->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function read(Request $request, AppNotification $notification)
    {
        $this->ensureOwned($request, $notification);

        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        return $this->success(__('messages.data_updated_successfully'), new NotificationResource($notification));
    }

    public function readAll(Request $request)
    {
        $user = $request->user();

        AppNotification::query()
            ->where('notifiable_type', $user->getMorphClass())
            ->where('notifiable_id', $user->getKey())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return $this->success(__('messages.data_updated_successfully'));
    }

    private function ensureOwned(Request $request, AppNotification $notification): void
    {
        $user = $request->user();

        if ($notification->notifiable_type !== $user->getMorphClass()
            || (int) $notification->notifiable_id !== (int) $user->getKey()) {
            abort(404, __('messages.resource_not_found'));
        }
    }
}
