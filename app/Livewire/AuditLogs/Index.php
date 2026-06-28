<?php

namespace App\Livewire\AuditLogs;

use App\Enums\AuditAction;
use App\Models\AuditLog;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Audit Logs')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $action = '';

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingAction(): void
    {
        $this->resetPage();
    }

    protected function viewData(): array
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('action', 'like', "%{$this->search}%")
                        ->orWhere('model_type', 'like', "%{$this->search}%")
                        ->orWhereHas('user', fn ($uq) => $uq->where('name', 'like', "%{$this->search}%"));
                });
            })
            ->when($this->action, fn ($q) => $q->where('action', $this->action))
            ->orderByDesc('created_at')
            ->paginate(25);

        return [
            'logs' => $logs,
            'actions' => AuditAction::options(),
        ];
    }

    public function render()
    {
        return view('livewire.audit-logs.index', $this->viewData());
    }
}
