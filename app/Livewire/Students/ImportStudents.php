<?php

namespace App\Livewire\Students;

use App\Models\Student;
use App\Services\Students\StudentImportService;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class ImportStudents extends Component
{
    use WithFileUploads;

    public bool $show = false;

    public int $step = 1;

    public $importFile;

    public bool $updateExisting = false;

    public int $importedCount = 0;

    public int $skippedCount = 0;

    /** @var list<array{row: int, message: string}> */
    public array $importErrors = [];

    /** @var list<array{row: int, message: string}> */
    public array $skippedRows = [];

    public bool $importing = false;

    #[On('open-student-import')]
    public function open(): void
    {
        $this->authorize('create', Student::class);

        $this->reset(['importFile', 'updateExisting', 'importedCount', 'skippedCount', 'importErrors', 'skippedRows', 'importing']);
        $this->step = 1;
        $this->show = true;
    }

    public function close(): void
    {
        $this->show = false;
    }

    public function goToUpload(): void
    {
        $this->step = 2;
    }

    public function import(StudentImportService $importService): void
    {
        $this->authorize('create', Student::class);

        $this->validate([
            'importFile' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        $this->importing = true;

        $result = $importService->import(
            $this->importFile,
            $this->updateExisting,
        );

        $this->importedCount = $result->imported;
        $this->skippedCount = $result->skipped;
        $this->importErrors = $result->errors;
        $this->skippedRows = $result->skippedRows;

        $this->importing = false;
        $this->step = 3;

        if ($result->imported > 0) {
            $this->dispatch('students-imported');
        }
    }

    public function resetImport(): void
    {
        $this->reset(['importFile', 'importedCount', 'skippedCount', 'importErrors', 'skippedRows']);
        $this->step = 2;
    }

    public function render()
    {
        return view('livewire.students.import-students');
    }
}
