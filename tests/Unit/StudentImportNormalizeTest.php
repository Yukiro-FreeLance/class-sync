<?php

namespace Tests\Unit;

use App\Services\Students\StudentImportService;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class StudentImportNormalizeTest extends TestCase
{
    #[DataProvider('spreadsheetValueProvider')]
    public function test_normalizes_spreadsheet_values_to_strings(mixed $input, ?string $expected): void
    {
        $service = app(StudentImportService::class);
        $method = new \ReflectionMethod($service, 'normalizeValue');
        $method->setAccessible(true);

        $this->assertSame($expected, $method->invoke($service, 'student_number', $input));
    }

    /**
     * @return array<string, array{0: mixed, 1: ?string}>
     */
    public static function spreadsheetValueProvider(): array
    {
        return [
            'null cell' => [null, null],
            'empty string' => ['', null],
            'numeric integer id' => [2024001, '2024001'],
            'numeric float id' => [2024001.0, '2024001'],
            'string id' => ['STU-001', 'STU-001'],
            'zero treated as blank' => [0, null],
            'string zero treated as blank' => ['0', null],
        ];
    }
}
