@props(['student'])

@php
    $isArchived = $student->trashed();
    $hasMenu = auth()->user()->can('update', $student)
        || auth()->user()->can('archive', $student)
        || auth()->user()->can('restore', $student)
        || auth()->user()->can('delete', $student);
@endphp

<div x-data="{ open: false }" class="relative flex items-center justify-end gap-1">
    <a href="{{ route('students.show', $student) }}" wire:navigate
        class="inline-flex items-center rounded-lg px-2.5 py-1.5 text-sm font-medium text-green-700 hover:bg-green-50 dark:text-green-400 dark:hover:bg-green-900/20 transition">
        View
    </a>

    @if ($hasMenu)
        <button type="button" @click="open = !open"
            class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition"
            :class="open ? 'bg-slate-100 dark:bg-slate-800' : ''"
            aria-label="More actions">
            <svg class="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z" />
            </svg>
        </button>

        <div x-show="open" @click.outside="open = false" x-transition x-cloak
            class="absolute right-0 top-full z-30 mt-1 w-52 rounded-xl border border-surface-border dark:border-slate-700 bg-white dark:bg-slate-900 shadow-lg py-1 overflow-hidden">
            @unless ($isArchived)
                @can('update', $student)
                    <a href="{{ route('students.edit', $student) }}" wire:navigate @click="open = false"
                        class="flex items-center gap-2.5 px-3 py-2 text-sm text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                        </svg>
                        Edit student
                    </a>
                @endcan
                @can('archive', $student)
                    <button type="button" wire:click="archive({{ $student->id }})"
                        wire:confirm="Archive {{ $student->list_name }}? They will be hidden from lists and attendance."
                        @click="open = false"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-amber-700 dark:text-amber-300 hover:bg-amber-50 dark:hover:bg-amber-900/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4" />
                        </svg>
                        Archive
                    </button>
                @endcan
            @else
                @can('restore', $student)
                    <button type="button" wire:click="restore({{ $student->id }})"
                        wire:confirm="Restore {{ $student->list_name }} to active records?"
                        @click="open = false"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                        </svg>
                        Restore
                    </button>
                @endcan
                @can('delete', $student)
                    <div class="my-1 border-t border-surface-border dark:border-slate-800"></div>
                    <button type="button" wire:click="forceDelete({{ $student->id }})"
                        wire:confirm="Permanently delete {{ $student->list_name }}? This cannot be undone."
                        @click="open = false"
                        class="flex w-full items-center gap-2.5 px-3 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">
                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75"
                                d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        Delete permanently
                    </button>
                @endcan
            @endunless
        </div>
    @endif
</div>
