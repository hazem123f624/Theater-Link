<?php

namespace Modules\Theaters\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $show_id
 * @property string $price
 * @property string $currency
 * @property int $total_quantity
 * @property int $sold_quantity
 * @property int $held_quantity
 * @property \Illuminate\Support\Carbon|null $sale_starts_at
 * @property \Illuminate\Support\Carbon|null $sale_ends_at
 * @property int|null $per_booking_limit
 * @property bool $is_active
 * @property-read Show $show
 * @property-read \Illuminate\Database\Eloquent\Collection<int, TicketTierTranslation> $translations
 */
class TicketTier extends Model
{
    use BelongsToTenant;
    use SoftDeletes;

    protected $table = 'ticket_tiers';

    protected $fillable = [
        'tenant_id', 'show_id', 'price', 'currency', 'total_quantity',
        'sold_quantity', 'held_quantity', 'sale_starts_at', 'sale_ends_at',
        'per_booking_limit', 'is_active',
    ];

    protected $casts = [
        'tenant_id' => 'integer',
        'show_id' => 'integer',
        'price' => 'decimal:2',
        'total_quantity' => 'integer',
        'sold_quantity' => 'integer',
        'held_quantity' => 'integer',
        'sale_starts_at' => 'datetime',
        'sale_ends_at' => 'datetime',
        'per_booking_limit' => 'integer',
        'is_active' => 'boolean',
    ];

    /** @return BelongsTo<Show, $this> */
    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    /** @return HasMany<TicketTierTranslation, $this> */
    public function translations(): HasMany
    {
        return $this->hasMany(TicketTierTranslation::class);
    }

    public function translation(string $lang): ?TicketTierTranslation
    {
        return $this->translations->firstWhere('lang', $lang);
    }

    public function availableQuantity(): int
    {
        return max(0, $this->total_quantity - $this->sold_quantity - $this->held_quantity);
    }
}
