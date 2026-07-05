<div>
    @if ($show)
        <div class="fixed inset-0 z-[80] flex items-center justify-center p-4" x-data x-trap.noscroll.inert="true">
            <div class="absolute inset-0 bg-slate-900/50 backdrop-blur-sm" wire:click="close"></div>

            <div class="relative w-full max-w-2xl panel shadow-2xl max-h-[90vh] overflow-y-auto">
                <div class="flex items-start justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-slate-900 dark:text-white">Import Students</h2>
                        <p class="text-sm text-slate-500 mt-1">Bulk enroll students from Excel or CSV</p>
                    </div>
                    <button type="button" wire:click="close" class="btn-ghost p-2">&times;</button>
                </div>

                {{-- Steps indicator --}}
                <div class="flex items-center gap-2 mb-8">
                    @foreach ([1 => 'Template', 2 => 'Upload', 3 => 'Results'] as $number => $label)
                        <div class="flex items-center gap-2 flex-1">
                            <div @class([
                                'flex h-8 w-8 items-center justify-center rounded-full text-xs font-bold shrink-0',
                                'bg-green-700 text-white' => $step >= $number,
                                'bg-slate-100 text-slate-400 dark:bg-slate-800' => $step < $number,
                            ])>{{ $number }}</div>
                            <span @class([
                                'text-xs font-medium hidden sm:block',
                                'text-green-700' => $step === $number,
                                'text-slate-500' => $step !== $number,
                            ])>{{ $label }}</span>
                            @if ($number < 3)
                                <div @class([
                                    'h-0.5 flex-1 rounded-full',
                                    'bg-brand-300' => $step > $number,
                                    'bg-slate-200 dark:bg-slate-700' => $step <= $number,
                                ])></div>
                            @endif
                        </div>
                    @endforeach
                </div>

                @if ($step === 1)
                    <div class="space-y-5">
                        <div
                            class="rounded-xl border border-brand-100 bg-brand-50/50 dark:bg-brand-900/10 dark:border-brand-800 p-5">
                            <h3 class="font-semibold text-slate-900 dark:text-white mb-2">Before you import</h3>
                            <ul class="text-sm text-slate-600 dark:text-slate-400 space-y-2 list-disc pl-5">
                                <li>Download the template and use the <strong>Students</strong> sheet for your data.
                                </li>
                                <li>Check the <strong>Reference</strong> sheet for valid grade levels, sections, and
                                    years.</li>
                                <li>Required fields: first name, last name, grade level, academic year.</li>
                                <li>Leave <code
                                        class="text-xs bg-white dark:bg-slate-800 px-1 rounded">student_number</code>
                                    blank to auto-generate IDs.</li>
                                <li>Existing lrn no tags are <strong>skipped</strong> automatically.</li>
                                <li>Enable <strong>Update existing</strong> on upload to overwrite matched records.</li>
                            </ul>
                        </div>

                        <a href="{{ route('students.import.template') }}" class="btn-primary w-full justify-center">
                            <svg class="h-5 w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                            </svg>
                            Download Import Template (.xlsx)
                        </a>

                        <button type="button" wire:click="goToUpload" class="btn-secondary w-full justify-center">
                            I have my file ready — Continue
                        </button>
                    </div>
                @endif

                @if ($step === 2)
                    <form wire:submit="import" class="space-y-5">
                        <div>
                            <label class="block text-sm font-medium text-slate-700 dark:text-slate-300 mb-2">
                                Upload filled template
                            </label>
                            <div
                                class="relative rounded-xl border-2 border-dashed border-surface-border dark:border-slate-700 p-8 text-center hover:border-brand-400 transition">
                                <input type="file" wire:model="importFile" accept=".xlsx,.xls,.csv"
                                    class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                <svg class="mx-auto h-10 w-10 text-slate-400 mb-3" fill="none" stroke="currentColor"
                                    viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5"
                                        d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5m-13.5-9L12 3m0 0 4.5 4.5M12 3v13.5" />
                                </svg>
                                @if ($importFile)
                                    <p class="text-sm font-medium text-green-700">
                                        {{ $importFile->getClientOriginalName() }}</p>
                                    <p class="text-xs text-slate-500 mt-1">Click or drop to replace</p>
                                @else
                                    <p class="text-sm font-medium text-slate-700 dark:text-slate-300">Drop your file
                                        here
                                        or click to browse</p>
                                    <p class="text-xs text-slate-500 mt-1">.xlsx, .xls, or .csv — max 10 MB</p>
                                @endif
                            </div>
                            @error('importFile')
                                <p class="mt-2 text-sm text-red-600">{{ $message }}</p>
                            @enderror
                            <div wire:loading wire:target="importFile" class="mt-2 text-sm text-slate-500">Uploading
                                file…
                            </div>
                        </div>

                        <label class="flex items-center gap-2.5 cursor-pointer">
                            <input type="checkbox" wire:model="updateExisting"
                                class="rounded border-surface-border text-green-700 focus:ring-brand-500">
                            <span class="text-sm text-slate-600 dark:text-slate-400">Update existing students when
                                lrn no or RFID matches</span>
                        </label>

                        <div class="flex gap-3 pt-2">
                            <button type="button" wire:click="$set('step', 1)"
                                class="btn-secondary flex-1">Back</button>
                            <button type="submit" wire:loading.attr="disabled" wire:target="import,importFile"
                                class="btn-primary flex-1 disabled:opacity-60">
                                <span wire:loading.remove wire:target="import">Import Students</span>
                                <span wire:loading wire:target="import">Importing…</span>
                            </button>
                        </div>
                    </form>
                @endif

                @if ($step === 3)
                    <div class="space-y-5">
                        <div class="grid grid-cols-3 gap-3">
                            <div class="rounded-xl bg-emerald-50 dark:bg-emerald-900/20 p-4 text-center">
                                <p class="text-2xl font-bold text-emerald-600">{{ $importedCount }}</p>
                                <p class="text-xs text-slate-500 mt-1">Imported</p>
                            </div>
                            <div class="rounded-xl bg-amber-50 dark:bg-amber-900/20 p-4 text-center">
                                <p class="text-2xl font-bold text-amber-600">{{ $skippedCount }}</p>
                                <p class="text-xs text-slate-500 mt-1">Skipped</p>
                            </div>
                            <div class="rounded-xl bg-slate-50 dark:bg-slate-800 p-4 text-center">
                                <p class="text-2xl font-bold text-slate-700 dark:text-slate-200">
                                    {{ $importedCount + $skippedCount }}</p>
                                <p class="text-xs text-slate-500 mt-1">Total rows</p>
                            </div>
                        </div>

                        @if ($skippedRows !== [])
                            <div
                                class="rounded-xl border border-amber-200 bg-amber-50 dark:bg-amber-900/10 dark:border-amber-800 p-4 max-h-48 overflow-y-auto">
                                <p class="text-sm font-semibold text-amber-800 dark:text-amber-300 mb-2">Skipped (already registered)</p>
                                <ul class="text-xs text-amber-700 dark:text-amber-200 space-y-1.5">
                                    @foreach ($skippedRows as $skipped)
                                        <li><strong>Row {{ $skipped['row'] }}:</strong> {{ $skipped['message'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if ($importErrors !== [])
                            <div
                                class="rounded-xl border border-red-200 bg-red-50 dark:bg-red-900/10 dark:border-red-800 p-4 max-h-48 overflow-y-auto">
                                <p class="text-sm font-semibold text-red-700 dark:text-red-400 mb-2">Row errors</p>
                                <ul class="text-xs text-red-600 dark:text-red-300 space-y-1.5">
                                    @foreach ($importErrors as $error)
                                        <li><strong>Row {{ $error['row'] }}:</strong> {{ $error['message'] }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @elseif ($importedCount > 0 && $skippedRows === [])
                            <div
                                class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">
                                All rows imported successfully. QR codes have been generated for new students.
                            </div>
                        @elseif ($importedCount > 0)
                            <div
                                class="rounded-xl border border-emerald-200 bg-emerald-50 dark:bg-emerald-900/10 p-4 text-sm text-emerald-700 dark:text-emerald-300">
                                Import completed. New students have been added; existing records were skipped.
                            </div>
                        @endif

                        <div class="flex gap-3">
                            <button type="button" wire:click="resetImport" class="btn-secondary flex-1">Import
                                another file</button>
                            <button type="button" wire:click="close" class="btn-primary flex-1">Done</button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    @endif
</div>
