<?php

namespace App\Livewire\Students;

use App\Models\Student;
use App\Services\Attendance\AttendancePeriodService;
use App\Services\Students\StudentService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Show extends Component
{
    public Student $student;

    public string $activeTab = 'info';

    public function mount(Student $student): void
    {
        $this->authorize('view', $student);

        $this->student = $student->load([
            'gradeLevel.department',
            'section',
            'course',
            'academicYear',
            'guardians',
            'emergencyContacts',
            'enrollments.academicYear',
            'enrollments.gradeLevel',
            'enrollments.section',
            'enrollments.course',
            'enrollments.classSchedules.subject',
            'enrollments.classSchedules.teacher',
        ]);
    }

    public function setTab(string $tab): void
    {
        $this->activeTab = $tab;
    }

    public function archive(StudentService $studentService): void
    {
        $this->authorize('archive', $this->student);

        $studentService->archive($this->student);

        $this->dispatch('toast', message: 'Student archived successfully.', type: 'success');

        $this->redirect(route('students.index'), navigate: true);
    }

    public function restore(StudentService $studentService): void
    {
        $this->authorize('restore', $this->student);

        $this->student = $studentService->restore($this->student)->load([
            'gradeLevel.department',
            'section',
            'course',
            'academicYear',
            'guardians',
            'emergencyContacts',
            'enrollments.academicYear',
            'enrollments.gradeLevel',
            'enrollments.section',
            'enrollments.course',
            'enrollments.classSchedules.subject',
            'enrollments.classSchedules.teacher',
        ]);

        $this->dispatch('toast', message: 'Student restored successfully.', type: 'success');
    }

    public function forceDelete(StudentService $studentService): void
    {
        $this->authorize('delete', $this->student);

        $studentService->forceDelete($this->student);

        $this->dispatch('toast', message: 'Student permanently deleted.', type: 'success');

        $this->redirect(route('students.index'), navigate: true);
    }

    protected function viewData(): array
    {
        $timeline = app(AttendancePeriodService::class)->buildStudentTimeline($this->student->id);
        $studentService = app(StudentService::class);

        return [
            'currentEnrollment' => $this->student->currentEnrollment(),
            'enrollments' => $this->student->enrollments->sortByDesc('academic_year_id'),
            'timeline' => $timeline,
            'attendance' => $this->student->attendanceRecords()
                ->orderByDesc('date')
                ->orderByDesc('time_in')
                ->limit(20)
                ->get(),
            'classAttendance' => $this->student->attendancePeriodLogs()
                ->with(['remark', 'classSchedule.subject', 'section.gradeLevel'])
                ->orderByDesc('date')
                ->orderByDesc('class_schedule_id')
                ->limit(20)
                ->get(),
            'behaviorRecords' => $this->student->behaviorRecords()
                ->orderByDesc('date')
                ->limit(20)
                ->get(),
            'documents' => $this->student->documents()
                ->orderByDesc('created_at')
                ->get(),
            'qrCodeUrl' => $studentService->getQrCodeUrl($this->student),
            'exportUrl' => route('students.export', [
                'search' => $this->student->student_number,
                'format' => 'xlsx',
            ]),
        ];
    }

    public function render()
    {
        return view('livewire.students.show', $this->viewData())
            ->title($this->student->full_name);
    }
}
