@props(['items', 'navigation'])

@foreach ($items as $item)
    @if (!empty($item['disabled']))
        <span
            class="nav-link nav-link-inactive opacity-60 cursor-not-allowed"
            :class="sidebarNarrow ? 'justify-center px-2' : ''"
            :title="sidebarNarrow ? @js($item['label']) : null">
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}" />
            </svg>
            <span x-show="!sidebarNarrow" x-cloak>{{ $item['label'] }}</span>
            <span x-show="!sidebarNarrow" x-cloak class="ml-auto badge-soon">Soon</span>
        </span>
    @else
        @php $isActive = $navigation->isActive($item); @endphp
        <a href="{{ route($item['route']) }}" wire:navigate
            :class="sidebarNarrow ? 'justify-center px-2' : ''"
            :title="sidebarNarrow ? @js($item['label']) : null"
            @class([
                'nav-link',
                'nav-link-active' => $isActive,
                'nav-link-inactive' => ! $isActive,
            ])>
            <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.75" d="{{ $item['icon'] }}" />
            </svg>
            <span x-show="!sidebarNarrow" x-cloak>{{ $item['label'] }}</span>
        </a>
    @endif
@endforeach
