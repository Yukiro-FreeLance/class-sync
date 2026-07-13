<?php

namespace App\Http\Controllers\Students;

use App\Enums\AuditAction;
use App\Exports\StudentsExport;
use App\Exports\StudentsImportTemplateExport;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Services\Audit\AuditLogService;
use App\Services\Students\StudentReferenceDataService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportExportController extends Controller
{
    public function template(StudentReferenceDataService $referenceData): BinaryFileResponse
    {
        $this->authorize('create', Student::class);

        $referenceData->ensureExists();

        return Excel::download(
            new StudentsImportTemplateExport,
            'students-import-template.xlsx',
        );
    }

    public function export(Request $request, AuditLogService $auditLog): BinaryFileResponse
    {
        $this->authorize('viewAny', Student::class);

        $filters = $request->only(['search', 'grade', 'section', 'status', 'gender']);
        $format = $request->query('format', 'xlsx');
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;

        $auditLog->log(
            AuditAction::Export,
            null,
            'Exported student list',
            ['filters' => $filters, 'format' => $extension],
        );

        return Excel::download(
            new StudentsExport($filters),
            'students-'.now()->format('Y-m-d_His').'.'.$extension,
            $writerType,
        );
    }
}
