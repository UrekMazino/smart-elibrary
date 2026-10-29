@extends("admin.layouts.master")
{{-- Administration → Accounts → Security Log (SecurityLogController, App\Services\SecurityLog): read-only; developer and
     super admin. Times show in the app's timezone; hover one for the UTC time the log keeps. --}}
@use('App\Services\SecurityLog')
@php
    $tone = fn ($event) => match (true) {
        in_array($event, ['sign-in.failed', 'sign-in.locked', 'sign-in.refused', 'access.denied', 'account.banned', 'account.blocked', 'account.deleted'], true) => 'warn',
        str_starts_with($event, 'role.') || str_starts_with($event, 'permission.') => 'role',
        default => '',
    };
    $query = fn (array $more) => route('admin.security-log', array_filter(['from' => $from->toDateString(), 'to' => $to->toDateString(),
        'type' => $group, 'q' => $search] + $more, fn ($v) => $v !== '' && $v !== null));
@endphp

@section('css')
    <style>
      .sl-filters { display: flex; flex-wrap: wrap; align-items: flex-end; gap: 8px 10px; margin-bottom: 12px; }
      .sl-filters label { display: block; font-size: 0.6875rem; color: var(--ui-muted, #7987a1); margin-bottom: 2px; }
      .sl-note { color: var(--ui-muted, #7987a1); font-size: 0.75rem; margin: 0 0 10px; }
      .sl-table { font-size: 0.8125rem; }
      .sl-table thead th { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1);
                           white-space: nowrap; padding: 9px 10px; border-bottom: 2px solid var(--ui-border, #eef0f2); }
      .sl-table tbody td { padding: 7px 10px; vertical-align: top; white-space: normal; }   /* the template's tables don't wrap */
      .sl-table td:first-child { white-space: nowrap; font-variant-numeric: tabular-nums; }
      .sl-table td:nth-child(2) { min-width: 180px; }   /* on a phone the table scrolls rather than squeezing words */
      .sl-table td:nth-child(3), .sl-table td:nth-child(5) { min-width: 190px; }
      .sl-ev { font-weight: 600; color: var(--ui-text, #22283b); }
      .sl-ev.is-warn { color: var(--ui-danger-text, #b42318); }
      .sl-ev.is-role { color: var(--ui-navy, #33509c); }
      .sl-sub { display: block; color: var(--ui-muted, #7987a1); font-size: 0.71875rem; overflow-wrap: anywhere; }
      .sl-ip { font-family: var(--bs-font-monospace, monospace); font-size: 0.75rem; }
      .sl-pager { display: flex; flex-wrap: wrap; align-items: center; justify-content: space-between; gap: 8px; margin-top: 12px; font-size: 0.78125rem; color: var(--ui-muted, #7987a1); }
      .sl-empty { color: var(--ui-muted, #7987a1); font-size: 0.8125rem; padding: 18px 0 6px; margin: 0; }
    </style>
@endsection

@section('content')
<div class="page-content">

    @include('admin.partials.breadcrumb', ['page' => 'security-log'])

    <div class="card">
        <div class="card-body">
            <h6 class="card-title mb-1">SECURITY LOG</h6>
            <p class="sl-note">
                Sign-ins, wrong passwords, lockouts, refused pages, and changes to roles, permissions, bans and passwords. The log keeps a
                year in one file; each night the entries older than that leave it. Nothing here can change it. Times are Philippine time
                (hover one for UTC, as the log keeps it). Developer and super admin accounts only.
            </p>

            <form method="GET" action="{{ route('admin.security-log') }}" class="sl-filters" role="search">
                <div><label for="sl-from">From</label>
                    <input id="sl-from" type="date" name="from" value="{{ $from->toDateString() }}" max="{{ $today->toDateString() }}" class="form-control form-control-sm"></div>
                <div><label for="sl-to">To</label>
                    <input id="sl-to" type="date" name="to" value="{{ $to->toDateString() }}" max="{{ $today->toDateString() }}" class="form-control form-control-sm"></div>
                <div><label for="sl-type">Kind</label>
                    <select id="sl-type" name="type" class="form-select form-select-sm">
                        <option value="">Everything</option>
                        @foreach (array_keys(SecurityLog::EVENTS) as $name)
                            <option value="{{ $name }}" @selected($group === $name)>{{ $name }}</option>
                        @endforeach
                    </select></div>
                <div><label for="sl-q">Search</label>
                    <input id="sl-q" type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" style="min-width: 220px;"
                           placeholder="Email, IP address or page"></div>
                <button type="submit" class="btn btn-primary btn-sm">Show</button>
            </form>

            @if ($clamped)
                <p class="sl-note">A view covers {{ $maxDays }} days at most: showing {{ $from->format('M j') }} to {{ $to->format('M j, Y') }}.</p>
            @endif
            @if ($more)
                <p class="sl-note">There are more entries in this range than one view reads: pick fewer days.</p>
            @endif

            @if ($entries->isEmpty())
                <p class="sl-empty">Nothing {{ $group !== '' || $search !== '' ? 'matching ' : '' }}from {{ $from->format('M j') }} to {{ $to->format('M j, Y') }}.</p>
            @else
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0 sl-table">
                        <thead>
                            <tr><th>When</th><th>What</th><th>Account</th><th>By</th><th>From</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($entries as $e)
                                @php
                                    $d = $e['data'];
                                    $account = isset($d['user']) ? $people->get($d['user']) : null;
                                    $by = isset($d['by']) ? $people->get($d['by']) : null;
                                    $detail = SecurityLog::describe($e['event'], $d);
                                @endphp
                                <tr>
                                    <td title="{{ $e['at']->format('Y-m-d H:i:s') }} UTC">{{ $e['at']->copy()->setTimezone($tz)->format('M j, g:i:s A') }}</td>
                                    <td><span @class(['sl-ev', 'is-' . $tone($e['event']) => $tone($e['event']) !== ''])>{{ SecurityLog::label($e['event']) }}</span>
                                        @if ($detail !== '')<span class="sl-sub">{{ $detail }}</span>@endif</td>
                                    <td>
                                        @if ($account)
                                            {{ $account->name }}@if ($account->deleted_at) <span class="sl-sub d-inline">(deleted)</span>@endif
                                            <span class="sl-sub">{{ $d['email'] ?? $account->email }}</span>
                                        @elseif (isset($d['email']))
                                            <span class="sl-sub">{{ $d['email'] }}</span>
                                        @else
                                            <span class="text-muted">—</span>
                                        @endif
                                    </td>
                                    <td>{{ $by?->name ?? (isset($d['by']) ? '#' . $d['by'] : '') }}</td>
                                    <td><span class="sl-ip">{{ $d['ip'] ?? '' }}</span>
                                        @if (isset($d['agent']))<span class="sl-sub" title="{{ $d['agent'] }}">{{ Str::limit($d['agent'], 48) }}</span>@endif</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="sl-pager">
                    <span>{{ number_format($total) }} {{ Str::plural('entry', $total) }}, newest first · page {{ $page }} of {{ $pages }}</span>
                    <span class="d-flex gap-2">
                        @if ($page > 1)<a class="btn btn-outline-secondary btn-sm" href="{{ $query(['page' => $page - 1]) }}">Newer</a>@endif
                        @if ($page < $pages)<a class="btn btn-outline-secondary btn-sm" href="{{ $query(['page' => $page + 1]) }}">Older</a>@endif
                    </span>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
