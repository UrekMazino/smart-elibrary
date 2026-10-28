{{--
  "Where you're signed in" (App\Services\BrowserSessions), on the staff and the patron profile pages: this session
  first, then the account's other sessions, each with "Sign out", and "Sign out all other sessions". Both open the
  password dialog (partials.browser-sessions-modal). $sessions: BrowserSessions::of().
--}}
<style>
  .bsess-intro { font-size: .8125rem; color: var(--ui-muted, #6b7280); margin: 0 0 10px; }
  .bsess-list { list-style: none; margin: 0; padding: 0; border: 1px solid var(--ui-border, #eef0f2); border-radius: 8px; }
  .bsess-item { display: flex; align-items: center; gap: 12px; padding: 10px 14px; }
  .bsess-item + .bsess-item { border-top: 1px solid var(--ui-border, #eef0f2); }
  .bsess-icon { flex: 0 0 auto; width: 22px; height: 22px; color: var(--ui-muted, #7987a1); }
  .bsess-what { flex: 1 1 auto; min-width: 0; }
  .bsess-name { font-size: .875rem; font-weight: 600; color: var(--ui-text, #222); }
  .bsess-here { margin-left: 2px; padding: 1px 8px; border-radius: 10px; font-size: .6875rem; font-weight: 700;
                color: var(--ui-navy, #00796f); background: var(--ui-navy-soft, #e7f5f3); white-space: nowrap; }
  .bsess-when { font-size: .75rem; color: var(--ui-muted, #6b7280); overflow-wrap: anywhere; }
  .bsess-foot { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; margin-top: 12px; }
  .bsess-hint { flex: 1 1 260px; font-size: .71875rem; color: var(--ui-muted, #6b7280); }
</style>
<div class="bsess">
  <p class="bsess-intro">These browsers are signed in to your account. Sign out any you don’t recognize.</p>
  <ul class="bsess-list">
    @foreach ($sessions as $session)
      <li class="bsess-item">
        <svg class="bsess-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
          @if ($session->mobile)
            <rect x="5" y="2" width="14" height="20" rx="2"/><line x1="12" y1="18" x2="12.01" y2="18"/>
          @else
            <rect x="2" y="3" width="20" height="14" rx="2"/><line x1="8" y1="21" x2="16" y2="21"/><line x1="12" y1="17" x2="12" y2="21"/>
          @endif
        </svg>
        <div class="bsess-what">
          <div class="bsess-name">{{ $session->browser }}@if ($session->current) <span class="bsess-here">This device</span>@endif</div>
          <div class="bsess-when">
            {{ $session->current ? 'Active now' : 'Last active ' . $session->last_active->diffForHumans() }}@if ($session->ip) · {{ $session->ip }}@endif
          </div>
        </div>
        @unless ($session->current)
          <button type="button" class="btn btn-sm btn-outline-secondary" data-end-session="{{ $session->ref }}" data-session-name="{{ $session->browser }}">Sign out</button>
        @endunless
      </li>
    @endforeach
  </ul>
  <div class="bsess-foot">
    {{-- A device kept signed in by its cookie may not be listed: the button is always there. --}}
    <button type="button" class="btn btn-sm btn-outline-danger" data-end-session="others">Sign out all other sessions</button>
    <span class="bsess-hint">A device where you ticked “Keep me signed in” can sign back in without being listed here. “Sign out all other sessions” signs it out too.</span>
  </div>
</div>
