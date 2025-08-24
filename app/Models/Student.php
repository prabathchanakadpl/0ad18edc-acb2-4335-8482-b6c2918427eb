<?php

namespace App\Models;


use Illuminate\Support\Arr;
use InvalidArgumentException;

class Student
{

    /**
     * @param string $id
     * @param string $firstName
     * @param string $lastName
     * @param int $yearLevel
     */
    public function __construct(
        public string $id,
        public string $firstName,
        public string $lastName,
        public int $yearLevel,
    )
    {}

    public static function fromArray(array $data): self
    {
        // Check all required fields exists
        $requiredFields = ['id', 'firstName', 'lastName', 'yearLevel'];
        foreach ($requiredFields as $field) {
            if (!Arr::has($data, $field)) {
                throw new InvalidArgumentException("Missing field '{$field}'");
            }
        }

        // Validate require fields
        if (!isset($data['id'], $data['firstName'], $data['lastName'], $data['yearLevel'])) {
            throw new InvalidArgumentException('Invalid data');
        }

        // Validate data types
        if (!is_int($data['yearLevel'])) {
            throw new InvalidArgumentException('Invalid data');
        }

        return new self(
            trim($data['id']),
            trim($data['firstName']),
            trim($data['lastName']),
            trim($data['yearLevel']),
        );
    }

    public function getFullName(): string
    {
        return $this->firstName. ' '. $this->lastName;
    }
}
