<?php

namespace App\Models;

use Illuminate\Support\Arr;
use http\Exception\InvalidArgumentException;

class StudentResponse
{
    public function __construct(
        public string $id,
        public string $assessmentId,
        public string $assigned,
        public ?string $started,
        public ?string $completed,
        public Student $student,
        public array $responses,
        public array $results,
    )
    {}

    public static function fromArray(array $data, Student $student): self
    {
        // Validate required fields
        $requiredFields = ['id', 'assessmentId', 'assigned', 'responses'];

        foreach ($requiredFields as $field) {
            if (!Arr::has($data, $field)) {
                throw new InvalidArgumentException("Missing required field: '$field'");
            }
        }

        // Validate responses array
        if (!is_array($data['responses']) || empty($data['responses'])) {
            throw new InvalidArgumentException('Responses must be a non-empty array');
        }

        // Validate each response has required fields
        foreach ($data['responses'] as $response) {
            if (!isset($response['questionId'], $response['response'])) {
                throw new InvalidArgumentException('Each response must have a questionId and response');
            }
        }

        return new self(
            trim($data['id']),
            trim($data['assessmentId']),
            trim($data['assigned']),
            $data['started'] ?? null,
            $data['completed'] ?? null,
            $student,
            $data['responses'],
            $data['results'] ?? [],
        );
    }

    public function isCompleted(): bool
    {
        return !empty($this->completed);
    }

    public function getResponseForQuestion(string $questionId): ?string
    {
        foreach ($this->responses as $response) {
            if ($response['questionId'] === $questionId) {
                return $response['response'];
            }
        }

        return null;
    }

    public function getRawScore(): int
    {
        return $this->results['rawScore'] ?? 0;
    }

    public function getTotalQuestions(): int
    {
        return count($this->responses);
    }

    public function getCompletionRate(): float
    {
        $total =  $this->getTotalQuestions();
        return $total > 0 ? ($this->getRawScore() / $total) * 100 : 0;
    }

    public function getCompletedTimestamp(): int
    {
        if (!$this->completed) {
            return 0;
        }

        // Parse date format "14/12/2019 10:31:00
        $date = \DateTime::createFromFormat('d/m/Y H:i:s', $this->completed);

        return $date ? $date->getTimestamp() : 0;
    }
}
