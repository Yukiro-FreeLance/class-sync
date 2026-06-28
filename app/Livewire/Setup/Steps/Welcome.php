<?php

namespace App\Livewire\Setup\Steps;

use Livewire\Component;

class Welcome extends Component
{
    public function getAppVersionProperty(): string
    {
        return '1.0.0';
    }

    public function getLaravelVersionProperty(): string
    {
        return app()->version();
    }

    public function getPhpVersionProperty(): string
    {
        return PHP_VERSION;
    }

    /**
     * @return array<int, array{label: string, value: string}>
     */
    public function getRequirementsProperty(): array
    {
        return [
            ['label' => 'PHP Version', 'value' => '8.3 or higher'],
            ['label' => 'Extensions', 'value' => 'PDO, OpenSSL, Mbstring, Fileinfo, Zip, BCMath'],
            ['label' => 'Writable Directories', 'value' => 'storage/, bootstrap/cache/'],
            ['label' => 'Database', 'value' => 'MySQL, MariaDB, or SQLite'],
        ];
    }

    public function continue(): void
    {
        $this->dispatch('wizard-next');
    }

    public function render()
    {
        return view('livewire.setup.steps.welcome');
    }
}
