<div>
    <form wire:submit="save" class="space-y-5">
        <div>
            <label for="driver" class="block text-sm font-medium text-indigo-100">Database Type</label>
            <select
                wire:model.live="driver"
                id="driver"
                class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm"
            >
                @foreach ($this->driverOptions as $value => $label)
                    <option value="{{ $value }}" class="bg-gray-900">{{ $label }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('driver')" class="mt-2" />
        </div>

        @unless ($this->isSqlite)
            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="host" class="block text-sm font-medium text-indigo-100">Host</label>
                    <input wire:model="host" type="text" id="host" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white placeholder-indigo-300/40 shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" placeholder="127.0.0.1" />
                    <x-input-error :messages="$errors->get('host')" class="mt-2" />
                </div>
                <div>
                    <label for="port" class="block text-sm font-medium text-indigo-100">Port</label>
                    <input wire:model="port" type="number" id="port" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                    <x-input-error :messages="$errors->get('port')" class="mt-2" />
                </div>
            </div>

            <div>
                <label for="database" class="block text-sm font-medium text-indigo-100">Database Name</label>
                <input wire:model="database" type="text" id="database" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white placeholder-indigo-300/40 shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" placeholder="class_sync" />
                <x-input-error :messages="$errors->get('database')" class="mt-2" />
            </div>

            <div class="grid sm:grid-cols-2 gap-4">
                <div>
                    <label for="username" class="block text-sm font-medium text-indigo-100">Username</label>
                    <input wire:model="username" type="text" id="username" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white placeholder-indigo-300/40 shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                </div>
                <div>
                    <label for="password" class="block text-sm font-medium text-indigo-100">Password</label>
                    <input wire:model="password" type="password" id="password" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                    <x-input-error :messages="$errors->get('password')" class="mt-2" />
                </div>
            </div>
        @else
            <div>
                <label for="database" class="block text-sm font-medium text-indigo-100">Database File</label>
                <input wire:model="database" type="text" id="database" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white placeholder-indigo-300/40 shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" placeholder="database.sqlite" />
                <p class="mt-1 text-xs text-indigo-300/50">Relative to the database/ directory or an absolute path.</p>
                <x-input-error :messages="$errors->get('database')" class="mt-2" />
            </div>
        @endunless

        @if ($connectionMessage)
            <div @class([
                'rounded-xl p-4 text-sm border',
                'bg-emerald-500/10 border-emerald-500/30 text-emerald-300' => $connectionSuccess,
                'bg-red-500/10 border-red-500/30 text-red-300' => ! $connectionSuccess,
            ])>
                {{ $connectionMessage }}
            </div>
        @endif

        <div class="flex items-center justify-between pt-2">
            <button
                type="button"
                wire:click="testConnection"
                class="inline-flex items-center gap-2 px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white bg-white/5 hover:bg-white/10 border border-white/10 rounded-xl transition-colors"
            >
                <svg wire:loading wire:target="testConnection" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Test Connection
            </button>

            <div class="flex items-center gap-3">
                <button
                    type="button"
                    wire:click="$dispatch('wizard-back')"
                    class="px-4 py-2 text-sm font-medium text-indigo-200 hover:text-white transition-colors"
                >
                    Back
                </button>
                <button
                    type="submit"
                    class="inline-flex items-center gap-2 px-6 py-2.5 bg-indigo-500 hover:bg-indigo-400 text-white text-sm font-semibold rounded-xl shadow-lg shadow-indigo-500/25 transition-all duration-200"
                >
                    <svg wire:loading wire:target="save" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Save &amp; Continue
                </button>
            </div>
        </div>
    </form>
</div>
