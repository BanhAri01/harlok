<?php

namespace App\Jobs;

use App\Models\InboundMessage;
use App\Services\InboundProcessor;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ProcessInboundMessage implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    public function __construct(public int $messageId)
    {
    }

    public function handle(InboundProcessor $processor): void
    {
        $message = InboundMessage::query()->where('status', 'received')->find($this->messageId);

        if ($message) {
            $processor->process($message);
        }
    }
}
