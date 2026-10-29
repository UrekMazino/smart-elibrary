@extends("admin.layouts.master")
{{-- Administration → Accounts → Statistics watch (StatisticsWatchController, App\Services\StatisticsWatch): a day's
     unusual activity compared with the 30 days before it; taking patrons out of the top-10 lists, and putting them back.
     $addresses: the viewer may see guests' IP addresses (developer and super admin). --}}
@use('App\Services\StatisticsWatch', 'Watch')
@php
    $isToday = $day->isToday();
    $dayLabel = $isToday ? 'today' : $day->format('M j, Y');
    $statusLabel = ['removed' => 'Removed', 'banned' => 'Banned', 'out' => 'Out of the rankings'];
@endphp

@section('css')
    <style>
      .sw-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(140px, 1fr)); gap: 12px; margin-bottom: 4px; }
      .sw-tile { padding: 12px 16px; border: 1px solid var(--ui-border, #eef0f4); border-radius: 10px; background: var(--ui-surface, #fff);
                 color: inherit; text-decoration: none; }
      a.sw-tile:hover { border-color: var(--ui-border-strong, #d7dded); color: inherit; }
      .sw-tile strong { display: block; font-size: 1.5rem; font-weight: 700; line-height: 1.2; color: var(--ui-text, #22283b); }
      .sw-tile span { font-size: 0.75rem; color: var(--ui-muted, #7987a1); }
      .sw-day { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; }
      .sw-note { color: var(--ui-muted, #7987a1); font-size: 0.75rem; margin: -4px 0 12px; }
      .sw-empty { color: var(--ui-muted, #7987a1); font-size: 0.8125rem; padding: 14px 0 4px; margin: 0; }

      .sw-table { font-size: 0.8125rem; }
      .sw-table thead th {
        font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1);
        white-space: nowrap; padding: 9px 10px; border-bottom: 2px solid var(--ui-border, #eef0f2);
      }
      .sw-table tbody td { padding: 8px 10px; vertical-align: middle; white-space: normal; }   /* the template's tables don't wrap */
      .sw-table td:first-child { min-width: 200px; }   /* on a phone the table scrolls rather than squeezing names */
      .sw-table td.sw-why { min-width: 180px; }
      .sw-table .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
      .sw-over { font-weight: 700; color: var(--ui-danger-text, #b42318); }
      .u-name { font-weight: 600; color: var(--ui-text, #22283b); }
      .u-sub { color: var(--ui-muted, #7987a1); font-size: 0.71875rem; word-break: break-all; }
      /* `.sw-table` prefix beats the template's `.badge { color:#fff }`. */
      .sw-table .sw-pill, .sw-burst .sw-pill { background: var(--ui-navy-soft, #e8eefc); color: var(--ui-navy, #33509c); font-weight: 600; font-size: 0.6875rem; }
      .sw-table .sw-pill.is-warn { background: var(--ui-warning-soft, #fff4e5); color: var(--ui-warning-text, #8a4b00); }
      .sw-who summary { cursor: pointer; color: var(--ui-text-2, #4a5a78); }
      .sw-who ul { list-style: none; margin: 6px 0 0; padding: 0; }
      .sw-who li { display: flex; align-items: center; justify-content: space-between; gap: 10px; padding: 3px 0; }
      .sw-burst + .sw-burst { margin-top: 18px; }
      .sw-ip { font-family: var(--bs-font-monospace, monospace); font-size: 0.78125rem; }
      .sw-burst-head { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 6px; }
      .sw-burst-head strong { color: var(--ui-text, #22283b); }
    </style>
@endsection

@section('content')
<div class="page-content">

    @include('admin.partials.breadcrumb', ['page' => 'statistics-watch'])

    @if (session('success'))
        <div class="alert alert-success py-2" style="font-size: 0.8125rem;" role="status">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger py-2" style="font-size: 0.8125rem;" role="alert">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                <h6 class="card-title mb-0">STATISTICS WATCH</h6>
                <form method="GET" action="{{ route('admin.statistics-watch') }}" class="sw-day">
                    <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.statistics-watch', ['day' => $day->copy()->subDay()->toDateString()]) }}"
                       aria-label="The day before">&lsaquo;</a>
                    <input type="date" name="day" value="{{ $day->toDateString() }}" max="{{ today()->toDateString() }}"
                           class="form-control form-control-sm" style="width: auto;" aria-label="Day to watch">
                    @unless ($isToday)
                        <a class="btn btn-outline-secondary btn-sm" href="{{ route('admin.statistics-watch', ['day' => $day->copy()->addDay()->toDateString()]) }}"
                           aria-label="The day after">&rsaquo;</a>
                    @endunless
                    <button type="submit" class="btn btn-primary btn-sm">Show</button>
                    @unless ($isToday)
                        <a class="btn btn-link btn-sm" href="{{ route('admin.statistics-watch') }}">Today</a>
                    @endunless
                </form>
            </div>
            <p class="text-muted mb-3" style="font-size: 0.78125rem;">
                What was unusual {{ $isToday ? 'today' : 'on ' . $dayLabel }}, compared with the {{ Watch::BASELINE_DAYS }} days before.
                Unusual isn’t always wrong: a class assignment or a newsletter can do it too. Look, then decide. Taking an account
                out of the rankings stops its views, downloads and ratings counting in the top-10 lists; it can still sign in and
                use the library, and you can put it back. To stop an account altogether, ban it in Patrons.
            </p>
            <div class="sw-tiles">
                <a class="sw-tile" href="#sw-spikes"><strong>{{ $spikes->count() }}</strong><span>{{ Str::plural('Material', $spikes->count()) }} with a spike</span></a>
                <a class="sw-tile" href="#sw-accounts"><strong>{{ $accounts->count() }}</strong><span>Unusual {{ Str::plural('account', $accounts->count()) }}</span></a>
                <a class="sw-tile" href="#sw-guests"><strong>{{ $guests->count() }}</strong><span>Unusual {{ Str::plural('guest', $guests->count()) }}</span></a>
                <a class="sw-tile" href="#sw-signups"><strong>{{ $signUps->bursts->count() }}</strong><span>Sign-up {{ Str::plural('burst', $signUps->bursts->count()) }} · {{ $signUps->total }} {{ Str::plural('sign-up', $signUps->total) }} in all</span></a>
                <a class="sw-tile" href="#sw-out"><strong>{{ $excluded->count() }}</strong><span>Out of the rankings now</span></a>
            </div>
        </div>
    </div>

    {{-- Materials with a spike --}}
    <div class="card mb-3" id="sw-spikes">
        <div class="card-body">
            <h6 class="card-title mb-2">Materials with a spike</h6>
            <p class="sw-note">Far more viewers or readers than on their usual day: {{ Watch::SPIKE_TIMES }}× their usual day and at least
                {{ Watch::SPIKE_AT_LEAST }}. Counted as the top-10 lists count them, so accounts already out of the rankings, or banned, are left out.</p>
            @if ($spikes->isEmpty())
                <p class="sw-empty">No material had an unusual day.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 sw-table">
                        <thead>
                            <tr><th>Material</th><th>What</th><th class="num">That day</th><th class="num">Usual day</th><th>From</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($spikes as $s)
                                <tr>
                                    <td>
                                        @if ($s->link)<a href="{{ $s->link }}" target="_blank" rel="noopener" class="u-name">{{ $s->title }}</a>@else<span class="u-name">{{ $s->title }}</span>@endif
                                    </td>
                                    <td>{{ $s->what === 'views' ? 'Views' : 'Downloads' }}</td>
                                    <td class="num"><span class="sw-over">{{ number_format($s->count) }}</span>
                                        <span class="u-sub">{{ $s->what === 'views' ? Str::plural('viewer', $s->count) : Str::plural('reader', $s->count) }}</span></td>
                                    <td class="num">{{ $s->usual > 0 ? number_format($s->usual, 1) : 'none before' }}
                                        @if ($s->times)<span class="u-sub">· {{ number_format($s->times) }}×</span>@endif</td>
                                    <td>
                                        @if ($s->what === 'views')
                                            {{ $s->accounts }} {{ Str::plural('account', $s->accounts) }} · {{ $s->guests }} {{ Str::plural('guest', $s->guests) }}
                                            @if ($s->addresses->isNotEmpty())
                                                <details class="sw-who">
                                                    <summary>The guests’ addresses ({{ $s->addresses->count() }})</summary>
                                                    <ul>
                                                        @foreach ($s->addresses as $ip => $n)
                                                            <li><span class="sw-ip">{{ $ip }}</span><span class="u-sub">{{ $n }} {{ Str::plural('guest', $n) }}</span></li>
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @endif
                                        @else
                                            {{ $s->accounts }} {{ Str::plural('account', $s->accounts) }} · {{ $s->files }} {{ Str::plural('file', $s->files) }}
                                        @endif
                                        @if ($s->people->isNotEmpty())
                                            <details class="sw-who">
                                                <summary>The accounts</summary>
                                                <ul>
                                                    @foreach ($s->people as $p)
                                                        <li><span>{{ $p->name }} <span class="u-sub">{{ $p->email }}</span></span>
                                                            @include('admin.partials.statistics-watch-action', ['p' => $p])</li>
                                                    @endforeach
                                                </ul>
                                                @php($open = $s->people->whereNull('status'))
                                                @if ($open->count() > 1)
                                                    <button type="button" class="btn btn-outline-danger btn-xs mt-1" data-take-out="{{ $open->pluck('id')->toJson() }}"
                                                            data-name="the {{ $open->count() }} accounts behind this spike">Take all {{ $open->count() }} out</button>
                                                @endif
                                            </details>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Unusual accounts --}}
    <div class="card mb-3" id="sw-accounts">
        <div class="card-body">
            <h6 class="card-title mb-2">Unusual accounts</h6>
            <p class="sw-note">Patrons who, in the day, viewed {{ Watch::ACCOUNT_VIEWS }} materials or more, downloaded {{ Watch::ACCOUNT_DOWNLOADS }}
                files or more, or gave {{ Watch::ACCOUNT_RATINGS }} ratings or more. The number over the line is in red.</p>
            @if ($accounts->isEmpty())
                <p class="sw-empty">No account did anything unusual.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 sw-table">
                        <thead>
                            <tr><th>Account</th><th class="num">Viewed</th><th class="num">Downloads</th><th class="num">Ratings</th><th>Status</th><th class="text-center">Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($accounts as $p)
                                <tr>
                                    <td>@include('admin.partials.statistics-watch-person', ['p' => $p])</td>
                                    <td class="num"><span @class(['sw-over' => $p->views >= Watch::ACCOUNT_VIEWS])>{{ number_format($p->views) }}</span></td>
                                    <td class="num"><span @class(['sw-over' => $p->downloads >= Watch::ACCOUNT_DOWNLOADS])>{{ number_format($p->downloads) }}</span>
                                        @if ($p->downloads)<span class="u-sub">· {{ $p->materials }} {{ Str::plural('material', $p->materials) }}</span>@endif</td>
                                    <td class="num"><span @class(['sw-over' => $p->ratings >= Watch::ACCOUNT_RATINGS])>{{ number_format($p->ratings) }}</span></td>
                                    <td>@if ($p->status)<span @class(['badge sw-pill', 'is-warn' => $p->status !== 'out'])>{{ $statusLabel[$p->status] }}</span>@else — @endif</td>
                                    <td class="text-center">@include('admin.partials.statistics-watch-action', ['p' => $p])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Unusual guests --}}
    <div class="card mb-3" id="sw-guests">
        <div class="card-body">
            <h6 class="card-title mb-2">Unusual guests</h6>
            <p class="sw-note">Visitors not signed in who viewed {{ Watch::GUEST_VIEWS }} materials or more in the day. A guest is one browser
                at one address, known here by a code. “At the cap”: it reached {{ viewCapPerHour() }} counted views in an hour, after which its
                views stopped counting for that hour. {{ $addresses ? 'Addresses' : 'Their addresses' }} show to developer and super admin accounts
                only, and are deleted 90 days after the guest was first seen. A guest can’t be taken out of the rankings; an address that
                keeps at it can be blocked at the web server or the firewall.</p>
            @if ($guests->isEmpty())
                <p class="sw-empty">No guest did anything unusual.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 sw-table">
                        <thead>
                            <tr><th>Guest</th><th class="num">Viewed</th><th>Between</th>@if ($addresses)<th>Address</th>@endif</tr>
                        </thead>
                        <tbody>
                            @foreach ($guests as $g)
                                <tr>
                                    <td><span class="u-name sw-ip">Guest {{ $g->code }}</span>
                                        @if ($g->capped)<span class="badge sw-pill is-warn">At the cap</span>@endif</td>
                                    <td class="num"><span class="sw-over">{{ number_format($g->views) }}</span> <span class="u-sub">materials</span></td>
                                    <td class="text-nowrap">{{ $g->first->format('g:i A') }}–{{ $g->last->format('g:i A') }}</td>
                                    @if ($addresses)<td class="sw-ip">{{ $g->address ?? 'deleted' }}</td>@endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>

    {{-- Sign-ups --}}
    <div class="card mb-3" id="sw-signups">
        <div class="card-body">
            <h6 class="card-title mb-2">Sign-ups</h6>
            <p class="sw-note">{{ $signUps->total }} {{ Str::plural('patron', $signUps->total) }} signed up {{ $isToday ? 'today' : 'that day' }}; a usual day has
                {{ number_format($signUps->usualDay, 1) }}. A burst is an hour with {{ Watch::BURST_AT_LEAST }} sign-ups or more, and
                {{ Watch::BURST_TIMES }}× the usual hour. The numbers after each account are everything it has done since.</p>
            @forelse ($signUps->bursts as $b)
                <div class="sw-burst">
                    <div class="sw-burst-head">
                        <span><strong>{{ $b->from->format('g A') }}–{{ $b->from->copy()->addHour()->format('g A') }}</strong>:
                            {{ $b->count }} sign-ups (a usual hour: {{ number_format($signUps->usualHour, 2) }})</span>
                        @php($open = $b->people->whereNull('status'))
                        @if ($open->isNotEmpty())
                            <button type="button" class="btn btn-outline-danger btn-sm" data-take-out="{{ $open->pluck('id')->toJson() }}"
                                    data-name="the {{ $open->count() }} {{ Str::plural('account', $open->count()) }} made from {{ $b->from->format('g A') }} to {{ $b->from->copy()->addHour()->format('g A') }}">Take all {{ $open->count() }} out</button>
                        @endif
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0 sw-table">
                            <thead>
                                <tr><th>Account</th><th>Made</th><th class="num">Views</th><th class="num">Downloads</th><th class="num">Ratings</th><th>Status</th><th class="text-center">Action</th></tr>
                            </thead>
                            <tbody>
                                @foreach ($b->people as $p)
                                    <tr>
                                        <td>@include('admin.partials.statistics-watch-person', ['p' => $p, 'inBurst' => true])</td>
                                        <td class="text-nowrap">{{ $p->made?->format('g:i A') }}</td>
                                        <td class="num">{{ number_format($p->views) }}</td>
                                        <td class="num">{{ number_format($p->downloads) }}</td>
                                        <td class="num">{{ number_format($p->ratings) }}</td>
                                        <td>@if ($p->status)<span @class(['badge sw-pill', 'is-warn' => $p->status !== 'out'])>{{ $statusLabel[$p->status] }}</span>@else — @endif</td>
                                        <td class="text-center">@include('admin.partials.statistics-watch-action', ['p' => $p])</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @empty
                <p class="sw-empty">No hour had an unusual number of sign-ups.</p>
            @endforelse
        </div>
    </div>

    {{-- Out of the rankings now (whatever the day) --}}
    <div class="card" id="sw-out">
        <div class="card-body">
            <h6 class="card-title mb-2">Out of the rankings now</h6>
            <p class="sw-note">Their views, downloads and ratings don’t count in the top-10 lists until you put them back. Banned accounts are out too, while banned.</p>
            @if ($excluded->isEmpty())
                <p class="sw-empty">No account is out of the rankings.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 sw-table">
                        <thead>
                            <tr><th>Account</th><th>Since</th><th>Why</th><th class="text-center">Action</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($excluded as $x)
                                <tr>
                                    <td><div class="u-name">{{ $x->name }}</div><div class="u-sub">{{ $x->email }}</div></td>
                                    <td class="text-nowrap" title="{{ $x->since->format('M j, Y g:i A') }}">{{ $x->since->format('M j, Y') }}<div class="u-sub">by {{ $x->by ?? 'a removed account' }}</div></td>
                                    <td class="sw-why">{{ $x->reason ?: '—' }}</td>
                                    <td class="text-center">@include('admin.partials.statistics-watch-action', ['p' => (object) ['id' => $x->user_id, 'name' => $x->name, 'status' => 'out']])</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>

{{-- Taking out: one account, or a burst's or a spike's. The reason is optional and shows in "Out of the rankings now". --}}
<div class="modal fade" id="takeOutModal" tabindex="-1" aria-labelledby="takeOutTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.statistics-watch.exclude') }}">
                @csrf
                <div data-take-out-ids></div>
                <div class="modal-header">
                    <h6 class="modal-title" id="takeOutTitle">Take out of the rankings</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" data-take-out-text></p>
                    <label for="take-out-reason" class="form-label">Why <span class="text-muted">(optional)</span></label>
                    <input id="take-out-reason" name="reason" type="text" maxlength="255" class="form-control"
                           placeholder="For example: 40 downloads in an hour">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Take out</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
$(function () {
    if (window.feather) feather.replace();

    var $modal = $('#takeOutModal'), form = $modal.find('form')[0], ids = $modal.find('[data-take-out-ids]')[0];

    $(document).on('click', '[data-take-out]', function () {
        var users = JSON.parse(this.dataset.takeOut);
        ids.replaceChildren.apply(ids, users.map(function (id) {
            var input = document.createElement('input');
            input.type = 'hidden'; input.name = 'users[]'; input.value = id;
            return input;
        }));
        $modal.find('[data-take-out-text]').text('Take ' + this.dataset.name + ' out of the top-10 lists? '
            + 'Their views, downloads and ratings stop counting until you put them back. '
            + 'They can still sign in and use the library.');
        $modal.modal('show');
    });
    $modal.on('shown.bs.modal', function () { form.elements.reason.focus(); });
    $modal.on('hidden.bs.modal', function () { form.elements.reason.value = ''; });
});
</script>
@endsection
