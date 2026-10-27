<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['phone', 'body', 'channel', 'status', 'error'])]
class OutboundMessage extends Model
{
}
