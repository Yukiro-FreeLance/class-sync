<?php

namespace App\Livewire\Attendance;

use App\Enums\AttendanceMethod;
use App\Services\Attendance\AttendanceService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Attendance Scanner')]
class Scanner extends Component
{
    public string $mode = 'qr';

    public string $scanInput = '';

    public string $gate = 'Main Gate';

    public array $recentScans = [];

    public function processScan(AttendanceService $service): void
    {
        $code = trim($this->scanInput);

        if ($code === '') {
            return;
        }

        try {
            $method = $this->mode === 'rfid' ? AttendanceMethod::Rfid : AttendanceMethod::QrCode;
            $record = $service->recordByIdentifier($code, $method, auth()->id());
            $student = $record->student;
            $action = $record->time_out ? 'out' : 'in';

            array_unshift($this->recentScans, [
                'name' => $student->full_name,
                'id' => $student->student_number,
                'type' => $action,
                'time' => now()->format('H:i:s'),
            ]);

            $this->recentScans = array_slice($this->recentScans, 0, 10);
            $this->dispatch('toast', message: "{$student->full_name} checked {$action}.", type: 'success');
        } catch (\Throwable $e) {
            $this->dispatch('toast', message: $e->getMessage(), type: 'error');
        }

        $this->scanInput = '';
    }

    public function render()
    {
        return view('livewire.attendance.scanner', $this->viewData());
    }
}
