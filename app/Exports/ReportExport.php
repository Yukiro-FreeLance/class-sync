<?php

namespace App\Exports;

use App\DTOs\Reports\ReportPreview;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ReportExport implements FromCollection, ShouldAutoSize, WithHeadings, WithMapping, WithStyles
{
    public function __construct(protected ReportPreview $preview) {}

    public function collection(): Collection
    {
        return collect($this->preview->rows);
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return collect($this->preview->columns)->pluck('label')->all();
    }

    /**
     * @param  array<string, mixed>  $row
     * @return list<mixed>
     */
    public function map($row): array
    {
        return collect($this->preview->columns)
            ->map(fn (array $column) => $row[$column['key']] ?? '')
            ->all();
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}
