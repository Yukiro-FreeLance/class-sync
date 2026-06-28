<?php

namespace App\DTOs\Students;

readonly class BulkEnrollmentResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public int $processed = 0,
        public int $failed = 0,
        public array $errors = [],
    ) {}

    public function successCount(): int
    {
        return $this->processed - $this->failed;
    }
}
