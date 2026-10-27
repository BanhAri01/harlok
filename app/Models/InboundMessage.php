<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['external_id', 'phone', 'body', 'channel', 'status', 'processed_at'])]
class InboundMessage extends Model
{
    protected function casts(): array
    {
        return ['processed_at' => 'datetime'];
    }
}
