@php
    use App\Support\Navigation;
@endphp

<nav class="nav-list" aria-label="{{ __('app.app_name') }}">
    @foreach (Navigation::items() as $item)
        @php
            $title = __('app.'.$item['title']);
            $icon = $item['icon'] ?? 'dot';
        @endphp

        @if (! empty($item['children']))
            @php $open = Navigation::isGroupActive($item); @endphp
            <div class="nav-group {{ $open ? 'open' : '' }}" data-nav-group>
                <button type="button"
                        class="nav-item nav-parent {{ $open ? 'active' : '' }}"
                        data-nav-toggle
                        aria-expanded="{{ $open ? 'true' : 'false' }}">
                    <span class="nav-icon-wrap">@include('partials.icon', ['name' => $icon])</span>
                    <span class="nav-label">{{ $title }}</span>
                    <span class="nav-caret">@include('partials.icon', ['name' => 'chevron', 'class' => 'caret-svg'])</span>
                </button>

                <div class="nav-submenu" role="menu">
                    @foreach ($item['children'] as $child)
                        @continue(! Navigation::isVisible($child))
                        @php
                            $childHref = Navigation::href($child);
                            $childTitle = $child['label'] ?? __('app.'.$child['title']);
                        @endphp
                        @if ($childHref)
                            <a class="nav-subitem {{ Navigation::isActive($child) ? 'active' : '' }}" href="{{ $childHref }}" role="menuitem">
                                <span class="nav-bullet" aria-hidden="true"></span>
                                <span>{{ $childTitle }}</span>
                            </a>
                        @else
                            <span class="nav-subitem disabled" title="{{ __('app.not_built_yet') }}" role="menuitem">
                                <span class="nav-bullet" aria-hidden="true"></span>
                                <span>{{ $childTitle }}</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            </div>
        @elseif (Navigation::href($item))
            <a class="nav-item {{ Navigation::isActive($item) ? 'active' : '' }}" href="{{ Navigation::href($item) }}">
                <span class="nav-icon-wrap">@include('partials.icon', ['name' => $icon])</span>
                <span class="nav-label">{{ $title }}</span>
            </a>
        @else
            <span class="nav-item disabled" title="{{ __('app.not_built_yet') }}">
                <span class="nav-icon-wrap">@include('partials.icon', ['name' => $icon])</span>
                <span class="nav-label">{{ $title }}</span>
            </span>
        @endif
    @endforeach
</nav>
