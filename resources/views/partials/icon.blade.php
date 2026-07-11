@props(['name' => 'dot', 'class' => 'nav-icon'])
@php
    // Inline, offline SVG icon set. Paths use currentColor via stroke.
    $icons = [
        'dashboard' => '<rect x="3" y="3" width="7" height="9" rx="1.5"/><rect x="14" y="3" width="7" height="5" rx="1.5"/><rect x="14" y="12" width="7" height="9" rx="1.5"/><rect x="3" y="16" width="7" height="5" rx="1.5"/>',
        'building' => '<path d="M4 21V5a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v16"/><path d="M16 8h3a2 2 0 0 1 2 2v11"/><path d="M3 21h18"/><path d="M8 7h2M8 11h2M8 15h2"/>',
        'users' => '<circle cx="9" cy="8" r="3"/><path d="M4 20a5 5 0 0 1 10 0"/><path d="M16 5.5a3 3 0 0 1 0 5"/><path d="M17 20a5 5 0 0 0-2-4"/>',
        'document' => '<path d="M14 3v4a1 1 0 0 0 1 1h4"/><path d="M5 3h9l6 6v11a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Z"/><path d="M8 13h8M8 17h6"/>',
        'clock' => '<circle cx="12" cy="12" r="8"/><path d="M12 8v4l3 2"/>',
        'fingerprint' => '<path d="M12 11a2 2 0 0 1 2 2c0 3-1 5-1 5"/><path d="M8 12a4 4 0 0 1 8 0c0 4-1 6-1 6"/><path d="M5 13a7 7 0 0 1 14 0c0 2-.3 4-.8 5.5"/><path d="M9 20c.7-1.5 1-3 1-5a2 2 0 0 1 2-2"/>',
        'calendar' => '<rect x="3" y="5" width="18" height="16" rx="2"/><path d="M3 9h18M8 3v4M16 3v4"/>',
        'wallet' => '<path d="M3 7a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2"/><rect x="3" y="7" width="18" height="13" rx="2"/><path d="M16 12h3"/>',
        'chart' => '<path d="M4 20V4"/><path d="M4 20h16"/><path d="M8 16v-4M12 16V8M16 16v-6"/>',
        'shield' => '<path d="M12 3l7 3v5c0 4.5-3 8-7 10-4-2-7-5.5-7-10V6l7-3Z"/><path d="M9.5 12l2 2 3.5-4"/>',
        'history' => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 4v4h4"/><path d="M12 8v4l3 2"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1.3l2-1.5-2-3.4-2.3 1a7 7 0 0 0-2.2-1.3L14 2h-4l-.4 2.2a7 7 0 0 0-2.2 1.3l-2.3-1-2 3.4 2 1.5A7 7 0 0 0 5 12c0 .4 0 .9.1 1.3l-2 1.5 2 3.4 2.3-1a7 7 0 0 0 2.2 1.3L10 22h4l.4-2.2a7 7 0 0 0 2.2-1.3l2.3 1 2-3.4-2-1.5c.1-.4.1-.9.1-1.3Z"/>',
        'menu' => '<path d="M4 6h16M4 12h16M4 18h16"/>',
        'bell' => '<path d="M18 8a6 6 0 1 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9"/><path d="M10 21h4"/>',
        'chevron' => '<path d="M9 6l6 6-6 6"/>',
        'dot' => '<circle cx="12" cy="12" r="3"/>',
    ];
    $paths = $icons[$name] ?? $icons['dot'];
@endphp
<svg class="{{ $class }}" viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $paths !!}</svg>
