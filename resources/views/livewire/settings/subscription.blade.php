<div>
    <x-page-header title="System Subscription" subtitle="Set a time-bomb expiry that locks the system for all users except Super Admin">
        <x-slot name="actions">
            <x-primary-button type="submit" form="subscription-settings-form">Save Subscription</x-primary-button>
        </x-slot>
    </x-page-header>

    @if (session('status'))
        <div class="mb-6 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800 dark:border-emerald-800 dark:bg-emerald-900/20 dark:text-emerald-300">
            {{ session('status') }}
        </div>
    @endif

    <div class="grid gap-6 xl:grid-cols-3 mb-6 max-w-6xl">
        <section class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Current Status</h3>
            <p class="text-sm text-slate-500 mb-4">How the system looks to normal users right now.</p>

            <div class="space-y-3">
                <div class="flex items-center justify-between gap-3 rounded-xl border border-surface-border dark:border-slate-800 px-4 py-3">
                    <span class="text-sm text-slate-500">Access</span>
                    @if (! $status['is_configured'])
                        <span class="badge bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">Unlimited</span>
                    @elseif ($status['is_active'])
                        <span class="badge bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">Active</span>
                    @else
                        <span class="badge bg-rose-100 text-rose-700 dark:bg-rose-900/30 dark:text-rose-400">Expired</span>
                    @endif
                </div>

                <div class="flex items-center justify-between gap-3 rounded-xl border border-surface-border dark:border-slate-800 px-4 py-3">
                    <span class="text-sm text-slate-500">Expires</span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                        {{ $status['expires_at'] ? \Illuminate\Support\Carbon::parse($status['expires_at'])->format('M j, Y') : 'Never' }}
                    </span>
                </div>

                <div class="flex items-center justify-between gap-3 rounded-xl border border-surface-border dark:border-slate-800 px-4 py-3">
                    <span class="text-sm text-slate-500">Days remaining</span>
                    <span class="text-sm font-medium text-slate-900 dark:text-white">
                        @if (! $status['is_configured'])
                            —
                        @elseif ($status['days_remaining'] === null)
                            —
                        @elseif ($status['days_remaining'] < 0)
                            Expired {{ abs($status['days_remaining']) }} day{{ abs($status['days_remaining']) === 1 ? '' : 's' }} ago
                        @elseif ($status['days_remaining'] === 0)
                            Expires today
                        @else
                            {{ $status['days_remaining'] }} day{{ $status['days_remaining'] === 1 ? '' : 's' }}
                        @endif
                    </span>
                </div>
            </div>

            @if ($status['is_configured'] && ! $status['is_active'])
                <p class="mt-4 text-sm text-rose-600 dark:text-rose-400">
                    Normal users are redirected to the downtime page. Only Super Admin can still use the system.
                </p>
            @endif
        </section>

        <section class="panel xl:col-span-2">
            <div class="flex items-start gap-3 mb-5">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                    <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                            d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-lg text-slate-900 dark:text-white">Time Bomb Expiry</h3>
                    <p class="text-sm text-slate-500 mt-0.5">
                        After this date, every non–Super Admin user is locked out with a “system is down” page.
                    </p>
                </div>
            </div>

            <form id="subscription-settings-form" wire:submit="save" class="space-y-4">
                <div>
                    <x-input-label for="expires_at" value="Subscription end date" />
                    <x-text-input wire:model="expires_at" id="expires_at" type="date" class="mt-1 block w-full max-w-xs" />
                    <p class="text-xs text-slate-500 mt-1">Leave empty for unlimited access (no lockout).</p>
                    <x-input-error :messages="$errors->get('expires_at')" class="mt-1" />
                </div>

                <div>
                    <x-input-label for="message" value="Downtime message" />
                    <textarea wire:model="message" id="message" rows="3" class="mt-1 input-field"
                        placeholder="Shown to users when the subscription has expired"></textarea>
                    <x-input-error :messages="$errors->get('message')" class="mt-1" />
                </div>

                <div class="flex flex-wrap gap-3 pt-2">
                    <x-primary-button type="submit">Save Subscription</x-primary-button>
                    <button type="button" wire:click="clearExpiry" wire:confirm="Clear the expiry date and allow unlimited access?"
                        class="btn-secondary">
                        Clear Expiry
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>
