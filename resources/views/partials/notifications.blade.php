@php
    $notif = notifications();
    // Inline (feather-style) SVG paths so the panel is self-contained and renders
    // identically in the admin (nobleui) and patron (opac) themes without an icon font.
    $nfyIcons = [
        'bell'           => '<path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/>',
        'message-square' => '<path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>',
        'check-circle'   => '<path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/>',
        'help-circle'    => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
        'star'           => '<polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/>',
        'clock'          => '<circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/>',
    ];
@endphp
<div class="nfy">
    <div class="nfy-head">
        <span class="nfy-head-title">Notifications</span>
        @if($notif['count'] > 0)
            <span class="nfy-head-count">{{ $notif['count'] > 99 ? '99+' : $notif['count'] }} new</span>
        @endif
    </div>
    <div class="nfy-list">
        @forelse($notif['items'] as $it)
            <a href="{{ $it['url'] }}" class="nfy-item">
                <span class="nfy-ic nfy-{{ $it['color'] }}">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">{!! $nfyIcons[$it['icon']] ?? $nfyIcons['bell'] !!}</svg>
                </span>
                <span class="nfy-body">
                    <span class="nfy-title">{{ $it['title'] }}</span>
                    <span class="nfy-text">{{ $it['text'] }}</span>
                </span>
            </a>
        @empty
            <div class="nfy-empty">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">{!! $nfyIcons['check-circle'] !!}</svg>
                <p>You're all caught up.</p>
            </div>
        @endforelse
    </div>
</div>

<style>
    .nfy { width: 330px; max-width: 88vw; font-family: inherit; color: #22283b; }
    .nfy-head { display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #eef0f4; }
    .nfy-head-title { font-weight: 700; font-size: 14px; color: #22283b; }
    .nfy-head-count { font-size: 11px; font-weight: 700; color: #fff; background: #ff3366; border-radius: 10px; padding: 2px 9px; }
    .nfy-list { max-height: 380px; overflow-y: auto; }
    .nfy-item { display: flex; gap: 12px; align-items: flex-start; padding: 11px 16px; text-decoration: none; border-bottom: 1px solid #f3f5f8; transition: background .12s ease; }
    .nfy-item:last-child { border-bottom: 0; }
    .nfy-item:hover { background: #f7f9fc; }
    .nfy-ic { flex: 0 0 auto; width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; }
    .nfy-ic svg { width: 19px; height: 19px; }
    .nfy-body { display: flex; flex-direction: column; min-width: 0; }
    .nfy-title { font-size: 13px; font-weight: 600; color: #22283b; line-height: 1.35; }
    .nfy-text { font-size: 12px; color: #8a94a6; line-height: 1.35; }
    .nfy-empty { text-align: center; color: #9aa4b8; padding: 30px 16px; }
    .nfy-empty svg { width: 34px; height: 34px; color: #c7cfdb; margin-bottom: 8px; }
    .nfy-empty p { margin: 0; font-size: 13px; }
    .nfy-primary { background: #eef0ff; color: #6571ff; }
    .nfy-success { background: #e6f7ee; color: #05a34a; }
    .nfy-info    { background: #e7f4ff; color: #1a86d0; }
    .nfy-warning { background: #fff5e0; color: #e0a106; }
    .nfy-danger  { background: #ffe9ee; color: #ff3366; }
    /* Count badge on the bell trigger (shared by both themes) */
    .nfy-badge { position: absolute; top: 0; right: 0; min-width: 17px; height: 17px; padding: 0 4px; border-radius: 9px; background: #ff3366; color: #fff; font-size: 10px; font-weight: 700; line-height: 17px; text-align: center; box-shadow: 0 0 0 2px #fff; }
</style>
