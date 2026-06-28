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
                    class="btn-secondary w-full disabled:opacity-50">
                    <span wire:loading.remove wire:target="buildDesktopInstaller">Build Windows Installer (.exe)</span>
                    <span wire:loading wire:target="buildDesktopInstaller">Building installer...</span>
                </button>
            </div>

            <p class="text-xs text-slate-500 mt-4">
                The ZIP archive is ready for LAN/server deployment. The Windows installer bundles Electron and requires Node.js on this machine.
            </p>
        </section>
    </div>

    @if ($lastOutput)
        <div class="panel mb-6">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Build Log</h3>
            <pre class="overflow-x-auto rounded-xl bg-slate-950 text-slate-100 text-xs p-4 whitespace-pre-wrap">{{ $lastOutput }}</pre>
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
                                <button wire:click="deletePackage('{{ $package['filename'] }}')"
                                    wire:confirm="Delete this package?"
                                    class="text-red-600 hover:text-red-500 font-medium text-sm">Delete</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 py-12">No application packages yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
