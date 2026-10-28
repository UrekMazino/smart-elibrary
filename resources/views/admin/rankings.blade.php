@extends("admin.layouts.master")
{{-- Administration → Utilities → Rankings (RankingsController, App\Services\Rankings): the home page's three top-10
     lists for a window, the next ones down, and the numbers behind each rank. --}}
@use('App\Services\Rankings')
@php
    $recent = $window === 'recent';
    $records = auth()->user()->can('access.cataloging');   // "View in Records" needs the Cataloging module
    $windowLabel = rankingWindowLabel($window);
    $leftOut = $totals->banned + $totals->out;
    $foot = [
        'downloaded' => 'Readers who downloaded it: a reader downloading again counts once. Patrons only.',
        'viewed' => 'Viewer-days: a viewer counts once a day per material, at most ' . viewCapPerHour() . ' views an hour. Staff and search engines don’t count.',
        'rated' => 'Score = v/(v+' . Rankings::VOTES_WEIGHT . ')·R + ' . Rankings::VOTES_WEIGHT . '/(v+' . Rankings::VOTES_WEIGHT . ')·C: R its average, v its ratings, C = '
            . number_format($totals->average, 2) . ' (every rating in the ' . $windowLabel . '). One rating can’t top the list.',
    ];
@endphp

@section('css')
    <style>
      .rk-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 10px; margin-bottom: 4px; }
      .rk-tools { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
      .rk-seg { display: inline-flex; padding: 2px; border: 1px solid var(--ui-border, #e3e7ef); border-radius: 8px; background: var(--ui-surface-2, #f4f6fa); }
      .rk-seg a { padding: 4px 12px; border-radius: 6px; font-size: 0.78125rem; font-weight: 600; color: var(--ui-text-2, #4a5a78); text-decoration: none; }
      .rk-seg a:hover { color: var(--ui-text, #22283b); }
      .rk-seg a[aria-current="page"] { background: var(--ui-surface, #fff); color: var(--ui-text, #22283b); box-shadow: 0 1px 2px rgba(20, 30, 60, .12); }
      .rk-intro { color: var(--ui-muted, #7987a1); font-size: 0.78125rem; margin: 0 0 14px; }

      .rk-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(130px, 1fr)); gap: 12px; }
      .rk-tile { padding: 12px 16px; border: 1px solid var(--ui-border, #eef0f4); border-radius: 10px; background: var(--ui-surface, #fff); color: inherit; text-decoration: none; }
      a.rk-tile:hover { border-color: var(--ui-border-strong, #d7dded); color: inherit; }
      .rk-tile strong { display: block; font-size: 1.5rem; font-weight: 700; line-height: 1.2; color: var(--ui-text, #22283b); font-variant-numeric: tabular-nums; }
      .rk-tile span { display: block; font-size: 0.75rem; color: var(--ui-muted, #7987a1); }
      .rk-tile .rk-tile-name { color: var(--ui-text-2, #4a5a78); font-weight: 600; }

      .rk-lists { display: grid; grid-template-columns: repeat(auto-fit, minmax(330px, 1fr)); gap: 16px; align-items: start; margin-bottom: 16px; }
      .rk-list .card-body { display: flex; flex-direction: column; }
      .rk-list-head { display: flex; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
      .rk-list-head span { font-size: 0.71875rem; color: var(--ui-muted, #7987a1); }
      .rk-rows { list-style: none; margin: 0; padding: 0; }
      .rk-row { display: flex; align-items: center; gap: 10px; padding: 7px 0; }
      .rk-row + .rk-row { border-top: 1px solid var(--ui-border, #eef0f2); }
      .rk-rank { flex: 0 0 26px; height: 26px; border-radius: 50%; display: flex; align-items: center; justify-content: center;
                 font-size: 0.75rem; font-weight: 700; color: var(--ui-text-2, #4a5a78); background: var(--ui-surface-2, #eef1f6); font-variant-numeric: tabular-nums; }
      .rk-rank.is-1 { background: #f7e7a6; color: #5c4400; }
      .rk-rank.is-2 { background: #e3e7ee; color: #3a4556; }
      .rk-rank.is-3 { background: #f1d3bb; color: #6b3410; }
      .rk-cover { flex: 0 0 30px; width: 30px; height: 42px; object-fit: cover; border-radius: 3px; background: var(--ui-surface-2, #eef1f6);
                  box-shadow: 0 1px 2px rgba(20, 30, 60, .18); }
      .rk-what { flex: 1 1 auto; min-width: 0; display: flex; flex-direction: column; }
      .rk-title { font-size: 0.8125rem; font-weight: 600; color: var(--ui-text, #22283b); line-height: 1.3;
                  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
      .rk-title:hover { text-decoration: underline; color: var(--ui-text, #22283b); }
      /* A title opens a choice: the record in Cataloging, or its page in the OPAC. */
      button.rk-title { padding: 0; border: 0; background: none; text-align: left; font-family: inherit; cursor: pointer; }
      .rk-pick .dropdown-menu { font-size: 0.8125rem; min-width: 12rem; }
      .rk-pick .dropdown-item svg { width: 14px; height: 14px; margin-right: 8px; vertical-align: -2px; }
      .rk-sub { font-size: 0.71875rem; color: var(--ui-muted, #7987a1); }
      .rk-num { flex: 0 0 auto; text-align: right; display: flex; flex-direction: column; line-height: 1.15; }
      .rk-num strong { font-size: 1rem; font-weight: 700; color: var(--ui-text, #22283b); font-variant-numeric: tabular-nums; }
      .rk-num span { font-size: 0.6875rem; color: var(--ui-muted, #7987a1); }
      .rk-next-head { margin: 12px 0 2px; padding-top: 10px; border-top: 1px dashed var(--ui-border-strong, #d7dded);
                      font-size: 0.6875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1); }
      .rk-next .rk-row { opacity: .72; }
      .rk-empty { color: var(--ui-muted, #7987a1); font-size: 0.8125rem; margin: 6px 0 4px; }
      .rk-foot { margin: 12px 0 0; padding-top: 10px; border-top: 1px solid var(--ui-border, #eef0f2); font-size: 0.71875rem; color: var(--ui-muted, #7987a1); }

      .rk-rules { margin: 0; padding-left: 18px; font-size: 0.8125rem; color: var(--ui-text-2, #4a5a78); }
      .rk-rules li + li { margin-top: 6px; }
    </style>
@endsection

@section('content')
<div class="page-content">

    @include('admin.partials.breadcrumb', ['page' => 'rankings'])

    @if (session('success'))
        <div class="alert alert-success py-2" style="font-size: 0.8125rem;" role="status">{{ session('success') }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="rk-head">
                <h6 class="card-title mb-0">RANKINGS</h6>
                <div class="rk-tools">
                    <nav class="rk-seg" aria-label="Which window">
                        <a href="{{ route('utilities.rankings') }}" @if ($recent) aria-current="page" @endif>{{ ucfirst(rankingWindowLabel()) }}</a>
                        <a href="{{ route('utilities.rankings', ['window' => 'all']) }}" @unless ($recent) aria-current="page" @endunless>All time</a>
                    </nav>
                    <form method="POST" action="{{ route('utilities.rankings.refresh') }}">
                        @csrf
                        <button type="submit" class="btn btn-outline-secondary btn-sm"><i data-feather="refresh-cw" class="icon-sm me-1"></i>Refresh the home page</button>
                    </form>
                </div>
            </div>
            <p class="rk-intro">
                The home page’s top-10 lists for the {{ $windowLabel }}, worked out now, with the numbers behind each rank.
                Ranks 1–{{ Rankings::TOP }} are on the home page{{ $recent ? ' and earn the medals on covers' : ' when a visitor switches to All time' }};
                below them, the next ones down. The home page keeps its lists for 10 minutes: “Refresh the home page” shows these at once.
            </p>
            <div class="rk-tiles">
                <div class="rk-tile"><span class="rk-tile-name">Views</span><strong>{{ number_format($totals->views) }}</strong>
                    <span>viewer-days · {{ number_format($totals->guestViews) }} by guests</span></div>
                <div class="rk-tile"><span class="rk-tile-name">Downloads</span><strong>{{ number_format($totals->readers) }}</strong>
                    <span>{{ Str::plural('reader', $totals->readers) }} · {{ number_format($totals->files) }} {{ Str::plural('file', $totals->files) }}</span></div>
                <div class="rk-tile"><span class="rk-tile-name">Ratings</span><strong>{{ number_format($totals->ratings) }}</strong>
                    <span>{{ $totals->ratings ? 'average ★ ' . number_format($totals->average, 2) : 'none yet' }}</span></div>
                @if ($watch)
                    <a class="rk-tile" href="{{ route('admin.statistics-watch') }}#sw-out">
                @else
                    <div class="rk-tile">
                @endif
                    <span class="rk-tile-name">Left out</span><strong>{{ number_format($leftOut) }}</strong>
                    <span>{{ $totals->banned }} banned · {{ $totals->out }} taken out</span>
                @if ($watch) </a> @else </div> @endif
            </div>
        </div>
    </div>

    <div class="rk-lists">
        @foreach ($lists as $list)
            <section class="card rk-list" aria-labelledby="rk-{{ $list['key'] }}">
                <div class="card-body">
                    <div class="rk-list-head">
                        <h6 class="card-title mb-0" id="rk-{{ $list['key'] }}">{{ $list['label'] }}</h6>
                        <span>{{ ucfirst($windowLabel) }}</span>
                    </div>
                    @if ($list['rows']->isEmpty())
                        <p class="rk-empty">Nothing yet in the {{ $windowLabel }}.</p>
                    @else
                        @foreach ($list['rows']->chunk(Rankings::TOP) as $part => $rows)
                            @if ($part === 1)<p class="rk-next-head">Just outside the top {{ Rankings::TOP }}</p>@endif
                            <ol class="rk-rows {{ $part ? 'rk-next' : '' }}" start="{{ $part * Rankings::TOP + 1 }}">
                                @foreach ($rows as $i => $r)
                                    <li class="rk-row">
                                        <span @class(['rk-rank', 'is-' . ($i + 1) => $i < 3]) aria-hidden="true">{{ $i + 1 }}</span>
                                        <img class="rk-cover" src="{{ $r->thumb }}" alt="" width="30" height="42" loading="lazy">
                                        <span class="rk-what">
                                            @if ($records)
                                                <span class="dropdown rk-pick">
                                                    <button type="button" class="rk-title" data-bs-toggle="dropdown" aria-expanded="false"
                                                            aria-haspopup="menu" title="View it in Records or in the OPAC">{{ $r->title }}</button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="{{ route('show-record', $r->catalog_id) }}"><i data-feather="book-open"></i>View in Records</a></li>
                                                        <li><a class="dropdown-item" href="{{ $r->link }}" target="_blank" rel="noopener"><i data-feather="external-link"></i>View in OPAC</a></li>
                                                    </ul>
                                                </span>
                                            @else
                                                <a class="rk-title" href="{{ $r->link }}" target="_blank" rel="noopener" title="View in OPAC">{{ $r->title }}</a>
                                            @endif
                                            <span class="rk-sub">
                                                @switch ($list['key'])
                                                    @case('downloaded')
                                                        {{ number_format($r->files) }} {{ Str::plural('file', $r->files) }} · last {{ $r->last?->format('M j, Y') }}
                                                        @break
                                                    @case('viewed')
                                                        {{ number_format($r->total - $r->guests) }} signed in · {{ number_format($r->guests) }} {{ Str::plural('guest', $r->guests) }}
                                                        @break
                                                    @default
                                                        ★ {{ number_format($r->rating, 1) }} · {{ $r->votes }} {{ Str::plural('rating', $r->votes) }}
                                                @endswitch
                                            </span>
                                        </span>
                                        <span class="rk-num">
                                            @if ($list['key'] === 'rated')
                                                <strong>{{ number_format($r->score, 2) }}</strong><span>score</span>
                                            @else
                                                <strong>{{ number_format($r->total) }}</strong>
                                                <span>{{ $list['key'] === 'downloaded' ? Str::plural('reader', $r->total) : Str::plural('view', $r->total) }}</span>
                                            @endif
                                        </span>
                                    </li>
                                @endforeach
                            </ol>
                        @endforeach
                    @endif
                    <p class="rk-foot">{{ $foot[$list['key']] }}</p>
                </div>
            </section>
        @endforeach
    </div>

    <div class="card">
        <div class="card-body">
            <h6 class="card-title mb-2">How the lists work</h6>
            <ul class="rk-rules">
                <li><strong>Window.</strong> The home page opens on the {{ rankingWindowLabel() }}, and the medals on covers (search
                    results, Quick view, the record page) follow it. All time is one click away on the home page.</li>
                <li><strong>Who counts.</strong> Patrons and guests. Staff and search engines don’t; neither do banned accounts nor
                    accounts taken out of the rankings on the
                    @if ($watch)<a href="{{ route('admin.statistics-watch') }}">statistics watch</a>@else statistics watch @endif
                    ({{ $totals->banned }} and {{ $totals->out }} now). Lift the ban, or put the account back, and its counts return.</li>
                <li><strong>Only published materials</strong> are ranked. On a tie, the older record comes first.</li>
                <li><strong>Up to date.</strong> This page works the lists out on every visit. The home page keeps its copy for 10
                    minutes; banning an account or taking one out refreshes it at once, and so does the button above.</li>
            </ul>
        </div>
    </div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
$(function () { if (window.feather) feather.replace(); });
</script>
@endsection
