<div>
    <form wire:submit="save" class="space-y-5">
        <div class="grid sm:grid-cols-2 gap-4">
            <div class="sm:col-span-2">
                <label for="app_name" class="block text-sm font-medium text-indigo-100">Application Name</label>
                <input wire:model="app_name" type="text" id="app_name" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                <x-input-error :messages="$errors->get('app_name')" class="mt-2" />
            </div>

            <div>
                <label for="timezone" class="block text-sm font-medium text-indigo-100">Timezone</label>
                <select wire:model="timezone" id="timezone" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm">
                    @foreach ($this->timezoneOptions as $tz)
                        <option value="{{ $tz }}" class="bg-gray-900">{{ $tz }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('timezone')" class="mt-2" />
            </div>

            <div>
                <label for="locale" class="block text-sm font-medium text-indigo-100">Locale</label>
                <select wire:model="locale" id="locale" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm">
                    @foreach ($this->localeOptions as $value => $label)
                        <option value="{{ $value }}" class="bg-gray-900">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('locale')" class="mt-2" />
            </div>

            <div>
                <label for="currency" class="block text-sm font-medium text-indigo-100">Currency</label>
                <select wire:model="currency" id="currency" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm">
                    @foreach ($this->currencyOptions as $value => $label)
                        <option value="{{ $value }}" class="bg-gray-900">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('currency')" class="mt-2" />
            </div>

            <div>
                <label for="semester" class="block text-sm font-medium text-indigo-100">Current Semester</label>
                <select wire:model="semester" id="semester" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm">
                    @foreach ($this->semesterOptions as $value => $label)
                        <option value="{{ $value }}" class="bg-gray-900">{{ $label }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('semester')" class="mt-2" />
            </div>
        </div>

        <div class="border-t border-white/10 pt-5">
            <h4 class="text-sm font-semibold text-white mb-4">School Information</h4>

            <div class="space-y-4">
                <div>
                    <label for="school_name" class="block text-sm font-medium text-indigo-100">School Name</label>
                    <input wire:model="school_name" type="text" id="school_name" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" />
                    <x-input-error :messages="$errors->get('school_name')" class="mt-2" />
                </div>

                <div>
                    <label for="school_address" class="block text-sm font-medium text-indigo-100">School Address</label>
                    <textarea wire:model="school_address" id="school_address" rows="2" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm"></textarea>
                    <x-input-error :messages="$errors->get('school_address')" class="mt-2" />
                </div>

                <div>
                    <label for="academic_year" class="block text-sm font-medium text-indigo-100">Academic Year</label>
                    <input wire:model="academic_year" type="text" id="academic_year" class="mt-1 block w-full rounded-xl border-white/20 bg-white/5 text-white shadow-sm focus:border-indigo-400 focus:ring-indigo-400 text-sm" placeholder="2025-2026" />
                    <x-input-error :messages="$errors->get('academic_year')" class="mt-2" />
                </div>

                <div>
                    <label for="logo" class="block text-sm font-medium text-indigo-100">School Logo</label>
                    <input wire:model="logo" type="file" id="logo" accept="image/*" class="mt-1 block w-full text-sm text-indigo-200 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-500/20 file:text-indigo-200 hover:file:bg-indigo-500/30" />
                    <x-input-error :messages="$errors->get('logo')" class="mt-2" />
                    <div wire:loading wire:target="logo" class="mt-2 text-xs text-indigo-300/60">Uploading...</div>
                    @if ($logo)
                        <p class="mt-2 text-xs text-emerald-300">Logo ready for installation.</p>
                    @elseif ($logo_path)
                        <p class="mt-2 text-xs text-indigo-300/60">Logo saved from previous session.</p>
                    @endif
                </div>
            </div>
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
                <svg wire:loading wire:target="save" class="animate-spin w-4 h-4" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Save &amp; Continue
            </button>
        </div>
    </form>
</div>
