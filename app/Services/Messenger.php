<?php

namespace App\Services;

use App\Models\OutboundMessage;
use App\Support\Phone;
use Illuminate\Support\Facades\Http;
use Throwable;

class Messenger
{
    public function send(string $phone, string $body): OutboundMessage
    {
        $channel = Phone::channel($phone);

        $message = OutboundMessage::query()->create([
            'phone' => $phone,
            'body' => $body,
            'channel' => $channel,
            'status' => 'queued',
        ]);

        if ($channel !== 'wa' || config('lapor.whatsapp.driver') !== 'fonnte') {
            $message->update(['status' => 'logged']);

            return $message;
        }

        if ($this->sentToday() >= (int) config('lapor.whatsapp.daily_outbound_limit')) {
            $message->update(['status' => 'skipped', 'error' => 'Batas pesan harian tercapai']);

            return $message;
        }

        try {
            $response = Http::asForm()
                ->timeout(15)
                ->withHeaders(['Authorization' => (string) config('lapor.whatsapp.fonnte_token')])
                ->post((string) config('lapor.whatsapp.fonnte_url'), [
                    'target' => $phone,
                    'message' => $body,
                    'countryCode' => '62',
                ]);

            $ok = $response->successful() && $response->json('status') === true;
            $message->update([
                'status' => $ok ? 'sent' : 'failed',
                'error' => $ok ? null : mb_substr((string) ($response->json('reason') ?? $response->status()), 0, 255),
            ]);
        } catch (Throwable $exception) {
            $message->update(['status' => 'failed', 'error' => mb_substr($exception->getMessage(), 0, 255)]);
        }

        return $message;
    }

    public function sendMany(string $phone, array $bodies): void
    {
        foreach (array_filter($bodies) as $body) {
            $this->send($phone, $body);
        }
    }

    private function sentToday(): int
    {
        return OutboundMessage::query()
            ->where('channel', 'wa')
            ->where('status', 'sent')
            ->where('created_at', '>=', now()->startOfDay())
            ->count();
    }
}
