<?php

namespace App\Livewire\Teachers;

use App\Models\User;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Teachers')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $status = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $userId): void
    {
        $teacher = User::query()->assignableAsTeacher()->findOrFail($userId);
        $this->authorize('update', $teacher);

        $teacher->update(['is_active' => ! $teacher->is_active]);
        $this->dispatch('toast', message: 'Teacher status updated.', type: 'success');
    }

    public function render()
    {
        $teachers = User::query()
            ->assignableAsTeacher()
            ->withCount(['advisedSections', 'classSchedules'])
            ->when($this->search, function ($query) {
                $query->where(function ($q) {
                    $q->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->status === 'active', fn ($q) => $q->where('is_active', true))
            ->when($this->status === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('livewire.teachers.index', [
            'teachers' => $teachers,
        ]);
    }
}
