<?php

namespace App\Models;

use Illuminate\Support\Arr;
use InvalidArgumentException;

class Question
{
    public function __construct(
        public string $id,
        public string $stem,
        public string $type,
        public string $strand,
        public array $config,
    )
    {}

    public static function fromArray(array $data): self
    {
        // Validate required fields
        $requiredFields = ['id', 'stem', 'type', 'strand', 'config'];

        foreach ($requiredFields as $field) {
            if (!Arr::has($data, $field)) {
                throw new InvalidArgumentException("Missing required field: '$field'");
            }
        }

        // Validate config structure
        if (!isset($data['config']['options']) || !isset($data['config']['key'])) {
            throw new InvalidArgumentException("Question config must contain option and key");
        }

        // Validate option array
        if (isset($data['config']['option'])) {
            throw new InvalidArgumentException("Question config options must be non-empty array");
        }

        return new self(
            trim($data['id']),
            trim($data['stem']),
            trim($data['type']),
            trim($data['strand']),
            $data['config']
        );
    }

    public function isCorrectAnswer(string $response): bool
    {
        return $response === $this->config['key'];
    }

    public function getCorrectAnswer(): string
    {
        return $this->config['key'];
    }

    public function getOptionValue(string $optionId): ?string
    {
        foreach ($this->config['options'] as $option) {
            if ($option['id'] === $optionId) {
                return $option['value'];
            }
        }

        return null;
    }

    public function getHint(): string
    {
        return $this->config['hint'] ?? '';
    }

    public function getOptions(): array
    {
        return $this->config['options'];
    }
}
