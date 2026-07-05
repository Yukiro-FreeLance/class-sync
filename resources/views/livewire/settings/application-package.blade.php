<div>
    <x-page-header title="Application Package" subtitle="Export Class Sync for LAN servers or Windows desktop installation">
        <x-slot name="actions">
            <span class="badge bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-300">
                v{{ $appVersion }}
            </span>
        </x-slot>
    </x-page-header>

    <div class="grid gap-6 xl:grid-cols-3 mb-6">
        <section class="panel xl:col-span-2">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">System Checks</h3>
            <p class="text-sm text-slate-500 mb-4">Verify the server can build and package the application.</p>

            <div class="space-y-3">
                @foreach ($checks as $check)
                    <div class="flex items-start justify-between gap-4 rounded-xl border border-surface-border dark:border-slate-800 px-4 py-3">
                        <div>
                            <p class="font-medium text-slate-900 dark:text-white">{{ $check['label'] }}</p>
                            <p class="text-sm text-slate-500 mt-0.5">{{ $check['detail'] }}</p>
                        </div>
                        <span @class([
                            'badge shrink-0',
                            'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' => $check['ok'],
                            'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' => ! $check['ok'],
                        ])>{{ $check['ok'] ? 'Ready' : 'Missing' }}</span>
                    </div>
                @endforeach
            </div>
        </section>

        <section class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Build Actions</h3>
            <p class="text-sm text-slate-500 mb-4">Super Admin only. Large packages may take several minutes.</p>

            <div class="space-y-3">
                <button wire:click="buildAssets" wire:loading.attr="disabled" wire:target="buildAssets"
                    class="btn-secondary w-full disabled:opacity-50">
                    <span wire:loading.remove wire:target="buildAssets">Build Production Assets</span>
                    <span wire:loading wire:target="buildAssets">Building assets...</span>
                </button>

                <button wire:click="createPackage" wire:loading.attr="disabled" wire:target="createPackage"
                    @disabled(! $canPackage)
                    class="btn-primary w-full disabled:opacity-50">
                    <span wire:loading.remove wire:target="createPackage">Create Deployment Package (.zip)</span>
                    <span wire:loading wire:target="createPackage">Packaging application...</span>
                </button>

                <button wire:click="buildDesktopInstaller" wire:loading.attr="disabled" wire:target="buildDesktopInstaller"
                    @disabled(! $canBuildDesktop)
                    class="btn-secondary w-full disabled:opacity-50">
                    <span wire:loading.remove wire:target="buildDesktopInstaller">Build Windows Installer (.exe)</span>
                    <span wire:loading wire:target="buildDesktopInstaller">Building installer...</span>
                </button>
            </div>

            <p class="text-xs text-slate-500 mt-4">
                The ZIP archive is ready for LAN/server deployment. The Windows installer bundles Electron and requires Node.js, the <code class="text-xs">electron/</code> folder, and a writable temp directory on this machine.
            </p>
        </section>
    </div>

    <section class="panel mb-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white mb-1">Desktop App Icon</h3>
                <p class="text-sm text-slate-500">
                    Upload a PNG or ICO file (256x256 or larger). Used for the Windows installer, desktop shortcut, and taskbar icon.
                </p>
            </div>

            <div class="flex flex-wrap items-start gap-4">
                <div class="flex h-20 w-20 items-center justify-center rounded-2xl border border-surface-border dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 overflow-hidden">
                    @if ($desktopIcon)
                        <img src="{{ $desktopIcon->temporaryUrl() }}" alt="Icon preview" class="h-full w-full object-contain">
                    @elseif ($desktopIconInfo)
                        <img src="{{ $desktopIconInfo['preview_url'] }}?v={{ md5($desktopIconInfo['updated_at']) }}"
                            alt="Current desktop icon" class="h-full w-full object-contain">
                    @else
                        <svg class="h-10 w-10 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
                        </svg>
                    @endif
                </div>

                <div class="space-y-3 min-w-[240px]">
                    <input wire:model="desktopIcon" id="desktopIcon" type="file" accept=".png,.jpg,.jpeg,.ico,image/png,image/jpeg,image/x-icon"
                        class="block w-full text-sm text-slate-500 file:mr-3 file:rounded-lg file:border-0 file:bg-green-700 file:px-3 file:py-2 file:text-sm file:font-medium file:text-white hover:file:bg-green-800">

                    <div class="flex flex-wrap gap-2">
                        <button type="button" wire:click="uploadDesktopIcon" wire:loading.attr="disabled"
                            wire:target="desktopIcon,uploadDesktopIcon"
                            @disabled(! $desktopIcon)
                            class="btn-primary disabled:opacity-50">
                            <span wire:loading.remove wire:target="uploadDesktopIcon">Save Icon</span>
                            <span wire:loading wire:target="uploadDesktopIcon">Saving...</span>
                        </button>

                        <button type="button" wire:click="useSchoolLogoAsDesktopIcon" wire:loading.attr="disabled"
                            wire:target="useSchoolLogoAsDesktopIcon"
                            class="btn-secondary">
                            Use School Logo
                        </button>

                        @if ($desktopIconInfo)
                            <button type="button" wire:click="removeDesktopIcon" wire:confirm="Remove the desktop app icon?"
                                class="btn-secondary text-red-600 hover:text-red-700 dark:text-red-400">
                                Remove
                            </button>
                        @endif
                    </div>

                    @if ($desktopIconInfo)
                        <p class="text-xs text-slate-500 font-mono">{{ $desktopIconInfo['filename'] }}</p>
                    @endif

                    <x-input-error :messages="$errors->get('desktopIcon')" class="mt-1" />
                    <div wire:loading wire:target="desktopIcon" class="text-sm text-slate-500">Uploading...</div>
                </div>
            </div>
        </div>
    </section>

    @if ($lastOutput)
        <div class="panel mb-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Build Log</h3>
            <pre class="overflow-x-auto rounded-xl bg-slate-950 text-slate-100 text-xs p-4 whitespace-pre-wrap">{{ $lastOutput }}</pre>
        </div>
    @endif

    @if ($latestDesktopInstaller)
        <div class="panel mb-6 border border-brand-200 dark:border-brand-900/40 bg-brand-50/60 dark:bg-brand-950/20">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <h3 class="font-semibold text-slate-900 dark:text-white">Download Class Sync Desktop App</h3>
                    <p class="text-sm text-slate-600 dark:text-slate-400 mt-1">
                        Latest Windows installer:
                        <span class="font-mono text-xs">{{ $latestDesktopInstaller['filename'] }}</span>
                        ·
                        {{ number_format($latestDesktopInstaller['size'] / 1048576, 1) }} MB
                    </p>
                </div>
                <div class="flex flex-wrap gap-3">
                    <a href="{{ route('settings.application-package.download', $latestDesktopInstaller['filename']) }}"
                        class="btn-primary">
                        Download Installer
                    </a>
                    @if ($latestDesktopInstaller['source'] === 'electron')
                        <button wire:click="importDesktopInstaller(@js($latestDesktopInstaller['filename']))"
                            class="btn-secondary">
                            Save to Download Storage
                        </button>
                    @endif
                </div>
            </div>
        </div>
    @endif

    <div class="panel-flush">
        <div class="px-6 py-4 border-b border-surface-border dark:border-slate-800">
            <h3 class="font-semibold text-slate-900 dark:text-white">Generated Packages</h3>
            <p class="text-sm text-slate-500 mt-1">Download or remove previously created application exports.</p>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Location</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($packages as $package)
                        <tr>
                            <td class="font-mono text-xs">{{ $package['filename'] }}</td>
                            <td class="capitalize">{{ $package['type'] }}</td>
                            <td class="text-slate-500 capitalize">
                                {{ $package['source'] === 'storage' ? 'Download storage' : 'Build output' }}
                            </td>
                            <td class="text-slate-500">
                                @if ($package['size'] < 1024)
                                    {{ $package['size'] }} B
                                @elseif ($package['size'] < 1048576)
                                    {{ number_format($package['size'] / 1024, 1) }} KB
                                @else
                                    {{ number_format($package['size'] / 1048576, 1) }} MB
                                @endif
                            </td>
                            <td class="text-slate-500">
                                {{ \Illuminate\Support\Carbon::parse($package['created_at'])->diffForHumans() }}
                            </td>
                            <td class="text-right space-x-3">
                                <a href="{{ route('settings.application-package.download', $package['filename']) }}"
                                    class="text-green-700 hover:text-brand-500 font-medium text-sm">Download</a>
                                @if (($package['source'] ?? 'storage') === 'electron')
                                    <button wire:click="importDesktopInstaller(@js($package['filename']))"
                                        class="text-brand-700 hover:text-brand-500 font-medium text-sm">
                                        Save
                                    </button>
                                @else
                                    <button wire:click="deletePackage(@js($package['filename']))"
                                        wire:confirm="Delete this package?"
                                        class="text-red-600 hover:text-red-500 font-medium text-sm">Delete</button>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-slate-500 py-12">
                                No application packages yet. Build a Windows installer or deployment package above.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
