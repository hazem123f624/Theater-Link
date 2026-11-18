<?php

namespace App\Jobs;

use App\Services\Messages\MessageFactory;
use App\Services\Notifications\Models\AppNotification;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Modules\Admins\Models\Admins;
use Modules\Tenancy\Models\TenantMember;

class SendNotificationChannelsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public int $notificationId) {}

    public function handle(): void
    {
        $notification = AppNotification::query()->with('notifiable')->find($this->notificationId);

        if (! $notification) {
            return;
        }

        $notifiable = $notification->notifiable;

        if (! $notifiable) {
            return;
        }
        $email = $notifiable instanceof Admins || $notifiable instanceof TenantMember
            ? $notifiable->email
            : null;

        if ($email) {
            MessageFactory::make('email')->send([
                'to' => $email,
                'subject' => $notification->title,
                'view' => 'emails.app-notification',
                'body' => [
                    'title' => $notification->title,
                    'notificationBody' => $notification->body,
                ],
            ]);
        }

        if ($notifiable instanceof TenantMember && $notifiable->phone) {
            MessageFactory::make('sms')->send([
                'to' => $notifiable->phone,
                'body' => $notification->title.': '.$notification->body,
            ]);
        }
    }
}
