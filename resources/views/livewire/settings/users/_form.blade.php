<div class="grid sm:grid-cols-2 gap-6">
    <div>
        <x-input-label for="first_name" value="First Name" />
        <x-text-input wire:model="first_name" id="first_name" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('first_name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="last_name" value="Last Name" />
        <x-text-input wire:model="last_name" id="last_name" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('last_name')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="username" value="Username" />
        <x-text-input wire:model="username" id="username" class="mt-1 block w-full font-mono" />
        <x-input-error :messages="$errors->get('username')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="email" value="Email" />
        <x-text-input wire:model="email" id="email" type="email" class="mt-1 block w-full" />
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password" value="{{ ($editing ?? false) ? 'New Password' : 'Password' }}" />
        <x-text-input wire:model="password" id="password" type="password" class="mt-1 block w-full" />
        @if ($editing ?? false)
            <p class="text-xs text-slate-500 mt-1">Leave blank to keep current password.</p>
        @endif
        <x-input-error :messages="$errors->get('password')" class="mt-1" />
    </div>

    <div>
        <x-input-label for="password_confirmation" value="Confirm Password" />
        <x-text-input wire:model="password_confirmation" id="password_confirmation" type="password" class="mt-1 block w-full" />
    </div>

    <div class="sm:col-span-2">
        <x-input-label value="Roles" />
        <div class="mt-2 grid sm:grid-cols-2 gap-2">
            @foreach ($assignableRoles as $role)
                <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-surface-border dark:border-slate-800 px-3 py-2">
                    <input type="checkbox" wire:model.live="selectedRoles" value="{{ $role->value }}" class="rounded text-green-700">
                    {{ $role->label() }}
                </label>
            @endforeach
        </div>
        <x-input-error :messages="$errors->get('selectedRoles')" class="mt-1" />
    </div>

    @if (collect($selectedRoles)->intersect([config('classsync.roles.super_admin'), config('classsync.roles.administrator')])->isNotEmpty())
        <div class="sm:col-span-2 rounded-xl border border-brand-100 dark:border-brand-900/40 bg-brand-50/50 dark:bg-brand-900/10 p-4">
            <label class="inline-flex items-start gap-3">
                <input type="checkbox" wire:model="acts_as_teacher" class="mt-1 rounded text-green-700">
                <span>
                    <span class="font-medium text-slate-900 dark:text-white">Also act as Teacher</span>
                    <span class="block text-sm text-slate-500 mt-1">
                        When enabled, this administrator or super admin is scoped to their assigned classes and sections like a teacher,
                        and appears in teacher pickers for schedules and advisory assignments.
                    </span>
                </span>
            </label>
        </div>
    @endif

    <div>
        <label class="inline-flex items-center gap-2 text-sm">
            <input type="checkbox" wire:model="is_active" class="rounded text-green-700">
            Active account
        </label>
        <x-input-error :messages="$errors->get('is_active')" class="mt-1" />
    </div>
</div>
