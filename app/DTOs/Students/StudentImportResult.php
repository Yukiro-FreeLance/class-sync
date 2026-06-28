<?php

namespace App\DTOs\Students;

readonly class StudentImportResult
{
    /**
     * @param  list<array{row: int, message: string}>  $errors
     * @param  list<array{row: int, message: string}>  $skippedRows
     */
    public function __construct(
        public int $imported,
        public int $skipped,
        public array $errors,
        public array $skippedRows = [],
    ) {}

    public function hasErrors(): bool
    {
        return $this->errors !== [];
    }

    public function totalProcessed(): int
    {
        return $this->imported + $this->skipped;
    }
}
