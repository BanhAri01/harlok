<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'phone', 'region_id', 'commodity_id', 'lat', 'lng', 'channel', 'verified_at', 'digest_opt_in', 'stopped_at', 'last_message_at'])]
class Farmer extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'lat' => 'float',
            'lng' => 'float',
            'verified_at' => 'datetime',
            'digest_opt_in' => 'boolean',
            'stopped_at' => 'datetime',
            'last_message_at' => 'datetime',
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

    public function reports(): HasMany
    {
        return $this->hasMany(StockReport::class);
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isStopped(): bool
    {
        return $this->stopped_at !== null;
    }

    public function firstName(): string
    {
        return strtok($this->name, ' ') ?: $this->name;
    }
}
