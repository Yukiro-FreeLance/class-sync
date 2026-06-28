<?php

namespace App\Livewire\Setup\Steps;

use App\Services\Setup\SystemCheckService;
use Livewire\Component;

class SystemCheck extends Component
{
    /** @var array<string, array{status: string, message: string, details?: array<string, array{status: string, message: string}>}> */
    public array $checks = [];

    public bool $ran = false;

    public function mount(SystemCheckService $systemCheck): void
    {
        $this->runChecks($systemCheck);
    }

    public function runChecks(SystemCheckService $systemCheck): void
    {
        $this->checks = $systemCheck->run();
        $this->ran = true;
    }

    public function getCanContinueProperty(): bool
    {
        foreach ($this->checks as $check) {
            if (($check['status'] ?? '') === 'fail') {
                return false;
            }
        }

        return $this->ran && count($this->checks) > 0;
    }

    public function continue(): void
    {
        if (! $this->canContinue) {
            return;
        }

        $this->dispatch('wizard-next');
    }

    public function render()
    {
        return view('livewire.setup.steps.system-check');
    }
}
