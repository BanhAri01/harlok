<?php

namespace App\Ai;

use Anthropic\Client;
use GuzzleHttp\Client as GuzzleClient;

class AnthropicDriver implements AiDriver
{
    public function __construct(private string $apiKey, private string $model, private float $timeout) {}

    public function name(): string
    {
        return 'anthropic';
    }

    public function model(): string
    {
        return $this->model;
    }

    public function json(string $system, string $input, array $schema, int $maxTokens): ?array
    {
        $client = new Client(
            apiKey: $this->apiKey,
            requestOptions: [
                'maxRetries' => 1,
                'transporter' => new GuzzleClient(['timeout' => $this->timeout, 'connect_timeout' => 8]),
            ],
        );

        $message = $client->messages->create(
            maxTokens: $maxTokens,
            messages: [['role' => 'user', 'content' => $input]],
            model: $this->model,
            outputConfig: ['effort' => 'low', 'format' => ['type' => 'json_schema', 'schema' => $schema]],
            system: $system,
        );

        if ($message->stopReason === 'refusal') {
            throw new AiRefusedException('Permintaan ditolak model');
        }

        $json = '';
        foreach ($message->content as $block) {
            if ($block->type === 'text') {
                $json .= $block->text;
            }
        }

        $data = json_decode($json, true);

        return is_array($data) ? $data : null;
    }
}
