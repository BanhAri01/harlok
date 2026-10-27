<?php

namespace App\Http\Controllers;

use App\Models\InboundMessage;
use App\Models\OutboundMessage;
use App\Services\InboundProcessor;
use App\Support\Phone;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\View\View;

class SimulatorController extends Controller
{
    public function show(Request $request): View
    {
        return view('simulator', [
            'phone' => $this->phone($request),
            'waNumber' => config('lapor.whatsapp.device'),
        ]);
    }

    public function send(Request $request, InboundProcessor $processor): JsonResponse
    {
        $data = $request->validate(['message' => ['required', 'string', 'max:500']]);
        $phone = $this->phone($request);

        $key = 'sim:'.$phone.'|'.$request->ip();
        if (RateLimiter::tooManyAttempts($key, (int) config('lapor.simulator.per_minute'))) {
            return response()->json(['message' => 'Terlalu cepat, tunggu sebentar.'], 429);
        }
        RateLimiter::hit($key, 60);

        $message = InboundMessage::query()->create([
            'phone' => $phone,
            'body' => $data['message'],
            'channel' => 'simulator',
        ]);

        $processor->process($message);

        return $this->messages($request);
    }

    public function messages(Request $request): JsonResponse
    {
        $after = (int) $request->integer('after');

        $messages = OutboundMessage::query()
            ->where('phone', $this->phone($request))
            ->where('id', '>', $after)
            ->orderBy('id')
            ->limit(30)
            ->get(['id', 'body', 'created_at'])
            ->map(fn (OutboundMessage $message) => [
                'id' => $message->id,
                'body' => $message->body,
                'time' => $message->created_at->format('H.i'),
            ]);

        return response()->json(['messages' => $messages]);
    }

    public function reset(Request $request): JsonResponse
    {
        $request->session()->put('simulator_phone', Phone::simulator());

        return response()->json(['status' => 'ok']);
    }

    private function phone(Request $request): string
    {
        $phone = $request->session()->get('simulator_phone');

        if (! is_string($phone) || ! Phone::isSimulator($phone)) {
            $phone = Phone::simulator();
            $request->session()->put('simulator_phone', $phone);
        }

        return $phone;
    }
}
