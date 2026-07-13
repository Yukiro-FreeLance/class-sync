<?php

namespace App\Http\Controllers\Students;

use App\Enums\AuditAction;
use App\Exports\ClassListExport;
use App\Exports\MasterListExport;
use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Services\Audit\AuditLogService;
use App\Services\Students\StudentListService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Excel as ExcelFormat;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentListExportController extends Controller
{
    public function masterList(Request $request, StudentListService $listService, AuditLogService $auditLog): BinaryFileResponse
    {
        $this->authorize('viewAny', Student::class);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'grade' => ['required', 'exists:grade_levels,id'],
            'section' => ['nullable', 'exists:sections,id'],
            'active_only' => ['nullable', 'in:0,1'],
            'gender' => ['nullable', 'in:male,female'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $activeOnly = ($validated['active_only'] ?? '1') === '1';
        $students = $listService->masterListQuery(
            (int) $validated['academic_year_id'],
            (int) $validated['grade'],
            isset($validated['section']) ? (int) $validated['section'] : null,
            $activeOnly,
            $request->user(),
            $validated['gender'] ?? null,
        )->get();

        $gradeName = GradeLevel::query()->find($validated['grade'])?->name ?? 'grade';
        $format = $validated['format'] ?? 'xlsx';
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;
        $filename = 'master-list-'.str($gradeName)->slug().'-'.now()->format('Y-m-d').'.'.$extension;

        $auditLog->log(AuditAction::Export, null, 'Exported master list', [
            'grade' => $validated['grade'],
            'section' => $validated['section'] ?? null,
            'count' => $students->count(),
            'format' => $extension,
        ]);

        return Excel::download(new MasterListExport($students), $filename, $writerType);
    }

    public function classList(Request $request, StudentListService $listService, AuditLogService $auditLog): BinaryFileResponse
    {
        $this->authorize('viewAny', Student::class);

        $validated = $request->validate([
            'academic_year_id' => ['required', 'exists:academic_years,id'],
            'section' => ['required', 'exists:sections,id'],
            'subject' => ['nullable', 'exists:subjects,id'],
            'active_only' => ['nullable', 'in:0,1'],
            'gender' => ['nullable', 'in:male,female'],
            'format' => ['nullable', 'in:xlsx,csv'],
        ]);

        $activeOnly = ($validated['active_only'] ?? '1') === '1';
        $students = $listService->classListQuery(
            (int) $validated['academic_year_id'],
            (int) $validated['section'],
            isset($validated['subject']) ? (int) $validated['subject'] : null,
            $activeOnly,
            $request->user(),
            $validated['gender'] ?? null,
        )->get();

        $section = Section::query()->with('gradeLevel')->find($validated['section']);
        $label = str($section?->gradeLevel?->name.'-'.$section?->name)->slug();
        $format = $validated['format'] ?? 'xlsx';
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writerType = $format === 'csv' ? ExcelFormat::CSV : ExcelFormat::XLSX;
        $filename = 'class-list-'.$label.'-'.now()->format('Y-m-d').'.'.$extension;

        $auditLog->log(AuditAction::Export, null, 'Exported class list', [
            'section' => $validated['section'],
            'subject' => $validated['subject'] ?? null,
            'count' => $students->count(),
            'format' => $extension,
        ]);

        return Excel::download(new ClassListExport($students), $filename, $writerType);
    }
}
