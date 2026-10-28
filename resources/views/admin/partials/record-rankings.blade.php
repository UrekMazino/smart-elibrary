{{-- Reviews tab of the admin record view (admin.catalog-view-record), above the reviews: where this material stands in
     the home page's three top-10 lists, for the last rankingDays() days and all time, with the numbers behind each rank.
     $rankings: App\Services\Rankings::standings() (the same queries as Utilities → Rankings, so the ranks agree);
     $published: only published materials are ranked. --}}
@use('App\Services\Rankings')
@php
  $rrLists = ['downloaded' => 'Most Downloaded', 'viewed' => 'Most Viewed', 'rated' => 'Highest Rating'];
  $rrNone = ['downloaded' => 'No downloads', 'viewed' => 'No views', 'rated' => 'No ratings'];
  $rrLink = auth()->user()->can('access.utilities') && auth()->user()->can('access.rankings') ? route('utilities.rankings') : null;
@endphp
<style>
  .rr { margin: 0 0 18px; padding: 14px 16px; border: 1px solid var(--ui-border, #eceff4); border-radius: 10px; background: var(--ui-surface, #fff); }
  .rr-head { display: flex; flex-wrap: wrap; align-items: baseline; justify-content: space-between; gap: 8px; margin-bottom: 10px; }
  .rr-title { margin: 0; font-size: 0.875rem; font-weight: 600; color: var(--ui-text, #1f2433); }
  .rr-link { font-size: 0.78125rem; font-weight: 500; }
  .rr-table { width: 100%; border-collapse: collapse; font-size: 0.8125rem; }
  .rr-table th, .rr-table td { padding: 9px 10px; border-top: 1px solid var(--ui-border, #eceff4); vertical-align: top; text-align: left; }
  .rr-table thead th { border-top: 0; padding-top: 0; font-size: 0.71875rem; font-weight: 600; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #8a94a6); }
  .rr-table tbody th { width: 26%; font-weight: 600; color: var(--ui-text-2, #4a5a78); }
  .rr-pos { display: inline-block; min-width: 34px; padding: 1px 8px; border-radius: 999px; background: var(--ui-surface-2, #eef1f6);
            font-weight: 700; color: var(--ui-text, #1f2433); text-align: center; font-variant-numeric: tabular-nums; }
  .rr-pos.is-1 { background: #f7e7a6; color: #5c4400; }
  .rr-pos.is-2 { background: #e3e7ee; color: #3a4556; }
  .rr-pos.is-3 { background: #f1d3bb; color: #6b3410; }
  .rr-top { margin-left: 6px; font-size: 0.71875rem; font-weight: 600; color: var(--t-primary, #0a7a6e); }
  .rr-num { display: block; margin-top: 3px; font-size: 0.75rem; color: var(--ui-muted, #8a94a6); }
  .rr-none { color: var(--ui-muted, #8a94a6); }
  .rr-note, .rr-foot { margin: 8px 0 0; font-size: 0.75rem; color: var(--ui-muted, #8a94a6); }
  @media (max-width: 575px) { .rr-table tbody th { width: auto; } }
</style>
<section class="rr" aria-labelledby="rr-h">
  <div class="rr-head">
    <h6 class="rr-title" id="rr-h">Rankings</h6>
    @if($rrLink)<a class="rr-link" href="{{ $rrLink }}">All rankings</a>@endif
  </div>
  @if(!$published)
    <p class="rr-note">Not ranked: only published materials are ranked.</p>
  @else
    <table class="rr-table">
      <thead>
        <tr><th scope="col">List</th><th scope="col">{{ ucfirst(rankingWindowLabel()) }}</th><th scope="col">All time</th></tr>
      </thead>
      <tbody>
        @foreach($rrLists as $key => $label)
          <tr>
            <th scope="row">{{ $label }}</th>
            @foreach(['recent', 'all'] as $w)
              @php $st = $rankings[$w][$key] ?? null; @endphp
              <td>
                @if($st)
                  <span @class(['rr-pos', 'is-' . $st->pos => $st->pos <= 3])>#{{ $st->pos }}</span>
                  @if($st->pos <= Rankings::TOP)<span class="rr-top">{{ $w === 'recent' ? 'Top ' . Rankings::TOP . ' · medal' : 'Top ' . Rankings::TOP }}</span>@endif
                  <span class="rr-num">
                    @switch($key)
                      @case('downloaded')
                        {{ number_format($st->total) }} {{ Str::plural('reader', (int) $st->total) }} · {{ number_format($st->files) }} {{ Str::plural('file', (int) $st->files) }}
                        @break
                      @case('viewed')
                        {{ number_format($st->total) }} viewer-{{ Str::plural('day', (int) $st->total) }} · {{ number_format($st->guests) }} by {{ Str::plural('guest', (int) $st->guests) }}
                        @break
                      @default
                        Score {{ number_format($st->score, 2) }} · ★ {{ number_format($st->rating, 1) }} from {{ $st->votes }} {{ Str::plural('rating', (int) $st->votes) }}
                    @endswitch
                  </span>
                @else
                  <span class="rr-none">Not ranked</span>
                  <span class="rr-num">{{ $rrNone[$key] }} {{ $w === 'recent' ? 'in the ' . rankingWindowLabel() : 'yet' }}</span>
                @endif
              </td>
            @endforeach
          </tr>
        @endforeach
      </tbody>
    </table>
    <p class="rr-foot">Counted as on Utilities → Rankings: downloads by reader, views once a viewer a day, rating by weighted score; no
      staff, banned or taken-out accounts. Ranks 1–{{ Rankings::TOP }} over the {{ rankingWindowLabel() }} are on the home page and earn the medal on its cover.</p>
  @endif
</section>
