<?php

namespace App\Livewire\Settings;

use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('layouts.app')]
#[Title('Backups')]
class Backup extends Component
{
    public bool $isCreating = false;

    public function createBackup(): void
    {
        $this->isCreating = true;

        DB::table('backups')->insert([
            'filename' => 'backup_'.now()->format('Y-m-d_His').'.sql',
            'path' => 'backups/backup_'.now()->format('Y-m-d_His').'.sql',
            'size' => 0,
            'type' => 'database',
            'created_by' => auth()->id(),
            'status' => 'pending',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->isCreating = false;
        $this->dispatch('toast', message: 'Backup job queued. Full backup engine coming soon.', type: 'info');
    }

    public function restore(int $id): void
    {
        $this->dispatch('toast', message: 'Restore functionality will be available soon.', type: 'warning');
    }

    protected function viewData(): array
    {
        $backups = DB::table('backups')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get();

        return [
            'backups' => $backups,
        ];
    }

    public function render()
    {
        return view('livewire.settings.backup', $this->viewData());
    }
}
