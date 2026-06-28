<div>

    <x-page-header title="Roles & Restrictions" subtitle="Configure what each role is allowed to do" />



    <x-settings-users-nav />



    <div class="grid lg:grid-cols-3 gap-6">

        <div class="panel lg:col-span-1">

            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Roles</h3>

            <div class="space-y-1">

                @foreach ($roles as $roleModel)

                    <button type="button" wire:click="$set('role', '{{ $roleModel->name }}')"

                        @class([

                            'w-full text-left px-3 py-2 rounded-lg text-sm transition flex items-center justify-between gap-2',

                            'bg-green-700 text-white' => $role === $roleModel->name,

                            'hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300' => $role !== $roleModel->name,

                            'opacity-60' => ! $roleModel->is_enabled,

                        ])>

                        <span>{{ \App\Enums\UserRole::tryFrom($roleModel->name)?->label() ?? ucfirst($roleModel->name) }}</span>

                        @unless ($roleModel->is_enabled)

                            <span @class([

                                'text-[10px] uppercase tracking-wide font-semibold px-1.5 py-0.5 rounded',

                                'bg-white/20 text-white' => $role === $roleModel->name,

                                'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-300' => $role !== $roleModel->name,

                            ])>Disabled</span>

                        @endunless

                    </button>

                @endforeach

            </div>

        </div>



        <form wire:submit="save" class="panel lg:col-span-2">

            <div class="flex items-center justify-between gap-3 mb-6">

                <div>

                    <h3 class="font-semibold text-slate-900 dark:text-white">

                        {{ \App\Enums\UserRole::tryFrom($role)?->label() ?? ucfirst($role) }}

                    </h3>

                    <p class="text-sm text-slate-500 mt-1">Permission restrictions for this role.</p>

                </div>

                <div class="flex items-center gap-2">

                    @unless ($isProtectedRole)

                        <button type="button" wire:click="toggleEnabled"

                            @class([

                                'btn-secondary text-sm',

                                'border-amber-300 text-amber-700 dark:border-amber-800 dark:text-amber-300' => $roleEnabled,

                                'border-green-300 text-green-700 dark:border-green-800 dark:text-green-300' => ! $roleEnabled,

                            ])>

                            {{ $roleEnabled ? 'Disable Role' : 'Enable Role' }}

                        </button>

                    @endunless

                    @unless ($isUnrestrictedRole)

                        <x-primary-button type="submit">Save Restrictions</x-primary-button>

                    @endunless

                </div>

            </div>



            @if ($isUnrestrictedRole)

                <div class="rounded-xl bg-slate-50 dark:bg-slate-900/40 p-6 text-sm text-slate-600 dark:text-slate-300">

                    @if ($role === config('classsync.roles.super_admin'))

                        Super Admins have unrestricted access to every feature and policy check.

                        Only other Super Admins can assign this role.

                    @else

                        Administrators always have full system access and cannot be restricted.

                        Use <strong>Also act as Teacher</strong> on a user account if an admin should be scoped to specific classes.

                    @endif

                </div>

            @elseif (! $roleEnabled)

                <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 p-6 text-sm text-amber-800 dark:text-amber-200">

                    This role is disabled and cannot be assigned to new users. Existing users keep the role until you change their account.

                    Enable the role to make it available again.

                </div>

            @else

                <div class="space-y-6">

                    @foreach ($permissionGroups as $group => $permissions)

                        <div>

                            <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 mb-3">{{ $group }}</p>

                            <div class="grid sm:grid-cols-2 gap-2">

                                @foreach ($permissions as $permission)

                                    <label class="inline-flex items-center gap-2 text-sm rounded-lg border border-surface-border dark:border-slate-800 px-3 py-2">

                                        <input type="checkbox" wire:model="selectedPermissions" value="{{ $permission }}" class="rounded text-green-700">

                                        {{ $permissionLabels($permission) }}

                                    </label>

                                @endforeach

                            </div>

                        </div>

                    @endforeach

                </div>

            @endif

        </form>

    </div>

</div>

