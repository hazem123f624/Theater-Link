<?php

namespace Modules\Theaters\Services;

use App\Services\MediaSystem\MediaService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Tenancy\Models\Tenant;
use Modules\Theaters\Models\Event;
use Modules\Theaters\Models\EventTranslation;

class EventService
{
    public function __construct(
        private MediaService $media,
        private ShowService $shows,
    ) {}

    public function create(Tenant $tenant, array $data, ?UploadedFile $poster = null): Event
    {
        return DB::transaction(function () use ($tenant, $data, $poster): Event {
            $event = Event::query()->create([
                'tenant_id' => $tenant->id,
                'category_id' => $data['category_id'] ?? null,
                'slug' => $this->uniqueSlug($data['slug'] ?? $data['title_en']),
                'status' => 'draft',
            ]);

            $this->writeTranslations($event, $data);
            $this->storePoster($event, $poster);

            return $event->fresh(['translations', 'category.translations', 'poster']);
        });
    }

    public function update(Event $event, array $data, ?UploadedFile $poster = null): Event
    {
        return DB::transaction(function () use ($event, $data, $poster): Event {
            $event->update([
                'category_id' => $data['category_id'] ?? $event->category_id,
                'slug' => isset($data['slug']) ? $this->uniqueSlug($data['slug'], $event->id) : $event->slug,
            ]);

            $this->writeTranslations($event, $data);

            if ($poster) {
                $this->storePoster($event, $poster);
            }

            return $event->fresh(['translations', 'category.translations', 'poster']);
        });
    }

    public function publish(Event $event): Event
    {
        if ($event->cancelled_at) {
            throw ValidationException::withMessages([
                'event' => [__('theaters::messages.event_cancelled')],
            ]);
        }

        if (! $event->shows()->where('status', 'scheduled')->where('starts_at', '>', now())->exists()) {
            throw ValidationException::withMessages([
                'shows' => [__('theaters::messages.event_publish_requires_show')],
            ]);
        }

        $event->update([
            'status' => 'published',
            'published_at' => $event->published_at ?? now(),
        ]);

        return $event->fresh(['translations', 'category.translations', 'poster', 'shows']);
    }

    public function unpublish(Event $event): Event
    {
        $event->update([
            'status' => 'draft',
            'published_at' => null,
        ]);

        return $event->fresh(['translations', 'category.translations', 'poster', 'shows']);
    }

    public function cancel(Event $event): Event
    {
        return DB::transaction(function () use ($event): Event {
            $event->update([
                'status' => 'cancelled',
                'cancelled_at' => now(),
            ]);

            $this->shows->cancelForEvent($event);

            return $event->fresh(['translations', 'category.translations', 'poster', 'shows']);
        });
    }

    public function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'event';
        $slug = $base;
        $counter = 2;

        while (Event::withoutGlobalScope(Event::CURRENT_TENANT_SCOPE)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->where('slug', $slug)
            ->exists()) {
            $slug = $base.'-'.$counter++;
        }

        return $slug;
    }

    private function writeTranslations(Event $event, array $data): void
    {
        foreach (['en', 'ar'] as $lang) {
            $title = $data['title_'.$lang] ?? ($lang === 'ar' ? $data['title_en'] : null);
            if (! $title) {
                continue;
            }

            EventTranslation::query()->updateOrCreate(
                ['event_id' => $event->id, 'lang' => $lang],
                [
                    'title' => $title,
                    'description' => $data['description_'.$lang] ?? null,
                ]
            );
        }
    }

    private function storePoster(Event $event, ?UploadedFile $poster): void
    {
        if (! $poster) {
            return;
        }

        $media = $this->media
            ->useDisk(config('filesystems.default'))
            ->withVisibility('public')
            ->withCategory('event_poster')
            ->upload($poster, $event, 'poster', "events/{$event->tenant_id}/{$event->id}");

        $event->update(['poster_media_id' => $media->id]);
    }
}
