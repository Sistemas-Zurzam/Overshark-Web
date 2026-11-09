{{-- Una entrada del menú lateral del panel. Variables del bucle: $routeName, $label, $icon, $navBadges. --}}
@php($active = \App\Support\AdminNav::isActive($routeName))
@php($badge = $navBadges[$routeName] ?? null)
<a href="{{ route($routeName) }}" @class(['admin-nav-link', 'is-active' => $active]) @if ($active) aria-current="page" @endif>
    <x-admin.nav-icon :name="$icon" />
    <span class="flex-1">{{ $label }}</span>
    @if ($badge)
        <span class="admin-nav-badge admin-nav-badge--{{ $badge['tone'] }}" title="{{ $badge['count'] }} {{ $badge['title'] }}"><span class="sr-only">{{ $badge['count'] }} {{ $badge['title'] }}</span><span aria-hidden="true">{{ $badge['count'] > 99 ? '99+' : $badge['count'] }}</span></span>
    @endif
</a>
