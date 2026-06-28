<div>
    <div class="mb-8">
        <a href="{{ route('settings.users.index') }}" wire:navigate class="text-sm text-green-700 hover:text-brand-500">&larr; Back to Users</a>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white mt-2">Add User</h1>
    </div>

    <x-settings-users-nav />

    <form wire:submit="save" class="panel max-w-3xl">
        @include('livewire.settings.users._form')

        <div class="mt-8 flex gap-3">
            <x-primary-button type="submit">Create User</x-primary-button>
            <a href="{{ route('settings.users.index') }}" wire:navigate class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
