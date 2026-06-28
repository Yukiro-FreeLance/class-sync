<div>
    <x-page-header title="Backups" subtitle="Create and restore system backups" />

    <div class="panel mb-6">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="font-semibold text-slate-900 dark:text-white">Create Backup</h3>
                <p class="text-sm text-slate-500 mt-1">Generate a new database backup</p>
            </div>
            <button wire:click="createBackup" wire:loading.attr="disabled" class="btn-primary disabled:opacity-50">
                <span wire:loading.remove wire:target="createBackup">Create Backup</span>
                <span wire:loading wire:target="createBackup">Creating...</span>
            </button>
        </div>
    </div>

    <div class="panel-flush">
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Filename</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Created</th>
                        <th class="text-right">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr>
                            <td class="font-mono text-xs">{{ $backup->filename }}</td>
                            <td class="capitalize">{{ $backup->type }}</td>
                            <td>
                                <span @class([
                                    'badge',
                                    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400' =>
                                        $backup->status === 'completed',
                                    'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' =>
                                        $backup->status === 'pending',
                                    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400' =>
                                        $backup->status === 'failed',
                                ])>{{ ucfirst($backup->status) }}</span>
                            </td>
                            <td class="text-slate-500">
                                {{ \Illuminate\Support\Carbon::parse($backup->created_at)->diffForHumans() }}</td>
                            <td class="text-right">
                                <button wire:click="restore({{ $backup->id }})"
                                    class="text-green-700 hover:text-brand-500 font-medium text-sm">Restore</button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 py-12">No backups yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
