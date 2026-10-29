<?php

namespace App\Services;

use App\Chat\ChatBot;
use App\Models\InboundMessage;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class InboundProcessor
{
    public function __construct(private ChatBot $bot, private Messenger $messenger) {}

    public function process(InboundMessage $message): void
    {
        Cache::lock('chat:'.$message->phone, 30)->block(20, function () use ($message) {
            try {
                $replies = $this->bot->handle($message->phone, $message->body);
                $this->messenger->sendMany($message->phone, $replies);
                $message->update(['status' => 'processed', 'processed_at' => now()]);
            } catch (Throwable $exception) {
                Log::error('Gagal memproses pesan masuk #'.$message->id.': '.$exception->getMessage(), ['exception' => $exception]);
                $message->update(['status' => 'failed', 'processed_at' => now()]);
                $this->messenger->send($message->phone, 'Maaf, sistem sedang sibuk. Coba kirim ulang beberapa menit lagi.');
            }
        });
    }
}
