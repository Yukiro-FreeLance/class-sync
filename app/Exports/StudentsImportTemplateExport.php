<?php

namespace App\Exports;

use App\Exports\Sheets\StudentsReferenceSheet;
use App\Exports\Sheets\StudentsTemplateSheet;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class StudentsImportTemplateExport implements WithMultipleSheets
{
    use Exportable;

    /**
     * @return array<int, FromArray|WithTitle>
     */
    public function sheets(): array
    {
        return [
            new StudentsTemplateSheet,
            new StudentsReferenceSheet,
        ];
    }
}
