<div>
    <x-page-header title="General Settings" subtitle="School information, branding, and attendance rules">
        <x-slot name="actions">
            <x-primary-button type="submit" form="general-settings-form">Save Settings</x-primary-button>
        </x-slot>
    </x-page-header>

    <form id="general-settings-form" wire:submit="save" class="space-y-6 max-w-6xl">
        <div class="grid gap-6 lg:grid-cols-5">
            {{-- Left column --}}
            <div class="lg:col-span-3 space-y-6">
                <section class="panel">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-700 dark:bg-green-500/15 dark:text-green-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-slate-900 dark:text-white">School Information</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Basic details shown across reports and the sidebar.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="grid sm:grid-cols-2 gap-4">
                            <div class="sm:col-span-2">
                                <x-input-label for="school_name" value="School Name" />
                                <x-text-input wire:model="school_name" id="school_name" class="mt-1 block w-full" />
                                <x-input-error :messages="$errors->get('school_name')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="school_code" value="School Code" />
                                <x-text-input wire:model.live="school_code" id="school_code" maxlength="12"
                                    placeholder="e.g. SNHS" class="mt-1 block w-full uppercase" />
                                <p class="text-xs text-slate-500 mt-1">Sidebar acronym when no logo is set.</p>
                                <x-input-error :messages="$errors->get('school_code')" class="mt-1" />
                            </div>
                            <div>
                                <x-input-label for="school_phone" value="Phone" />
                                <x-text-input wire:model="school_phone" id="school_phone" class="mt-1 block w-full" />
                            </div>
                            <div class="sm:col-span-2">
                                <x-input-label for="school_address" value="Address" />
                                <textarea wire:model="school_address" id="school_address" rows="2" class="mt-1 input-field"></textarea>
                            </div>
                            <div>
                                <x-input-label for="school_email" value="Email" />
                                <x-text-input wire:model="school_email" id="school_email" type="email"
                                    class="mt-1 block w-full" />
                            </div>
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-violet-50 text-violet-700 dark:bg-violet-500/15 dark:text-violet-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-slate-900 dark:text-white">Branding</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Logo and sidebar subtitle.</p>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div>
                            <x-input-label for="logo" value="School Logo" />
                            <p class="text-sm text-slate-500 mb-3">PNG, JPG, or SVG up to 2 MB.</p>
                            <div class="flex flex-wrap items-start gap-4">
                                <div class="flex items-center gap-3 rounded-xl border border-surface-border dark:border-slate-700 p-3 bg-slate-50 dark:bg-slate-800/50 min-w-[220px]">
                                    @if ($logo)
                                        <img src="{{ $logo->temporaryUrl() }}" alt="Logo preview"
                                            class="h-10 w-10 rounded-xl object-contain">
                                    @elseif ($current_logo_url)
                                        <img src="{{ $current_logo_url }}" alt="Current logo"
                                            class="h-10 w-10 rounded-xl object-contain">
                                    @else
                                        <x-app-brand-mark :acronym="strtoupper($school_code) ?: 'CS'" :school-name="$school_name" />
                                    @endif
                                    <div class="text-sm min-w-0">
                                        <p class="font-medium text-slate-900 dark:text-white truncate">Sidebar preview</p>
                                        <p class="text-slate-500 truncate">
                                            @if ($school_code)
                                                <span class="font-semibold text-slate-700 dark:text-slate-200">{{ strtoupper($school_code) }}</span>
                                                <span class="mx-1">&middot;</span>
                                            @endif
                                            {{ $school_name ?: 'School name' }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex flex-col gap-2">
                                    <input wire:model="logo" id="logo" type="file" accept="image/*"
                                        class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-green-700 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-green-800">
                                    @if ($current_logo_url || $logo)
                                        <button type="button" wire:click="removeLogo" wire:confirm="Remove the current logo?"
                                            class="text-sm text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300 text-left">
                                            Remove logo
                                        </button>
                                    @endif
                                </div>
                            </div>
                            <x-input-error :messages="$errors->get('logo')" class="mt-1" />
                            <div wire:loading wire:target="logo" class="text-sm text-slate-500 mt-1">Uploading…</div>
                        </div>
                        <div>
                            <x-input-label for="app_subtitle" value="Sidebar Subtitle" />
                            <x-text-input wire:model="app_subtitle" id="app_subtitle" maxlength="50"
                                placeholder="Class Sync" class="mt-1 block w-full max-w-sm" />
                            <x-input-error :messages="$errors->get('app_subtitle')" class="mt-1" />
                        </div>
                    </div>
                </section>

                <section class="panel">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-50 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-slate-900 dark:text-white">Attendance Rules</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Late threshold and automatic checkout.</p>
                        </div>
                    </div>

                    <div class="grid sm:grid-cols-2 gap-4">
                        <div>
                            <x-input-label for="late_threshold" value="Late Threshold" />
                            <x-text-input wire:model="late_threshold" id="late_threshold" type="time"
                                class="mt-1 block w-full" />
                        </div>
                        <div>
                            <x-input-label for="checkout_time" value="Auto Checkout Time" />
                            <x-text-input wire:model="checkout_time" id="checkout_time" type="time"
                                class="mt-1 block w-full" />
                        </div>
                        <div class="sm:col-span-2 flex items-center gap-3 rounded-xl border border-surface-border dark:border-slate-700 px-4 py-3 bg-slate-50/80 dark:bg-slate-800/40">
                            <input wire:model="auto_checkout" id="auto_checkout" type="checkbox"
                                class="rounded border-surface-border text-green-700 focus:ring-brand-500">
                            <div>
                                <x-input-label for="auto_checkout" value="Enable automatic checkout" class="!mb-0" />
                                <p class="text-xs text-slate-500">Mark students out at the configured time each day.</p>
                            </div>
                        </div>
                    </div>
                </section>
            </div>

            {{-- Right column --}}
            <div class="lg:col-span-2 space-y-6">
                <section class="panel">
                    <div class="flex items-start justify-between gap-3 mb-5">
                        <div class="flex items-start gap-3">
                            <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-sky-50 text-sky-700 dark:bg-sky-500/15 dark:text-sky-300">
                                <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                        d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01" />
                                </svg>
                            </div>
                            <div>
                                <h3 class="font-semibold text-lg text-slate-900 dark:text-white">Layout Colors</h3>
                                <p class="text-sm text-slate-500 mt-0.5">Customize sidebar, header, and page background.</p>
                            </div>
                        </div>
                        <button type="button" wire:click="resetLayoutColors"
                            class="text-xs font-medium text-slate-500 hover:text-slate-700 dark:hover:text-slate-300 shrink-0">
                            Reset defaults
                        </button>
                    </div>

                    <div class="space-y-4 mb-6">
                        @foreach ([
                            'sidebar_color' => ['label' => 'Sidebar', 'hint' => 'Navigation panel background'],
                            'header_color' => ['label' => 'Header', 'hint' => 'Top bar background'],
                            'background_color' => ['label' => 'Background', 'hint' => 'Main content area'],
                        ] as $field => $meta)
                            <div>
                                <x-input-label :for="$field" :value="$meta['label']" />
                                <p class="text-xs text-slate-500 mb-2">{{ $meta['hint'] }}</p>
                                <div class="flex items-center gap-3">
                                    <input wire:model.live="{{ $field }}" id="{{ $field }}" type="color"
                                        class="h-11 w-14 cursor-pointer rounded-xl border border-surface-border dark:border-slate-700 bg-white p-1">
                                    <x-text-input wire:model.live="{{ $field }}" type="text" maxlength="7"
                                        class="block w-full font-mono text-sm uppercase" />
                                </div>
                                <x-input-error :messages="$errors->get($field)" class="mt-1" />
                            </div>
                        @endforeach
                    </div>

                    {{-- Live layout preview --}}
                    <div class="rounded-2xl border border-surface-border dark:border-slate-700 overflow-hidden shadow-inner">
                        <p class="px-3 py-2 text-[11px] font-semibold uppercase tracking-wide text-slate-500 bg-slate-50 dark:bg-slate-800/60 border-b border-surface-border dark:border-slate-700">
                            Live preview
                        </p>
                        <div class="flex h-44" wire:key="layout-preview-{{ $sidebar_color }}-{{ $header_color }}-{{ $background_color }}">
                            <div class="w-[30%] border-r border-surface-border/80 flex flex-col shrink-0"
                                style="background-color: {{ $sidebar_color }}">
                                <div class="px-2 py-3 border-b border-black/5 text-center space-y-1">
                                    <div class="mx-auto h-7 w-7 rounded-lg bg-green-700 text-[10px] font-bold text-white flex items-center justify-center">
                                        {{ strtoupper(substr($school_code ?: 'CS', 0, 2)) }}
                                    </div>
                                    <p class="text-[9px] font-bold uppercase text-slate-800 truncate px-1">
                                        {{ strtoupper($school_code ?: 'CS') }}
                                    </p>
                                </div>
                                <div class="p-2 space-y-1">
                                    <div class="h-2 rounded bg-green-100/90 w-full"></div>
                                    <div class="h-2 rounded bg-black/5 w-4/5"></div>
                                    <div class="h-2 rounded bg-black/5 w-3/5"></div>
                                </div>
                            </div>
                            <div class="flex-1 flex flex-col min-w-0" style="background-color: {{ $background_color }}">
                                <div class="h-8 border-b border-black/5 flex items-center px-2 gap-1 shrink-0"
                                    style="background-color: {{ $header_color }}">
                                    <div class="h-2 flex-1 rounded bg-black/5 max-w-[60%]"></div>
                                    <div class="h-4 w-4 rounded-full bg-green-700 ml-auto"></div>
                                </div>
                                <div class="p-3 space-y-2 flex-1">
                                    <div class="h-2.5 rounded bg-black/10 w-1/3"></div>
                                    <div class="rounded-lg bg-white/90 border border-black/5 p-2 space-y-1.5 shadow-sm">
                                        <div class="h-2 rounded bg-black/5 w-full"></div>
                                        <div class="h-2 rounded bg-black/5 w-2/3"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="text-xs text-slate-500 mt-3">Custom colors apply in light mode. Dark mode uses the built-in palette.</p>
                </section>

                <section class="panel">
                    <div class="flex items-start gap-3 mb-5">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-indigo-50 text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                    d="M20.354 15.354A9(8) 0 018.646 3.646 8.003 8.003 0 0012 21a8.003 8.003 0 007.354-5.646z" />
                            </svg>
                        </div>
                        <div>
                            <h3 class="font-semibold text-lg text-slate-900 dark:text-white">Appearance</h3>
                            <p class="text-sm text-slate-500 mt-0.5">Theme preference for all users on this device.</p>
                        </div>
                    </div>
                    <div>
                        <x-input-label for="theme" value="Theme" />
                        <select wire:model="theme" id="theme" class="mt-1 select-field w-full">
                            @foreach ($themes as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </section>

                <section class="panel">
                    <h3 class="font-semibold mb-3 text-slate-900 dark:text-white">More Configuration</h3>
                    <div class="space-y-3">
                        <a href="{{ route('settings.academic.structure') }}" wire:navigate
                            class="flex items-center justify-between gap-3 rounded-xl border border-surface-border dark:border-slate-700 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition group">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Academic Structure</p>
                                <p class="text-xs text-slate-500">Departments, sections, schedules</p>
                            </div>
                            <svg class="h-4 w-4 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                        <a href="{{ route('settings.attendance') }}" wire:navigate
                            class="flex items-center justify-between gap-3 rounded-xl border border-surface-border dark:border-slate-700 px-4 py-3 hover:bg-slate-50 dark:hover:bg-slate-800/50 transition group">
                            <div>
                                <p class="text-sm font-medium text-slate-900 dark:text-white">Attendance Config</p>
                                <p class="text-xs text-slate-500">Remarks, periods, bulk options</p>
                            </div>
                            <svg class="h-4 w-4 text-slate-400 group-hover:text-slate-600 dark:group-hover:text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                            </svg>
                        </a>
                    </div>
                </section>
            </div>
        </div>

        <div class="flex items-center justify-end gap-3 pt-2 border-t border-surface-border dark:border-slate-800 max-w-6xl">
            <x-primary-button type="submit">Save Settings</x-primary-button>
        </div>
    </form>
</div>
