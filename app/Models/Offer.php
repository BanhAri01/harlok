<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['stock_report_id', 'buyer_id', 'price_per_kg', 'quantity_kg', 'market_avg', 'status', 'counter_price', 'responded_at'])]
class Offer extends Model
{
    public const SENT = 'sent';
    public const ACCEPTED = 'accepted';
    public const REJECTED = 'rejected';
    public const COUNTERED = 'countered';
    public const CANCELLED = 'cancelled';

    public const OPEN = [self::SENT, self::COUNTERED];

    protected function casts(): array
    {
        return [
            'price_per_kg' => 'integer',
            'quantity_kg' => 'float',
            'market_avg' => 'integer',
            'counter_price' => 'integer',
            'responded_at' => 'datetime',
        ];
    }

    public function report(): BelongsTo
    {
        return $this->belongsTo(StockReport::class, 'stock_report_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function isOpen(): bool
    {
        return in_array($this->status, self::OPEN, true);
    }

    public function percentFromMarket(): ?float
    {
        if (! $this->market_avg) {
            return null;
        }

        return round(($this->price_per_kg - $this->market_avg) / $this->market_avg * 100, 1);
    }
}
