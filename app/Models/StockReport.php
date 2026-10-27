<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['farmer_id', 'commodity_id', 'region_id', 'quantity_kg', 'price_per_kg', 'status', 'flag_reason', 'parsed_by', 'raw_text', 'lat', 'lng', 'confirmed_at', 'expires_at', 'closed_at'])]
class StockReport extends Model
{
    use HasFactory;

    public const PENDING = 'pending';
    public const CONFIRMED = 'confirmed';
    public const FLAGGED = 'flagged';
    public const SOLD = 'sold';
    public const CLOSED = 'closed';
    public const CANCELLED = 'cancelled';
    public const REJECTED = 'rejected';

    public const PRICED = [self::CONFIRMED, self::SOLD, self::CLOSED];

    protected function casts(): array
    {
        return [
            'quantity_kg' => 'float',
            'price_per_kg' => 'integer',
            'lat' => 'float',
            'lng' => 'float',
            'confirmed_at' => 'datetime',
            'expires_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    public function farmer(): BelongsTo
    {
        return $this->belongsTo(Farmer::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function offers(): HasMany
    {
        return $this->hasMany(Offer::class);
    }

    public function scopeAvailable(Builder $query): void
    {
        $query->where('status', self::CONFIRMED)->where('expires_at', '>', now());
    }

    public function isAvailable(): bool
    {
        return $this->status === self::CONFIRMED && (bool) $this->expires_at?->isFuture();
    }
}
