<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

#[Fillable(['actor', 'action', 'subject_type', 'subject_id', 'meta', 'ip'])]
class AuditLog extends Model
{
    public const UPDATED_AT = null;

    protected function casts(): array
    {
        return ['meta' => 'array'];
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    public static function record(string $actor, string $action, ?Model $subject = null, array $meta = []): void
    {
        static::query()->create([
            'actor' => mb_substr($actor, 0, 40),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta ?: null,
            'ip' => app()->runningInConsole() ? null : request()->ip(),
        ]);
    }
}
