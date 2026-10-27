<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['name', 'slug', 'aliases', 'unit_sizes', 'min_price', 'max_price', 'is_active', 'sort'])]
class Commodity extends Model
{
    public $timestamps = false;

    protected function casts(): array
    {
        return [
            'aliases' => 'array',
            'unit_sizes' => 'array',
            'min_price' => 'integer',
            'max_price' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public static function active(): Collection
    {
        return once(fn () => static::query()->where('is_active', true)->orderBy('sort')->orderBy('name')->get());
    }

    public function unitSize(string $unit): ?float
    {
        $size = $this->unit_sizes[$unit] ?? null;

        return $size ? (float) $size : null;
    }

    public function priceIsPlausible(int $pricePerKg): bool
    {
        return $pricePerKg >= $this->min_price && $pricePerKg <= $this->max_price;
    }
}
