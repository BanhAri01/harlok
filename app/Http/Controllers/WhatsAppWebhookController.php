<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessInboundMessage;
use App\Models\InboundMessage;
use App\Support\Phone;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

class WhatsAppWebhookController extends Controller
{
    public function __invoke(Request $request, string $secret): JsonResponse
    {
        $expected = (string) config('lapor.whatsapp.webhook_secret');
        abort_if($expected === '' || ! hash_equals($expected, $secret), 404);

        $device = Phone::normalize(config('lapor.whatsapp.device'));
        if ($device && Phone::normalize((string) $request->input('device')) !== $device) {
            return $this->ignored('device');
        }

        $rawSender = (string) ($request->input('pengirim') ?: $request->input('sender'));
        if ($request->boolean('isgroup') || filled($request->input('member')) || str_contains($rawSender, '@g.us') || str_contains($rawSender, '-')) {
            return $this->ignored('group');
        }

        if (str_contains($rawSender, '@')) {
            return $this->ignored('lid');
        }

        $sender = Phone::normalize($rawSender);
        $text = trim((string) $request->input('message', ''));

        if (! $sender || $text === '') {
            return $this->ignored('empty');
        }

        $key = 'wa-in:'.$sender;
        if (RateLimiter::tooManyAttempts($key, (int) config('lapor.whatsapp.per_minute'))) {
            return $this->ignored('rate');
        }
        RateLimiter::hit($key, 60);

        $inboxId = (string) ($request->input('inboxid') ?: $request->input('id'));
        $externalId = $inboxId !== '' && $inboxId !== '0' ? 'fonnte:'.mb_substr($inboxId, 0, 90) : null;

        try {
            $message = InboundMessage::query()->create([
                'external_id' => $externalId,
                'phone' => $sender,
                'body' => mb_substr($text, 0, 1000),
                'channel' => 'wa',
            ]);
        } catch (UniqueConstraintViolationException) {
            return $this->ignored('duplicate');
        }

        ProcessInboundMessage::dispatchAfterResponse($message->id);

        return response()->json(['status' => 'accepted']);
    }

    private function ignored(string $reason): JsonResponse
    {
        return response()->json(['status' => 'ignored', 'reason' => $reason]);
    }
}
