<div>
    <div class="mb-8">
        <a href="{{ route('teachers.index') }}" wire:navigate class="text-sm text-green-700 hover:text-brand-500">&larr; Back to Teachers</a>
        <h1 class="text-2xl font-bold text-slate-900 dark:text-white mt-2">Add Teacher</h1>
        <p class="text-sm text-slate-500 mt-1">Create a teacher account for class schedules and attendance.</p>
    </div>

    <form wire:submit="save" class="panel max-w-2xl space-y-5">
        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="first_name" value="First Name" />
                <x-text-input wire:model="first_name" id="first_name" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="last_name" value="Last Name" />
                <x-text-input wire:model="last_name" id="last_name" class="mt-1 block w-full" required />
                <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
            </div>
        </div>

        <div>
            <x-input-label for="username" value="Username" />
            <x-text-input wire:model="username" id="username" class="mt-1 block w-full font-mono" required autocomplete="username" />
            <x-input-error :messages="$errors->get('username')" class="mt-1" />
        </div>

        <div>
            <x-input-label for="email" value="Email" />
            <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" required autocomplete="email" />
            <x-input-error :messages="$errors->get('email')" class="mt-1" />
        </div>

        <div class="grid sm:grid-cols-2 gap-5">
            <div>
                <x-input-label for="password" value="Password" />
                <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
                <x-input-error :messages="$errors->get('password')" class="mt-1" />
            </div>
            <div>
                <x-input-label for="password_confirmation" value="Confirm Password" />
                <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" class="mt-1 block w-full" required autocomplete="new-password" />
            </div>
        </div>

        <label class="flex items-center gap-2.5 cursor-pointer">
            <input type="checkbox" wire:model="is_active" class="rounded border-surface-border text-green-700 focus:ring-brand-500">
            <span class="text-sm text-slate-600 dark:text-slate-300">Account is active (can sign in)</span>
        </label>

        <div class="flex gap-3 pt-2">
            <x-primary-button type="submit">Create Teacher</x-primary-button>
            <a href="{{ route('teachers.index') }}" wire:navigate class="btn-secondary">Cancel</a>
        </div>
    </form>
</div>
