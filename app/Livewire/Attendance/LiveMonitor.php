<?php

namespace App\Livewire\Attendance;

use App\Services\Attendance\LiveMonitorService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Live Monitor')]
class LiveMonitor extends Component
{
    public function render(LiveMonitorService $monitor)
    {
        return view('livewire.attendance.live-monitor', $monitor->snapshot());
    }
}
