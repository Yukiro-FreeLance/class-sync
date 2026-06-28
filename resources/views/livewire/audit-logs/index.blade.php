<div>
    <x-page-header title="Audit Logs" subtitle="System activity and change history" />

    <div class="panel mb-6">
        <div class="grid sm:grid-cols-2 gap-4">
            <input wire:model.live.debounce.300ms="search" type="search" placeholder="Search logs..." class="input-field">
            <select wire:model.live="action" class="select-field">
                <option value="">All Actions</option>
                @foreach ($actions as $value => $label)
                    <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="panel-flush">
        <div class="overflow-x-auto">
            <table class="w-full data-table">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>User</th>
                        <th>Action</th>
                        <th>Model</th>
                        <th>IP</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr>
                            <td class="text-slate-500 whitespace-nowrap">
                                {{ $log->created_at?->format('M d, H:i') }}</td>
                            <td>{{ $log->user?->name ?? 'System' }}</td>
                            <td>
                                <span
                                    class="badge bg-brand-100 text-green-700 dark:bg-brand-900/30 dark:text-brand-400 capitalize">{{ $log->action }}</span>
                            </td>
                            <td class="text-slate-500 text-xs">{{ class_basename($log->model_type ?? '') }}
                                #{{ $log->model_id }}</td>
                            <td class="text-slate-500 font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center text-slate-500 py-12">No audit logs found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($logs->hasPages())
            <div class="px-5 py-4 border-t border-surface-border dark:border-slate-800">
                {{ $logs->links() }}
            </div>
        @endif
    </div>
</div>
