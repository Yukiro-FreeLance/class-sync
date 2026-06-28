<?php

namespace App\Livewire\Dashboard;

use App\Services\Dashboard\DashboardService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Dashboard')]
class Index extends Component
{
    public function render(DashboardService $dashboard)
    {
        $data = $dashboard->data();

        return view('livewire.dashboard.index', array_merge($data, [
            'greeting' => $this->greeting(),
            'dashboardService' => $dashboard,
        ]));
    }

    private function greeting(): string
    {
        $hour = (int) now()->format('H');

        if ($hour < 12) {
            return 'Good morning';
        }

        if ($hour < 17) {
            return 'Good afternoon';
        }

        return 'Good evening';
    }
}
