<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['phone', 'state', 'data', 'expires_at'])]
class Conversation extends Model
{
    public const IDLE = 'idle';

    protected function casts(): array
    {
        return ['data' => 'array', 'expires_at' => 'datetime'];
    }

    public static function for(string $phone): self
    {
        $conversation = static::query()->firstOrCreate(['phone' => $phone], ['state' => self::IDLE]);

        if ($conversation->state !== self::IDLE && $conversation->expires_at?->isPast()) {
            $conversation->reset();
        }

        return $conversation;
    }

    public function moveTo(string $state, array $data = [], int $minutes = 30): void
    {
        $this->forceFill(['state' => $state, 'data' => $data, 'expires_at' => now()->addMinutes($minutes)])->save();
    }

    public function reset(): void
    {
        $this->forceFill(['state' => self::IDLE, 'data' => null, 'expires_at' => null])->save();
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
