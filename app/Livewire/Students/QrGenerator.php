<?php

namespace App\Livewire\Students;

use App\Models\Student;
use App\Services\Students\StudentListService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

#[Layout('layouts.app')]
#[Title('QR Code Generator')]
class QrGenerator extends Component
{
    public string $search = '';

    public array $selectedIds = [];

    public function toggleStudent(int $id): void
    {
        if (in_array($id, $this->selectedIds, true)) {
            $this->selectedIds = array_values(array_diff($this->selectedIds, [$id]));
        } else {
            $this->selectedIds[] = $id;
        }
    }

    public function selectAll(): void
    {
        $this->selectedIds = Student::query()
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('student_number', 'like', "%{$this->search}%");
                });
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    protected function viewData(): array
    {
        $students = Student::query()
            ->with(['gradeLevel', 'section'])
            ->when($this->search, function ($q) {
                $q->where(function ($query) {
                    $query->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('student_number', 'like', "%{$this->search}%");
                });
            })
            ->tap(fn ($query) => StudentListService::orderByGenderThenName($query))
            ->limit(50)
            ->get();

        $qrCodes = $students
            ->whereIn('id', $this->selectedIds)
            ->mapWithKeys(function (Student $student) {
                $code = $student->qr_code ?? $student->student_number;

                return [
                    $student->id => base64_encode(
                        (string) QrCode::format('png')->size(200)->margin(1)->generate($code)
                    ),
                ];
            });

        return [
            'students' => $students,
            'qrCodes' => $qrCodes,
        ];
    }

    public function render()
    {
        return view('livewire.students.qr-generator', $this->viewData());
    }
}
