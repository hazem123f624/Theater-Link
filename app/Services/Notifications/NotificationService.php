<?php

namespace App\Services\Notifications;

use App\Services\Notifications\Models\AppNotification;
use App\Jobs\SendNotificationChannelsJob;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Admins\Models\Admins;
use Modules\Tenancy\Models\GroupApplication;
use Modules\Tenancy\Models\TenantMember;

class NotificationService
{
    public function notifyAdmins(string $type, string $title, string $body, array $data = []): void
    {
        $admins = Admins::query()
            ->where('is_active', true)
            ->get()
            ->filter(fn (Admins $admin): bool => $admin->hasPermission('group_applications-fetch'));

        if ($admins->isEmpty()) {
            $admins = Admins::query()->where('is_active', true)->get();
        }

        foreach ($admins as $admin) {
            $this->notify($admin, $type, $title, $body, $data);
        }
    }

    public function notifyGroupOwner(
        GroupApplication $application,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): void {
        $application->loadMissing('tenant.members');
        $owner = $application->tenant->members->firstWhere('is_owner', true);

        if ($owner) {
            $this->notify($owner, $type, $title, $body, $data);
        }
    }

    public function notify(
        Model $notifiable,
        string $type,
        string $title,
        string $body,
        array $data = []
    ): AppNotification {
        $notification = AppNotification::create([
            'notifiable_type' => $notifiable->getMorphClass(),
            'notifiable_id' => $notifiable->getKey(),
            'type' => $type,
            'title' => $title,
            'body' => $body,
            'data' => $data,
        ]);

        $dispatch = fn () => SendNotificationChannelsJob::dispatch($notification->id);

        if (DB::transactionLevel() > 0) {
            DB::afterCommit($dispatch);
        } else {
            $dispatch();
        }

        return $notification;
    }
}
