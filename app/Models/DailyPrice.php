<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['commodity_id', 'region_id', 'date', 'avg_price', 'min_price', 'max_price', 'median_price', 'reports_count'])]
class DailyPrice extends Model
{
    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'avg_price' => 'integer',
            'min_price' => 'integer',
            'max_price' => 'integer',
            'median_price' => 'integer',
            'reports_count' => 'integer',
        ];
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function commodity(): BelongsTo
    {
        return $this->belongsTo(Commodity::class);
    }
}
