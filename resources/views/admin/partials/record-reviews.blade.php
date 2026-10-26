{{-- Reviews tab of the admin record view (admin.catalog-view-record). $reviews: ratings for this record
     joined with the reviewer (newest first, from CatalogingController::renderRecordView).
     A summary (average + star breakdown, whose rows double as star filters), a "with comments" filter,
     sorting and "Show more" paging all run client-side over the rendered list — a record has few
     reviews. Without JS every review simply shows, newest first. Patron emails only show to staff
     who can open patron accounts. --}}
@php
  $rvLabels   = [1 => 'Poor', 2 => 'Fair', 3 => 'Good', 4 => 'Very good', 5 => 'Excellent'];
  $rvTotal    = $reviews->count();
  $rvAvg      = $rvTotal ? round($reviews->avg('rating'), 1) : 0;
  $rvDist     = collect([5, 4, 3, 2, 1])->mapWithKeys(fn ($s) => [$s => $reviews->where('rating', $s)->count()]);
  $rvWithText = $reviews->filter(fn ($r) => trim((string) $r->remarks) !== '')->count();
  $rvEmails   = auth()->user()->can('access.accounts');
@endphp
<style>
  .rv { --rv-ink: var(--ui-text, #1f2433); --rv-ink-2: var(--ui-text-2, #5b6478); --rv-muted: var(--ui-muted, #8a94a6); --rv-line: var(--ui-border, #eceff4); --rv-accent: var(--t-primary); --rv-accent-ink: color-mix(in srgb, var(--t-primary), var(--ui-text, #000) 18%); --rv-star: #f5b301; --rv-star-off: var(--ui-border, #d5dae4); color: var(--rv-ink); }
  .rv-stars { position: relative; display: inline-block; font-size: 0.875rem; line-height: 1; letter-spacing: 1px; color: var(--rv-star-off); white-space: nowrap; vertical-align: -1px; }
  .rv-stars::before { content: '\2605\2605\2605\2605\2605'; }
  .rv-stars > span { position: absolute; top: 0; left: 0; bottom: 0; overflow: hidden; color: var(--rv-star); }
  .rv-stars > span::before { content: '\2605\2605\2605\2605\2605'; }
  .rv-stars-lg { font-size: 1.25rem; letter-spacing: 2px; }

  .rv-summary { display: grid; grid-template-columns: minmax(170px, 220px) minmax(0, 420px); gap: 28px; align-items: center; padding: 6px 4px 18px; border-bottom: 1px solid var(--rv-line); }
  .rv-avg { text-align: center; }
  .rv-avg-num { font-size: 2.75rem; font-weight: 700; line-height: 1; color: var(--rv-ink); }
  .rv-avg-of { font-size: 0.9375rem; font-weight: 500; color: var(--rv-muted); }
  .rv-avg-sub { margin-top: 8px; font-size: 0.78125rem; color: var(--rv-muted); }
  .rv-dist { display: flex; flex-direction: column; gap: 2px; }
  .rv-dist-row { display: grid; grid-template-columns: 34px minmax(0, 1fr) 44px; align-items: center; gap: 10px; width: 100%; padding: 4px 6px; border: 0; border-radius: 8px;
    background: none; font-size: 0.78125rem; color: var(--rv-ink-2); text-align: left; cursor: pointer; }
  .rv-dist-row:hover:not(:disabled) { background: var(--ui-surface-2, #f4f5fa); }
  .rv-dist-row:disabled { cursor: default; opacity: .55; }
  .rv-dist-row[aria-pressed="true"] { background: color-mix(in srgb, var(--t-primary) 12%, var(--ui-surface, #fff)); color: var(--rv-accent-ink); font-weight: 600; }
  .rv-dist-row:focus-visible, .rv-link:focus-visible, .rv-more:focus-visible { outline: 2px solid var(--rv-accent); outline-offset: 2px; }
  .rv-dist-label { white-space: nowrap; }
  .rv-dist-label::after { content: ' \2605'; color: var(--rv-star); }
  .rv-dist-track { height: 8px; border-radius: 8px; background: var(--ui-surface-2, #eef0f6); overflow: hidden; }
  .rv-dist-track > span { display: block; height: 100%; border-radius: 8px; background: var(--rv-star); }
  .rv-dist-n { text-align: right; font-variant-numeric: tabular-nums; }
  .rv-dist-hint { margin: 6px 6px 0; font-size: 0.71875rem; color: var(--rv-muted); }

  .rv-toolbar { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px 16px; padding: 14px 4px 6px; }
  .rv-status { margin: 0; font-size: 0.8125rem; color: var(--rv-ink-2); }
  .rv-status strong { color: var(--rv-ink); }
  .rv-controls { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 18px; }
  .rv-controls .form-check { margin: 0; font-size: 0.8125rem; }
  .rv-sort { display: inline-flex; align-items: center; gap: 8px; margin: 0; font-size: 0.8125rem; color: var(--rv-ink-2); }
  .rv-sort .form-select { width: auto; min-width: 150px; }
  .rv-link { padding: 0; border: 0; background: none; color: var(--rv-accent-ink); font-weight: 500; text-decoration: underline; cursor: pointer; }

  .rv-list { margin: 0; padding: 0; list-style: none; }
  .rv-item { display: flex; gap: 14px; padding: 16px 4px; border-top: 1px solid var(--rv-line); }
  .rv-item:first-child { border-top: 0; }
  .rv-avatar { flex: 0 0 auto; display: flex; align-items: center; justify-content: center; width: 38px; height: 38px; border-radius: 50%;
    background: color-mix(in srgb, var(--t-primary) 12%, var(--ui-surface, #fff)); color: var(--rv-accent-ink); font-size: 0.875rem; font-weight: 700; }
  .rv-body { flex: 1 1 auto; min-width: 0; }
  .rv-head { display: flex; flex-wrap: wrap; align-items: baseline; gap: 2px 8px; }
  .rv-name { font-size: 0.84375rem; font-weight: 600; color: var(--rv-ink); }
  .rv-email { font-size: 0.75rem; color: var(--rv-muted); }
  .rv-when { margin-left: auto; font-size: 0.75rem; color: var(--rv-muted); white-space: nowrap; }
  .card-body .rv-badge { padding: 1px 7px; border-radius: 10px; background: var(--ui-danger-soft, #ffe9ea); color: var(--ui-danger-ink, #a12a2e); font-size: 0.6875rem; font-weight: 600; }
  .rv-rating { display: flex; align-items: center; gap: 8px; margin-top: 4px; }
  .rv-rating-label { font-size: 0.75rem; font-weight: 600; color: var(--rv-ink-2); }
  .rv-text { margin: 8px 0 0; font-size: 0.84375rem; line-height: 1.55; color: var(--rv-ink); white-space: pre-line; overflow-wrap: anywhere; }
  .rv-text.is-empty { font-size: 0.78125rem; font-style: italic; color: var(--rv-muted); }
  .rv-none { margin: 0; padding: 28px 4px; text-align: center; font-size: 0.8125rem; color: var(--rv-muted); }
  .rv-more { display: block; margin: 8px auto 4px; padding: 7px 18px; border: 1px solid var(--ui-border, #dfe3eb); border-radius: 9px; background: var(--ui-surface, #fff); font-size: 0.8125rem; font-weight: 500; color: var(--rv-ink); cursor: pointer; }
  .rv-more:hover { background: var(--ui-surface-2, #f6f7fb); }

  .rv-empty { padding: 44px 16px; text-align: center; }
  .rv-empty-ic { display: inline-flex; align-items: center; justify-content: center; width: 56px; height: 56px; margin-bottom: 12px; border-radius: 50%; background: var(--ui-warning-soft, #fff4dc); color: var(--ui-warning-ink, #b77f00); font-size: 1.625rem; }
  .rv-empty h4 { margin: 0 0 6px; font-size: 1rem; font-weight: 600; color: var(--rv-ink); }
  .rv-empty p { max-width: 420px; margin: 0 auto; font-size: 0.8125rem; color: var(--rv-muted); }

  @media (max-width: 767.98px) {
    .rv-summary { grid-template-columns: minmax(0, 1fr); gap: 16px; }
    .rv-when { margin-left: 0; flex-basis: 100%; }
  }
</style>

<div class="rv" id="rv">
  @if($rvTotal === 0)
    <div class="rv-empty">
      <span class="rv-empty-ic" aria-hidden="true">&#9733;</span>
      <h4>No reviews yet</h4>
      <p>Patrons are asked for a quick review after they download this material or read it online. Their reviews will appear here.</p>
    </div>
  @else
    <div class="rv-summary">
      <div class="rv-avg">
        <div class="rv-avg-num">{{ number_format($rvAvg, 1) }} <span class="rv-avg-of">/ 5</span></div>
        <span class="rv-stars rv-stars-lg" role="img" aria-label="Average rating {{ number_format($rvAvg, 1) }} out of 5"><span style="width: {{ $rvAvg / 5 * 100 }}%"></span></span>
        <div class="rv-avg-sub">{{ $rvTotal }} {{ Str::plural('review', $rvTotal) }} · {{ $rvWithText }} with comments</div>
      </div>
      <div>
        <div class="rv-dist" role="group" aria-label="Filter by star rating">
          @foreach($rvDist as $star => $n)
            <button type="button" class="rv-dist-row" data-rv-star="{{ $star }}" aria-pressed="false" @disabled($n === 0)
                    aria-label="{{ $star }} stars: {{ $n }} {{ Str::plural('review', $n) }}{{ $n ? ' — show only these' : '' }}">
              <span class="rv-dist-label">{{ $star }}</span>
              <span class="rv-dist-track" aria-hidden="true"><span style="width: {{ $rvTotal ? round($n / $rvTotal * 100) : 0 }}%"></span></span>
              <span class="rv-dist-n">{{ $n }}</span>
            </button>
          @endforeach
        </div>
        <p class="rv-dist-hint">Click a row to show only those reviews.</p>
      </div>
    </div>

    <div class="rv-toolbar">
      <p class="rv-status" id="rv-status" aria-live="polite">{{ $rvTotal }} {{ Str::plural('review', $rvTotal) }}, newest first</p>
      <div class="rv-controls">
        <div class="form-check form-switch">
          <input class="form-check-input" type="checkbox" role="switch" id="rv-text-only" @disabled($rvWithText === 0)>
          <label class="form-check-label" for="rv-text-only">With comments only</label>
        </div>
        <label class="rv-sort" for="rv-sort">Sort by
          <select class="form-select form-select-sm" id="rv-sort">
            <option value="newest">Newest first</option>
            <option value="oldest">Oldest first</option>
            <option value="highest">Highest rating</option>
            <option value="lowest">Lowest rating</option>
          </select>
        </label>
      </div>
    </div>

    <ol class="rv-list" id="rv-list">
      @foreach($reviews as $r)
        @php
          $when = \Carbon\Carbon::parse($r->created_at);
          $text = trim((string) $r->remarks);
          $name = $r->name ?: 'Former patron';
        @endphp
        <li class="rv-item" data-rating="{{ (int) $r->rating }}" data-time="{{ $when->timestamp }}" data-id="{{ $r->id }}" data-text="{{ $text !== '' ? 1 : 0 }}">
          <span class="rv-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($name, 0, 1)) }}</span>
          <div class="rv-body">
            <div class="rv-head">
              <span class="rv-name">{{ $name }}</span>
              @if($r->is_banned)<span class="rv-badge">Banned</span>@elseif($r->is_blocked)<span class="rv-badge">Blocked</span>@endif
              @if($rvEmails && $r->email)<span class="rv-email">{{ $r->email }}</span>@endif
              <time class="rv-when" datetime="{{ $when->toIso8601String() }}" title="{{ $when->format('M j, Y g:i A') }}">{{ $when->format('M j, Y') }} · {{ $when->diffForHumans() }}</time>
            </div>
            <div class="rv-rating">
              <span class="rv-stars" role="img" aria-label="{{ (int) $r->rating }} out of 5 stars"><span style="width: {{ (int) $r->rating * 20 }}%"></span></span>
              <span class="rv-rating-label">{{ $rvLabels[(int) $r->rating] ?? '' }}</span>
            </div>
            @if($text !== '')
              <p class="rv-text">{{ $text }}</p>
            @else
              <p class="rv-text is-empty">Star rating only, no comment.</p>
            @endif
          </div>
        </li>
      @endforeach
    </ol>
    <p class="rv-none" id="rv-none" hidden>No reviews match these filters. <button type="button" class="rv-link" data-rv-clear>Clear filters</button></p>
    <button type="button" class="rv-more" id="rv-more" hidden></button>
  @endif
</div>

<script nonce="{{ cspNonce() }}">
(function () {
  'use strict';
  var list = document.getElementById('rv-list');
  if (!list) return;

  var PAGE = 10;
  var items = Array.prototype.slice.call(list.children);
  var total = items.length;
  var state = { star: null, textOnly: false, sort: 'newest', limit: PAGE };
  var rows = document.querySelectorAll('.rv-dist-row');
  var status = document.getElementById('rv-status');
  var more = document.getElementById('rv-more');
  var none = document.getElementById('rv-none');
  var textOnly = document.getElementById('rv-text-only');
  var sort = document.getElementById('rv-sort');

  function n(li, key) { return +li.getAttribute('data-' + key); }
  function newest(a, b) { return n(b, 'time') - n(a, 'time') || n(b, 'id') - n(a, 'id'); }
  var ORDER = {
    newest: newest,
    oldest: function (a, b) { return -newest(a, b); },
    highest: function (a, b) { return n(b, 'rating') - n(a, 'rating') || newest(a, b); },
    lowest: function (a, b) { return n(a, 'rating') - n(b, 'rating') || newest(a, b); }
  };
  var SORT_TEXT = { newest: 'newest first', oldest: 'oldest first', highest: 'highest rating first', lowest: 'lowest rating first' };

  function plural(k, word) { return k + ' ' + word + (k === 1 ? '' : 's'); }
  function matches(li) { return (state.star === null || n(li, 'rating') === state.star) && (!state.textOnly || n(li, 'text') === 1); }

  function apply() {
    var sorted = items.slice().sort(ORDER[state.sort]);
    var shown = sorted.filter(matches);
    sorted.forEach(function (li) { list.appendChild(li); li.hidden = true; });
    shown.slice(0, state.limit).forEach(function (li) { li.hidden = false; });

    var filtered = state.star !== null || state.textOnly;
    status.textContent = '';
    if (filtered) {
      var what = [state.star !== null ? 'rated ' + state.star + ' ★' : '', state.textOnly ? 'with comments' : ''].filter(Boolean).join(', ');
      var strong = document.createElement('strong');
      strong.textContent = shown.length + ' of ' + plural(total, 'review');
      status.appendChild(strong);
      status.appendChild(document.createTextNode(' ' + what + ', ' + SORT_TEXT[state.sort] + ' · '));
      var clear = document.createElement('button');
      clear.type = 'button'; clear.className = 'rv-link'; clear.setAttribute('data-rv-clear', '');
      clear.textContent = 'Clear filters';
      status.appendChild(clear);
    } else {
      status.textContent = plural(total, 'review') + ', ' + SORT_TEXT[state.sort];
    }

    none.hidden = shown.length > 0;
    var left = shown.length - state.limit;
    more.hidden = left <= 0;
    if (left > 0) more.textContent = 'Show ' + Math.min(PAGE, left) + ' more (' + left + ' left)';
    rows.forEach(function (r) { r.setAttribute('aria-pressed', +r.getAttribute('data-rv-star') === state.star ? 'true' : 'false'); });
  }

  rows.forEach(function (r) {
    r.addEventListener('click', function () {
      var star = +r.getAttribute('data-rv-star');
      state.star = state.star === star ? null : star;   // clicking the active row clears it
      state.limit = PAGE;
      apply();
    });
  });
  textOnly.addEventListener('change', function () { state.textOnly = textOnly.checked; state.limit = PAGE; apply(); });
  sort.addEventListener('change', function () { state.sort = sort.value; apply(); });
  more.addEventListener('click', function () {
    // The first review this reveals: move focus there so keyboard users land on the new batch.
    var first = Array.prototype.filter.call(list.children, function (li) { return li.hidden && matches(li); })[0];
    state.limit += PAGE;
    apply();
    if (first) { first.setAttribute('tabindex', '-1'); first.focus(); }
  });
  document.getElementById('rv').addEventListener('click', function (e) {
    if (!e.target.closest('[data-rv-clear]')) return;
    state.star = null; state.textOnly = false; textOnly.checked = false; state.limit = PAGE;
    apply();
  });


  apply();
})();
</script>
