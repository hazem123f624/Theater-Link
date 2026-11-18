<?php

namespace Modules\Theaters\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Theaters\Models\Show;
use Modules\Theaters\Models\TicketTier;
use Modules\Theaters\Models\TicketTierTranslation;

class TicketTierService
{
    public function create(Show $show, array $data): TicketTier
    {
        $this->assertInventory($data);
        $this->assertCapacity($show, (int) $data['total_quantity']);

        return DB::transaction(function () use ($show, $data): TicketTier {
            $tier = TicketTier::query()->create([
                'tenant_id' => $show->tenant_id,
                'show_id' => $show->id,
                'price' => $data['price'],
                'currency' => strtoupper($data['currency'] ?? 'SAR'),
                'total_quantity' => $data['total_quantity'],
                'sale_starts_at' => $data['sale_starts_at'] ?? null,
                'sale_ends_at' => $data['sale_ends_at'] ?? null,
                'per_booking_limit' => $data['per_booking_limit'] ?? null,
                'is_active' => $data['is_active'] ?? true,
            ]);

            $this->writeTranslations($tier, $data);

            return $tier->fresh(['translations']);
        });
    }

    public function update(TicketTier $tier, array $data): TicketTier
    {
        $total = (int) ($data['total_quantity'] ?? $tier->total_quantity);

        if ($total < $tier->sold_quantity + $tier->held_quantity) {
            throw ValidationException::withMessages([
                'total_quantity' => [__('theaters::messages.tier_below_inventory')],
            ]);
        }

        $this->assertInventory(array_merge([
            'price' => $tier->price,
            'total_quantity' => $tier->total_quantity,
            'sale_starts_at' => $tier->sale_starts_at,
            'sale_ends_at' => $tier->sale_ends_at,
            'per_booking_limit' => $tier->per_booking_limit,
        ], $data));

        if (array_key_exists('total_quantity', $data)) {
            $this->assertCapacity($tier->show, $total, $tier->id);
        }

        return DB::transaction(function () use ($tier, $data): TicketTier {
            $tier->update(collect($data)->only([
                'price',
                'currency',
                'total_quantity',
                'sale_starts_at',
                'sale_ends_at',
                'per_booking_limit',
                'is_active',
            ])->all());

            if (isset($data['currency'])) {
                $tier->currency = strtoupper($data['currency']);
                $tier->save();
            }

            $this->writeTranslations($tier, $data);

            return $tier->fresh(['translations']);
        });
    }

    public function deactivate(TicketTier $tier): TicketTier
    {
        $tier->update(['is_active' => false]);

        return $tier->fresh(['translations']);
    }

    public function assertCapacity(Show $show, int $additional, ?int $ignoreId = null): void
    {
        $current = TicketTier::query()
            ->where('show_id', $show->id)
            ->where('is_active', true)
            ->when($ignoreId, fn ($query) => $query->where('id', '!=', $ignoreId))
            ->sum('total_quantity');

        if ($current + $additional > $show->hall->capacity) {
            throw ValidationException::withMessages([
                'total_quantity' => [__('theaters::messages.tier_capacity_exceeded')],
            ]);
        }
    }

    private function assertInventory(array $data): void
    {
        $saleStartsAt = ! empty($data['sale_starts_at']) ? CarbonImmutable::parse($data['sale_starts_at']) : null;
        $saleEndsAt = ! empty($data['sale_ends_at']) ? CarbonImmutable::parse($data['sale_ends_at']) : null;

        if ($saleStartsAt && $saleEndsAt && $saleStartsAt->greaterThanOrEqualTo($saleEndsAt)) {
            throw ValidationException::withMessages([
                'sale_ends_at' => [__('theaters::messages.invalid_schedule_window')],
            ]);
        }

        if (($data['per_booking_limit'] ?? null) && (int) $data['per_booking_limit'] > (int) $data['total_quantity']) {
            throw ValidationException::withMessages([
                'per_booking_limit' => [__('theaters::messages.tier_capacity_exceeded')],
            ]);
        }
    }

    private function writeTranslations(TicketTier $tier, array $data): void
    {
        foreach (['en', 'ar'] as $lang) {
            $name = $data['name_'.$lang] ?? ($lang === 'ar' ? $data['name_en'] : null);
            if (! $name) {
                continue;
            }

            TicketTierTranslation::query()->updateOrCreate(
                ['ticket_tier_id' => $tier->id, 'lang' => $lang],
                [
                    'name' => $name,
                    'description' => $data['description_'.$lang] ?? null,
                ]
            );
        }
    }
}
