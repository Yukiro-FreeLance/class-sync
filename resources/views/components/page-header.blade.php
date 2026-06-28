@props(['title', 'subtitle' => null])

<div {{ $attributes->merge(['class' => 'flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4 mb-6']) }}>
    <div>
        <h1 class="page-title">{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (isset($actions))
        <div class="flex flex-wrap items-center gap-2 shrink-0">
            {{ $actions }}
        </div>
    @endif
</div>
