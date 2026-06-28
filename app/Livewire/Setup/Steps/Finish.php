<?php

namespace App\Livewire\Setup\Steps;

use App\Livewire\Setup\Concerns\ManagesWizardSession;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

class Finish extends Component
{
    use ManagesWizardSession;

    public string $appName = '';

    public function mount(): void
    {
        $application = $this->wizardData('application', []);
        $this->appName = $application['app_name'] ?? config('app.name', 'Class Sync');

        $adminUserId = session('setup.wizard.admin_user_id');

        if ($adminUserId && ! Auth::check()) {
            $user = User::query()->find($adminUserId);

            if ($user) {
                Auth::login($user);
            }
        }
    }

    public function goToDashboard(): void
    {
        $this->clearWizardSession();

        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.setup.steps.finish');
    }
}
