<?php

namespace App\DTOs\Reports;

readonly class ReportPreview
{
    /**
     * @param  list<array{label: string, value: string|int, hint?: ?string}>  $summaryStats
     * @param  list<array{key: string, label: string, align?: string}>  $columns
     * @param  list<array<string, mixed>>  $rows
     * @param  list<array{title: string, columns: list<array{key: string, label: string, align?: string}>, rows: list<array<string, mixed>>}>  $tables
     * @param  list<array{key: string, title: string, subtitle?: string, labels: list<string>, datasets: list<array{label: string, data: list<int|float>, colors?: list<string>|string}>, yMax?: ?int, suffix?: string, wide?: bool}>  $charts
     */
    public function __construct(
        public string $title,
        public string $periodLabel,
        public array $summaryStats,
        public array $columns,
        public array $rows,
        public array $tables = [],
        public int $totalRows = 0,
        public array $charts = [],
        public ?string $narrative = null,
        public ?string $narrativeNote = null,
        public ?string $rowsTitle = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->totalRows === 0 && $this->rows === [];
    }
}
