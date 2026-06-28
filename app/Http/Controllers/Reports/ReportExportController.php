<?php

namespace App\Http\Controllers\Reports;

use App\Enums\AuditAction;
use App\Exports\ReportExport;
use App\Http\Controllers\Controller;
use App\Services\Audit\AuditLogService;
use App\Services\Reports\ReportService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ReportExportController extends Controller
{
    public function __invoke(Request $request, ReportService $reportService, AuditLogService $auditLog): BinaryFileResponse
    {
        abort_unless($request->user()?->can('reports.export'), 403);

        $validated = $request->validate([
            'report_type' => ['required', 'string', 'in:'.implode(',', array_keys($reportService->reportTypes()))],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'department' => ['nullable', 'integer'],
            'grade' => ['nullable', 'integer'],
            'section' => ['nullable', 'integer'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $preview = $reportService->preview(
            $validated['report_type'],
            $validated['date_from'],
            $validated['date_to'],
            $this->filtersFrom($validated),
        );

        $format = $validated['format'] ?? 'xlsx';
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;
        $filename = str($preview->title)->slug().'-'.now()->format('Y-m-d').'.'.$extension;

        $auditLog->log(AuditAction::Export, null, "Exported report: {$preview->title}", [
            'report_type' => $validated['report_type'],
            'date_from' => $validated['date_from'],
            'date_to' => $validated['date_to'],
            'rows' => $preview->totalRows,
            'format' => $extension,
        ]);

        return Excel::download(new ReportExport($preview), $filename, $writerType);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array{department?: ?int, grade?: ?int, section?: ?int}
     */
    protected function filtersFrom(array $validated): array
    {
        return array_filter([
            'department' => isset($validated['department']) ? (int) $validated['department'] : null,
            'grade' => isset($validated['grade']) ? (int) $validated['grade'] : null,
            'section' => isset($validated['section']) ? (int) $validated['section'] : null,
        ], fn ($value) => $value !== null);
    }
}
