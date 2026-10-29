<?php

namespace App\Ai;

use App\Models\AiCall;
use App\Support\Phone;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Throwable;

class AiGateway
{
    private AiDriver|false|null $driver = null;

    public function available(): bool
    {
        return $this->driver() !== null;
    }

    public function budgetsFor(string $phone): array
    {
        $budgets = ['ai:'.$phone => (int) config('lapor.ai.daily_per_phone'), 'ai:semua' => (int) config('lapor.ai.daily_global')];

        if (Phone::channel($phone) !== 'wa') {
            $budgets['ai:simulator'] = (int) config('lapor.ai.daily_simulator');
        }

        return $budgets;
    }

    public function json(string $purpose, string $system, string $input, array $schema, int $maxTokens = 400, array $budgets = []): ?array
    {
        $driver = $this->driver();

        if (! $driver) {
            return null;
        }

        $budgets += ['ai:semua' => (int) config('lapor.ai.daily_global')];

        foreach ($budgets as $key => $limit) {
            if (RateLimiter::tooManyAttempts($key, $limit)) {
                $this->log($purpose, $driver, AiCall::SKIPPED, 0, 'Kuota harian habis');

                return null;
            }
        }

        foreach (array_keys($budgets) as $key) {
            RateLimiter::hit($key, 86400);
        }

        $started = hrtime(true);
        $error = null;

        try {
            $data = $driver->json($system, mb_substr($input, 0, 4000), $schema, $maxTokens);
            $status = $data !== null && $this->matches($data, $schema) ? AiCall::OK : AiCall::INVALID;
        } catch (AiRefusedException $exception) {
            [$data, $status, $error] = [null, AiCall::REFUSED, $exception->getMessage()];
        } catch (Throwable $exception) {
            [$data, $status, $error] = [null, AiCall::FAILED, $exception->getMessage()];
            Log::warning("AI gagal ({$purpose}): ".$exception->getMessage());
        }

        $this->log($purpose, $driver, $status, (int) ((hrtime(true) - $started) / 1_000_000), $error);

        return $status === AiCall::OK ? $data : null;
    }

    private function matches(array $data, array $schema): bool
    {
        foreach ($schema['required'] ?? [] as $key) {
            if (! array_key_exists($key, $data)) {
                return false;
            }
        }

        foreach ($schema['properties'] ?? [] as $key => $rule) {
            if (isset($rule['enum']) && array_key_exists($key, $data) && ! in_array($data[$key], $rule['enum'], true)) {
                return false;
            }
        }

        return true;
    }

    private function log(string $purpose, AiDriver $driver, string $status, int $latency, ?string $error): void
    {
        AiCall::query()->create([
            'purpose' => $purpose,
            'driver' => $driver->name(),
            'model' => mb_substr($driver->model(), 0, 60),
            'status' => $status,
            'latency_ms' => $latency,
            'error' => $error ? mb_substr($error, 0, 255) : null,
        ]);
    }

    private function driver(): ?AiDriver
    {
        if ($this->driver === null) {
            $key = (string) config('lapor.ai.api_key');
            $name = (string) config('lapor.ai.driver');
            $model = (string) (config('lapor.ai.model') ?: config("lapor.ai.models.{$name}"));
            $timeout = (float) config('lapor.ai.timeout');

            $this->driver = match (true) {
                $key === '' || $model === '' => false,
                $name === 'openai' => new OpenAiCompatibleDriver($key, (string) config('lapor.ai.base_url'), $model, $timeout),
                $name === 'anthropic' => new AnthropicDriver($key, $model, $timeout),
                default => false,
            };
        }

        return $this->driver ?: null;
    }
}
