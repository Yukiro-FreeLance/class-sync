<div x-data="{
    buffer: '',
    timer: null,
    handleKey(e) {
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA') return;
        if (e.key === 'Enter') {
            e.preventDefault();
            $wire.set('scanInput', this.buffer);
            $wire.processScan();
            this.buffer = '';
            return;
        }
        if (e.key.length === 1) {
            this.buffer += e.key;
            clearTimeout(this.timer);
            this.timer = setTimeout(() => { this.buffer = ''; }, 200);
        }
    }
}" @keydown.window="handleKey($event)">
    <x-page-header title="Attendance Scanner" subtitle="Scan QR codes or swipe RFID cards" />

    <div class="grid lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 panel">
            <div class="flex gap-2 mb-6">
                <button wire:click="$set('mode', 'qr')" @class([
                    'px-4 py-2 rounded-xl text-sm font-medium transition',
                    'bg-green-700 text-white shadow-sm' => $mode === 'qr',
                    'btn-secondary' => $mode !== 'qr',
                ])>QR Scanner</button>
                <button wire:click="$set('mode', 'rfid')" @class([
                    'px-4 py-2 rounded-xl text-sm font-medium transition',
                    'bg-green-700 text-white shadow-sm' => $mode === 'rfid',
                    'btn-secondary' => $mode !== 'rfid',
                ])>RFID Reader</button>
            </div>

            @if ($mode === 'qr')
                <div
                    class="text-center py-12 border-2 border-dashed border-surface-border dark:border-slate-700 rounded-2xl">
                    <svg class="mx-auto h-16 w-16 text-slate-400 mb-4" fill="none" stroke="currentColor"
                        viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                            d="M12 4v1m6 11h2m-6 0h-2v4m0-11v3m0 0h.01M12 12h4.01M16 20h4M4 12h4m12 0h.01M5 8h2a1 1 0 001-1V5a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1zm12 0h2a1 1 0 001-1V5a1 1 0 00-1-1h-2a1 1 0 00-1 1v2a1 1 0 001 1zM5 20h2a1 1 0 001-1v-2a1 1 0 00-1-1H5a1 1 0 00-1 1v2a1 1 0 001 1z" />
                    </svg>
                    <p class="text-slate-500 mb-4">Camera scanner placeholder — use manual input below</p>
                </div>
            @else
                <div
                    class="text-center py-12 border-2 border-dashed border-brand-200 dark:border-brand-800 rounded-2xl bg-brand-50/50 dark:bg-brand-900/10">
                    <div
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-brand-100 dark:bg-brand-900/30 text-green-700 dark:text-brand-300 text-sm font-medium mb-4">
                        <span class="h-2 w-2 rounded-full bg-emerald-500 animate-pulse"></span>
                        RFID Reader Active
                    </div>
                    <p class="text-slate-500">Swipe RFID card — reader sends keyboard input automatically</p>
                    <p class="text-xs text-slate-400 mt-2" x-show="buffer.length > 0">Reading: <span x-text="buffer"
                            class="font-mono"></span></p>
                </div>
            @endif

            <form wire:submit="processScan" class="mt-6">
                <x-input-label value="Manual Code Entry" />
                <div class="flex gap-2 mt-1">
                    <x-text-input wire:model="scanInput" class="flex-1" placeholder="Enter student ID or QR code..."
                        autofocus />
                    <x-primary-button type="submit">Scan</x-primary-button>
                </div>
            </form>

            <div class="mt-4">
                <x-input-label value="Gate" />
                <x-text-input wire:model="gate" class="mt-1 block w-full max-w-xs" />
            </div>
        </div>

        <div class="panel">
            <h3 class="font-semibold text-slate-900 dark:text-white mb-4">Recent Scans</h3>
            <div class="space-y-3">
                @forelse ($recentScans as $scan)
                    <div
                        class="flex items-center justify-between py-2.5 border-b border-surface-border dark:border-slate-800 last:border-0">
                        <div>
                            <p class="text-sm font-medium">{{ $scan['name'] }}</p>
                            <p class="text-xs text-slate-500 font-mono">{{ $scan['id'] }}</p>
                        </div>
                        <div class="text-right">
                            <span @class([
                                'text-xs font-medium uppercase',
                                'text-emerald-600' => $scan['type'] === 'in',
                                'text-amber-600' => $scan['type'] === 'out',
                            ])>{{ $scan['type'] }}</span>
                            <p class="text-xs text-slate-500">{{ $scan['time'] }}</p>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-slate-500">No scans yet. Scan a code to begin.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
