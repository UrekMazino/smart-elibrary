@extends("admin.layouts.master")

@section('css')
  <link rel="stylesheet" href="{{ versionedAsset('css/admin/dashboard.css') }}">
@endsection

@section('content')
@php
  $hour = now()->hour;
  $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
  $pct = fn ($part, $whole) => $whole ? (int) round($part / $whole * 100) : 0;
  $barList = function ($rows) { $max = max(1, (int) collect($rows)->max('total')); return collect($rows)->map(fn ($r) => ['name' => $r->name, 'total' => (int) $r->total, 'w' => max(2, (int) round($r->total / $max * 100))]); };
  $types = $barList($byType);
  $categories = $barList($byCategory);
@endphp
<div class="page-content dsh">
  @include('admin.partials.breadcrumb', ['page' => 'dashboard'])

  {{-- ============ Header: greeting + live clock + quick actions ============
       Server time (app timezone) is only the no-JS fallback: dashboard.js swaps in the
       viewer's own time zone and keeps the date, clock and greeting running. --}}
  <header class="dsh-head">
    <div>
      <p class="dsh-eyebrow">
        <time id="dsh-today" datetime="{{ now()->toDateString() }}">{{ now()->format('l, F j, Y') }}</time>
        <span aria-hidden="true">·</span>
        <time class="dsh-clock" id="dsh-clock" datetime="{{ now()->toIso8601String() }}">{{ now()->format('g:i:s A') }}</time>
        <span class="dsh-tz" id="dsh-tz">GMT{{ now()->format('P') }}</span>
      </p>
      <h1 class="dsh-title"><span id="dsh-greeting">{{ $greeting }}</span>{{ $firstName !== '' ? ', ' . $firstName : '' }}</h1>
    </div>
    <div class="dsh-actions">
      @if($links['newRecord'])
        <a class="dsh-btn dsh-btn-primary" href="{{ $links['newRecord'] }}"><i data-feather="plus"></i> New record</a>
      @endif
      @if($links['newPatron'])
        <a class="dsh-btn" href="{{ $links['newPatron'] }}"><i data-feather="user-plus"></i> Register patron</a>
      @endif
      @if($links['reports'])
        <a class="dsh-btn" href="{{ $links['reports'] }}"><i data-feather="bar-chart-2"></i> Reports</a>
      @endif
    </div>
  </header>

  {{-- ============ Needs attention (not affected by the date range) ============ --}}
  <section class="dsh-section" aria-labelledby="dsh-attn-h">
    <h2 class="dsh-h2" id="dsh-attn-h">Needs attention</h2>
    <div class="dsh-attn" style="--dsh-attn-cols: {{ count($attention) }}">
      @foreach($attention as $a)
        @php $tag = $a['url'] ? 'a' : 'div'; $clear = $a['count'] === 0; @endphp
        <{{ $tag }} class="dsh-card dsh-attn-tile {{ $clear ? 'is-clear' : 'tone-' . $a['tone'] }}" @if($a['url']) href="{{ $a['url'] }}" @endif>
          <span class="dsh-ic"><i data-feather="{{ $clear ? 'check' : $a['icon'] }}"></i></span>
          <span class="dsh-attn-body">
            <span class="dsh-attn-num">{{ number_format($a['count']) }}</span>
            <span class="dsh-attn-label">{{ $a['label'] }}</span>
            <span class="dsh-attn-hint">{{ $clear ? 'All clear' : $a['hint'] }}</span>
          </span>
          @if($a['url'])<i data-feather="chevron-right" class="dsh-attn-go"></i>@endif
        </{{ $tag }}>
      @endforeach
    </div>
  </section>

  {{-- ============ Activity (everything here follows the date range) ============ --}}
  <section class="dsh-section" id="dsh-activity" aria-labelledby="dsh-act-h">
    <div class="dsh-section-head">
      <div>
        <h2 class="dsh-h2" id="dsh-act-h">Activity</h2>
        <p class="dsh-sub" id="dsh-compare">&nbsp;</p>
      </div>
      <div class="dropdown dsh-range">
        <button class="dsh-btn dsh-range-btn" type="button" id="dsh-range-btn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false">
          <i data-feather="calendar"></i> <span id="dsh-range-label">Last 30 days</span> <i data-feather="chevron-down"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end dsh-range-menu" aria-labelledby="dsh-range-btn">
          @foreach($ranges as $key => $label)
            <button type="button" class="dsh-range-opt" data-range="{{ $key }}" aria-pressed="false">
              <span>{{ $label }}</span><i data-feather="check"></i>
            </button>
          @endforeach
          <form class="dsh-range-custom" id="dsh-range-custom">
            <span class="dsh-range-custom-h">Custom range</span>
            <div class="dsh-range-dates">
              <label><span>From</span><input type="date" name="from" required max="{{ now()->toDateString() }}"></label>
              <label><span>To</span><input type="date" name="to" required max="{{ now()->toDateString() }}"></label>
            </div>
            <button type="submit" class="dsh-btn dsh-btn-primary w-100">Apply</button>
          </form>
        </div>
      </div>
    </div>

    <div class="dsh-alert" id="dsh-error" hidden>
      Couldn't load activity for this range. <button type="button" class="dsh-link" id="dsh-retry">Try again</button>
    </div>

    @php
      // What each card shows and how it is counted (DashboardService::series(): rows of the table dated in the range).
      $kpiInfo = [
        'views' => ['Record views', [
          'How often materials were looked at: a record page opened in the OPAC, or a full text read online.',
          'One view per viewer per material a day: a signed-in reader is told apart by account, a guest by a coded IP address and browser. Reloading the page doesn’t add more, and at most 120 views an hour count from one viewer. Staff and search engines aren’t counted. Views saved before 28 October 2026 include every reload.',
          '“% by guests”: the share of the views by visitors who weren’t signed in.',
        ]],
        'downloads' => ['Downloads', [
          'PDFs downloaded by signed-in patrons.',
          'Every download counts here, a patron downloading the same file again included (Most Downloaded under Top materials counts each reader once). Staff downloads aren’t counted; banned accounts can’t download; at most 30 a minute per account.',
        ]],
        'reads' => ['Online reads', [
          'Materials patrons started reading in the online reader.',
          'Counted once per patron per material: the first time they open its full text in the reader, dated that day. Opening it again doesn’t add more. Staff reads aren’t recorded.',
        ]],
        'patrons' => ['New patrons', [
          'Patron accounts created in the range, however they signed up (the registration form, Google, or staff registering them). Deleted accounts aren’t counted; staff accounts aren’t patrons.',
          '“Patrons in all”: every patron account now.',
        ]],
      ];
    @endphp
    <div class="dsh-kpis-wrap">
    <div class="dsh-kpis" role="tablist" aria-label="Activity metric — choose one to chart it">
      @foreach([
        'views'     => ['eye', 'Record views'],
        'downloads' => ['download', 'Downloads'],
        'reads'     => ['book-open', 'Online reads'],
        'patrons'   => ['user-plus', 'New patrons'],
      ] as $metric => [$icon, $label])
        <button type="button" class="dsh-card dsh-kpi" role="tab" id="dsh-tab-{{ $metric }}" data-metric="{{ $metric }}"
                aria-selected="{{ $loop->first ? 'true' : 'false' }}" aria-controls="dsh-trend-card" tabindex="{{ $loop->first ? '0' : '-1' }}">
          <span class="dsh-kpi-label"><i data-feather="{{ $icon }}"></i> {{ $label }}</span>
          <span class="dsh-kpi-value" data-kpi-value>&mdash;</span>
          <span class="dsh-kpi-delta" data-kpi-delta>&nbsp;</span>
          <span class="dsh-kpi-foot" data-kpi-foot>&nbsp;</span>
          <svg class="dsh-spark" data-kpi-spark viewBox="0 0 120 32" preserveAspectRatio="none" aria-hidden="true"></svg>
        </button>
      @endforeach
    </div>
    {{-- An "about" button over each card's top-right corner. Outside the cards: a card is a tab (a button), which can't
         hold another button, and a tablist holds only tabs. Same grid as the cards, so each lands on its own card. --}}
    <div class="dsh-kpi-infos">
      @foreach($kpiInfo as $metric => [$title, $paras])
        <div class="dsh-kpi-info">
          <button type="button" class="dsh-info-btn" aria-expanded="false" aria-controls="dsh-info-{{ $metric }}" aria-label="About {{ $title }}" title="About {{ $title }}">
            <i data-feather="info"></i>
          </button>
          <div class="dsh-info-pop" id="dsh-info-{{ $metric }}" hidden>
            <strong>{{ $title }}</strong>
            @foreach($paras as $p)<p>{{ $p }}</p>@endforeach
            <p class="dsh-info-common">Compared with the same length of time just before the range (not for All time). The line shows it day by day (month by month for long ranges).</p>
          </div>
        </div>
      @endforeach
    </div>
    </div>

    <div class="row g-3">
      <div class="col-xl-8">
        <div class="dsh-card dsh-panel" id="dsh-trend-card" role="tabpanel" aria-labelledby="dsh-trend-title">
          <div class="dsh-panel-head">
            <div>
              <h3 class="dsh-h3" id="dsh-trend-title">Record views</h3>
              <p class="dsh-sub" id="dsh-trend-sub">&nbsp;</p>
            </div>
            <div class="dsh-panel-tools">
              <ul class="dsh-legend" id="dsh-legend">
                <li><span class="dsh-key dsh-key-current"></span><span id="dsh-legend-current">This period</span></li>
                <li id="dsh-legend-prev-li"><span class="dsh-key dsh-key-prev"></span><span id="dsh-legend-prev">Previous period</span></li>
              </ul>
              <button type="button" class="dsh-icon-btn" id="dsh-table-toggle" aria-pressed="false" title="Show as a table"><i data-feather="list"></i><span class="visually-hidden">Show as a table</span></button>
            </div>
          </div>
          <div id="dsh-trend-chart" class="dsh-trend-chart"></div>
          <div id="dsh-trend-table" class="dsh-table-wrap" hidden></div>
        </div>
      </div>

      <div class="col-xl-4">
        <div class="dsh-card dsh-panel">
          {{-- Utilities → Rankings' lists (DashboardService::rankingsTop, App\Services\Rankings): the same numbers. --}}
          <div class="dsh-panel-head">
            <div>
              <h3 class="dsh-h3">Top materials</h3>
              <p class="dsh-sub" id="dsh-top-sub">&nbsp;</p>
            </div>
            <div class="dsh-seg" role="group" aria-label="Rank by">
              <button type="button" data-top="viewed" aria-pressed="true">Views</button>
              <button type="button" data-top="downloaded" aria-pressed="false">Downloads</button>
              <button type="button" data-top="rated" aria-pressed="false">Rating</button>
            </div>
          </div>
          <ol class="dsh-top" id="dsh-top"></ol>
          @if($links['rankings'])
            <p class="dsh-foot"><a class="dsh-link" id="dsh-top-link" href="{{ $links['rankings'] }}" data-base="{{ $links['rankings'] }}">All rankings</a></p>
          @endif
        </div>
      </div>
    </div>
  </section>

  {{-- ============ Library at a glance (not affected by the date range) ============ --}}
  <section class="dsh-section" aria-labelledby="dsh-lib-h">
    <h2 class="dsh-h2" id="dsh-lib-h">Library at a glance</h2>
    <div class="row g-3">
      <div class="col-lg-6 col-xl-4">
        <div class="dsh-card dsh-panel">
          <h3 class="dsh-h3">Collection</h3>
          <div class="dsh-hero">{{ number_format($collection['titles']) }} <span>titles</span></div>
          <p class="dsh-sub mb-3">
            {{ number_format($collection['published']) }} published ·
            @if($collection['onHold'] && $links['onHold'])
              <a href="{{ $links['onHold'] }}">{{ number_format($collection['onHold']) }} on hold</a>
            @else
              {{ number_format($collection['onHold']) }} on hold
            @endif
          </p>
          @foreach([
            ['Full text attached', $collection['fulltext']],
            ['Cover image', $collection['covers']],
          ] as [$label, $n])
            @php $p = $pct($n, $collection['titles']); @endphp
            <div class="dsh-meter">
              <div class="dsh-meter-top"><span>{{ $label }}</span><span><strong>{{ $p }}%</strong> <small>{{ number_format($n) }} of {{ number_format($collection['titles']) }}</small></span></div>
              <div class="dsh-meter-track" role="img" aria-label="{{ $label }}: {{ $p }}%"><span style="width: {{ $p }}%"></span></div>
            </div>
          @endforeach
          <p class="dsh-foot"><i data-feather="book"></i> {{ number_format($collection['activeLoans']) }} {{ $collection['activeLoans'] === 1 ? 'copy' : 'copies' }} out on loan</p>
        </div>
      </div>

      <div class="col-lg-6 col-xl-4">
        <div class="dsh-card dsh-panel">
          <h3 class="dsh-h3">Titles by material type</h3>
          @include('admin.partials.dashboard-bars', ['rows' => $types, 'empty' => 'No titles yet.', 'unit' => 'titles'])
        </div>
      </div>

      <div class="col-lg-12 col-xl-4">
        <div class="dsh-card dsh-panel">
          <div class="dsh-panel-head">
            <h3 class="dsh-h3">Patrons by category</h3>
            @if($links['patrons'])<a class="dsh-link" href="{{ $links['patrons'] }}">All patrons</a>@endif
          </div>
          @include('admin.partials.dashboard-bars', ['rows' => $categories, 'empty' => 'No patrons yet.', 'unit' => 'patrons'])
          <p class="dsh-foot">
            <i data-feather="users"></i> {{ number_format($patrons['total']) }} {{ $patrons['total'] === 1 ? 'patron' : 'patrons' }}
            @if($patrons['banned']) · {{ number_format($patrons['banned']) }} banned @endif
            @if($patrons['blocked']) · {{ number_format($patrons['blocked']) }} blocked @endif
          </p>
        </div>
      </div>

      <div class="col-xl-6">
        <div class="dsh-card dsh-panel">
          <h3 class="dsh-h3">Recent reviews</h3>
          <ul class="dsh-feed">
            @forelse($reviews as $r)
              <li>
                <span class="dsh-stars" role="img" aria-label="{{ $r['rating'] }} of 5 stars">
                  @for($i = 1; $i <= 5; $i++)<span class="{{ $i <= $r['rating'] ? 'on' : '' }}">&#9733;</span>@endfor
                </span>
                <div class="dsh-feed-body">
                  @if($links['record'] && $r['catalog_id'])
                    <a class="dsh-feed-title" href="{{ $links['record'] }}/{{ $r['catalog_id'] }}#reviews">{{ $r['title'] }}</a>
                  @else
                    <span class="dsh-feed-title">{{ $r['title'] }}</span>
                  @endif
                  @if($r['remarks'] !== '')<p class="dsh-quote">“{{ $r['remarks'] }}”</p>@endif
                  <span class="dsh-feed-meta">{{ $r['name'] }} · <time datetime="{{ $r['when']->toIso8601String() }}" title="{{ $r['when']->format('M j, Y g:i A') }}">{{ $r['when']->diffForHumans() }}</time></span>
                </div>
              </li>
            @empty
              <li class="dsh-empty">No reviews yet.</li>
            @endforelse
          </ul>
        </div>
      </div>

      <div class="col-xl-6">
        <div class="dsh-card dsh-panel">
          <div class="dsh-panel-head">
            <h3 class="dsh-h3">Newest patrons</h3>
            @if($links['patrons'])<a class="dsh-link" href="{{ $links['patrons'] }}">All patrons</a>@endif
          </div>
          <ul class="dsh-feed">
            @forelse($newPatrons as $p)
              <li>
                <span class="dsh-avatar" aria-hidden="true">{{ mb_strtoupper(mb_substr($p['name'], 0, 1)) }}</span>
                <div class="dsh-feed-body">
                  <span class="dsh-feed-title">{{ $p['name'] }}
                    @if($p['status'])<span class="dsh-badge">{{ $p['status'] }}</span>@endif
                  </span>
                  <span class="dsh-feed-meta">{{ $p['email'] }} · {{ $p['type'] }}</span>
                </div>
                <time class="dsh-feed-when" datetime="{{ $p['when']->toIso8601String() }}" title="{{ $p['when']->format('M j, Y g:i A') }}">{{ $p['when']->diffForHumans() }}</time>
              </li>
            @empty
              <li class="dsh-empty">No patrons yet.</li>
            @endforelse
          </ul>
        </div>
      </div>
    </div>
  </section>

</div>
@endsection

@section('js')
  <script nonce="{{ cspNonce() }}">
    window.DASHBOARD = {
      activityUrl: @json(url('/admin/dashboard/activity')),
      ranges: @json($ranges),
      recordUrl: @json($links['record']),
      patronsTotal: {{ (int) $patrons['total'] }}
    };
  </script>
  <script src="{{ asset('js/admin/dashboard.js') }}?v={{ filemtime(public_path('js/admin/dashboard.js')) }}"></script>
@endsection
