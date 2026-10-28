{{-- An account on the statistics watch (StatisticsWatch::people()): name, address, and how and when it was made.
     $inBurst: a sign-up burst's table, which has its own Made column and where every account is new. --}}
<div class="u-name">{{ $p->name }}
    @if ($p->google)<span class="badge sw-pill" title="Made or linked with Continue with Google">Google</span>@endif
    @if ($p->fresh && empty($inBurst))<span class="badge sw-pill is-warn" title="Made {{ $p->made->format('M j, Y g:i A') }}">New</span>@endif
</div>
<div class="u-sub">{{ $p->email }}@if ($p->made && empty($inBurst)) · made {{ $p->made->format('M j, Y') }}@endif</div>
