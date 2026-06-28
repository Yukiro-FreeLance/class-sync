<?php

namespace App\Livewire\Setup\Steps;

use App\DTOs\Setup\DatabaseConfigDTO;
use App\Enums\DatabaseDriver;
use App\Livewire\Setup\Concerns\ManagesWizardSession;
use App\Services\Setup\DatabaseConfigService;
use Livewire\Component;

class DatabaseConfig extends Component
{
    use ManagesWizardSession;

    public string $driver = 'mysql';

    public string $host = '127.0.0.1';

    public int $port = 3306;

    public string $database = 'class_sync';

    public string $username = '';

    public string $password = '';

    public ?string $connectionMessage = null;

    public bool $connectionSuccess = false;

    public bool $saved = false;

    public function mount(): void
    {
        $saved = $this->wizardData('database', []);

        if ($saved) {
            $this->driver = $saved['driver'] ?? 'mysql';
            $this->host = $saved['host'] ?? '127.0.0.1';
            $this->port = (int) ($saved['port'] ?? 3306);
            $this->database = $saved['database'] ?? '';
            $this->username = $saved['username'] ?? '';
            $this->password = $saved['password'] ?? '';
            $this->saved = (bool) ($saved['saved'] ?? false);
            $this->connectionSuccess = $this->saved;
        }
    }

    public function updatedDriver(string $value): void
    {
        $this->connectionMessage = null;
        $this->connectionSuccess = false;
        $this->saved = false;

        if ($value === 'sqlite') {
            $this->database = $this->database ?: 'database.sqlite';
        } elseif ($value === 'mariadb') {
            $this->port = 3306;
        } else {
            $this->port = 3306;
        }
    }

    protected function rules(): array
    {
        $rules = [
            'driver' => 'required|in:mysql,mariadb,sqlite',
        ];

        if ($this->driver === 'sqlite') {
            $rules['database'] = 'required|string';
        } else {
            $rules['host'] = 'required|string';
            $rules['port'] = 'required|integer|min:1|max:65535';
            $rules['database'] = 'required|string';
            $rules['username'] = 'required|string';
            $rules['password'] = 'nullable|string';
        }

        return $rules;
    }

    protected function dto(): DatabaseConfigDTO
    {
        return DatabaseConfigDTO::fromArray([
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
        ]);
    }

    public function testConnection(DatabaseConfigService $databaseConfig): void
    {
        $this->validate();
        $this->connectionMessage = null;
        $this->connectionSuccess = false;

        $result = $databaseConfig->testConnection($this->dto());

        $this->connectionSuccess = $result['success'];
        $this->connectionMessage = $result['message'];
    }

    public function save(DatabaseConfigService $databaseConfig): void
    {
        $this->validate();

        if (! $this->connectionSuccess) {
            $result = $databaseConfig->testConnection($this->dto());

            if (! $result['success']) {
                $this->connectionSuccess = false;
                $this->connectionMessage = $result['message'];

                return;
            }
        }

        $databaseConfig->writeConfig($this->dto());

        $this->setWizardData('database', [
            'driver' => $this->driver,
            'host' => $this->host,
            'port' => $this->port,
            'database' => $this->database,
            'username' => $this->username,
            'password' => $this->password,
            'saved' => true,
        ]);

        $this->saved = true;
        $this->connectionSuccess = true;
        $this->connectionMessage = 'Database configuration saved successfully.';
        $this->dispatch('wizard-next');
    }

    /**
     * @return array<string, string>
     */
    public function getDriverOptionsProperty(): array
    {
        return DatabaseDriver::options();
    }

    public function getIsSqliteProperty(): bool
    {
        return $this->driver === 'sqlite';
    }

    public function render()
    {
        return view('livewire.setup.steps.database-config');
    }
}
