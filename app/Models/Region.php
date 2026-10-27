<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['name', 'slug', 'province', 'lat', 'lng', 'sort'])]
class Region extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return ['lat' => 'float', 'lng' => 'float'];
    }

    public function farmers(): HasMany
    {
        return $this->hasMany(Farmer::class);
    }

    public static function ordered(): Collection
    {
        return once(fn () => static::query()->orderBy('sort')->orderBy('name')->get());
    }
}
