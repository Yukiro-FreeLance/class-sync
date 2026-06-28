<?php

namespace App\Livewire\Setup;

use App\Livewire\Setup\Concerns\ManagesWizardSession;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.setup')]
class Wizard extends Component
{
    use ManagesWizardSession;

    public const TOTAL_STEPS = 7;

    public int $currentStep = 1;

    /**
     * @return array<int, array{title: string, description: string}>
     */
    public function steps(): array
    {
        return [
            1 => ['title' => 'Welcome', 'description' => 'Introduction'],
            2 => ['title' => 'System Check', 'description' => 'Requirements'],
            3 => ['title' => 'Database', 'description' => 'Connection'],
            4 => ['title' => 'Application', 'description' => 'Configuration'],
            5 => ['title' => 'Admin Account', 'description' => 'Administrator'],
            6 => ['title' => 'Installation', 'description' => 'Setup'],
            7 => ['title' => 'Finish', 'description' => 'Complete'],
        ];
    }

    public function mount(): void
    {
        $this->currentStep = (int) session(self::WIZARD_SESSION_KEY.'.step', 1);
        $this->currentStep = max(1, min(self::TOTAL_STEPS, $this->currentStep));
    }

    #[On('wizard-next')]
    public function nextStep(): void
    {
        if ($this->currentStep < self::TOTAL_STEPS) {
            $this->currentStep++;
            $this->persistStep();
        }
    }

    #[On('wizard-back')]
    public function previousStep(): void
    {
        if ($this->currentStep > 1 && $this->currentStep < 6) {
            $this->currentStep--;
            $this->persistStep();
        }
    }

    #[On('wizard-go-to')]
    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= self::TOTAL_STEPS && $step < $this->currentStep && $step < 6) {
            $this->currentStep = $step;
            $this->persistStep();
        }
    }

    protected function persistStep(): void
    {
        session([self::WIZARD_SESSION_KEY.'.step' => $this->currentStep]);
    }

    public function render()
    {
        return view('livewire.setup.wizard', [
            'steps' => $this->steps(),
        ]);
    }
}
