<div>
    <form wire:submit="save" class="space-y-5">
        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="first_name" class="block text-sm font-medium text-indigo-100">First Name</label>
                <input wire:model="first_name" type="text" id="first_name" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" autofocus />
                <x-input-error :messages="$errors->get('first_name')" class="mt-2" />
            </div>
            <div>
                <label for="last_name" class="block text-sm font-medium text-indigo-100">Last Name</label>
                <input wire:model="last_name" type="text" id="last_name" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                <x-input-error :messages="$errors->get('last_name')" class="mt-2" />
            </div>
        </div>

        <div>
            <label for="username" class="block text-sm font-medium text-indigo-100">Username</label>
            <input wire:model="username" type="text" id="username" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
            <x-input-error :messages="$errors->get('username')" class="mt-2" />
        </div>

        <div>
            <label for="email" class="block text-sm font-medium text-indigo-100">Email Address</label>
            <input wire:model="email" type="email" id="email" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="grid sm:grid-cols-2 gap-4">
            <div>
                <label for="password" class="block text-sm font-medium text-indigo-100">Password</label>
                <input wire:model="password" type="password" id="password" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div>
                <label for="password_confirmation" class="block text-sm font-medium text-indigo-100">Confirm Password</label>
                <input wire:model="password_confirmation" type="password" id="password_confirmation" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
            </div>
        </div>

        <div class="rounded-xl bg-indigo-500/10 border border-indigo-400/20 p-4">
            <p class="text-xs text-indigo-200/80">This account will be created as the system administrator with full access to all features.</p>
        </div>

        <div class="flex items-center justify-between pt-2">
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
                Save &amp; Continue
            </button>
        </div>
    </form>
</div>
