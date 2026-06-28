<?php

namespace App\Livewire\Setup\Steps;

use App\Livewire\Setup\Concerns\ManagesWizardSession;
use App\Services\Setup\SetupPayloadStore;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;
use Livewire\Component;

class AdminAccount extends Component
{
    use ManagesWizardSession;

    public string $first_name = '';

    public string $last_name = '';

    public string $username = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $saved = $this->wizardData('admin', []);

        $this->first_name = $saved['first_name'] ?? '';
        $this->last_name = $saved['last_name'] ?? '';
        $this->username = $saved['username'] ?? '';
        $this->email = $saved['email'] ?? '';
    }

    protected function rules(): array
    {
        return [
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => 'required|string|max:50|alpha_dash',
            'email' => 'required|email|max:255',
            'password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    public function save(SetupPayloadStore $payloadStore): void
    {
        $this->validate();

        $admin = [
            'first_name' => $this->first_name,
            'last_name' => $this->last_name,
            'username' => Str::lower(trim($this->username)),
            'email' => Str::lower(trim($this->email)),
            'password' => $this->password,
        ];

        $this->setWizardData('admin', $admin);
        $payloadStore->saveAdmin($admin);

        $this->dispatch('wizard-next');
    }

    public function render()
    {
        return view('livewire.setup.steps.admin-account');
    }
}
