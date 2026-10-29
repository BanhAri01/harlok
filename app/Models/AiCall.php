<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['purpose', 'driver', 'model', 'status', 'latency_ms', 'error'])]
class AiCall extends Model
{
    public const UPDATED_AT = null;

    public const OK = 'ok';

    public const FAILED = 'failed';

    public const INVALID = 'invalid';

    public const REFUSED = 'refused';

    public const SKIPPED = 'skipped';
}
