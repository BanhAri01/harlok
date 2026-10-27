<?php

namespace App\Chat;

use App\Models\Commodity;
use App\Models\Region;

final class ParsedReport
{
    public function __construct(
        public ?Commodity $commodity = null,
        public ?float $quantityKg = null,
        public ?int $pricePerKg = null,
        public ?Region $region = null,
        public string $source = 'pattern',
    ) {}

    public function isComplete(): bool
    {
        return $this->commodity !== null && $this->quantityKg > 0 && $this->pricePerKg > 0;
    }

    public function hasAnything(): bool
    {
        return $this->quantityKg !== null || $this->pricePerKg !== null;
    }

    public function missing(): array
    {
        return array_keys(array_filter([
            'komoditas' => $this->commodity === null,
            'jumlah' => ! ($this->quantityKg > 0),
            'harga' => ! ($this->pricePerKg > 0),
        ]));
    }

    public function fillFrom(self $other): self
    {
        return new self(
            $this->commodity ?? $other->commodity,
            $this->quantityKg ?? $other->quantityKg,
            $this->pricePerKg ?? $other->pricePerKg,
            $this->region ?? $other->region,
            $other->source,
        );
    }
}
