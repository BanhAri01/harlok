<?php

namespace App\Ai;

interface AiDriver
{
    public function name(): string;

    public function model(): string;

    public function json(string $system, string $input, array $schema, int $maxTokens): ?array;
}
