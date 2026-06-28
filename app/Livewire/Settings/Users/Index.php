<?php

namespace App\Livewire\Settings\Users;

use App\Enums\UserRole;
use App\Models\User;
use App\Services\Users\SuperAdminService;
use App\Services\Users\UserAccessService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
#[Title('Users')]
class Index extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $role = '';

    public function mount(): void
    {
        $this->authorize('viewAny', User::class);
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingRole(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $userId, SuperAdminService $superAdmin): void
    {
        $user = User::query()->findOrFail($userId);

        $superAdmin->ensureCanManageUser(auth()->user(), $user);

        $this->authorize('update', $user);

        if ($user->id === auth()->id()) {
            $this->dispatch('toast', message: 'You cannot deactivate your own account.', type: 'warning');

            return;
        }

        $user->update(['is_active' => ! $user->is_active]);
        $this->dispatch('toast', message: 'User status updated.', type: 'success');
    }

    public function render(UserAccessService $accessService)
    {
        $viewer = auth()->user();

        $users = User::query()
            ->visibleTo($viewer)
            ->with('roles')
            ->when($this->search, function ($query) {
                $query->where(function ($builder) {
                    $builder->where('first_name', 'like', "%{$this->search}%")
                        ->orWhere('last_name', 'like', "%{$this->search}%")
                        ->orWhere('username', 'like', "%{$this->search}%")
                        ->orWhere('email', 'like', "%{$this->search}%");
                });
            })
            ->when($this->role, fn ($query) => $query->role($this->role))
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(15);

        return view('livewire.settings.users.index', [
            'users' => $users,
            'roles' => $accessService->assignableRolesFor($viewer),
        ]);
    }
}
