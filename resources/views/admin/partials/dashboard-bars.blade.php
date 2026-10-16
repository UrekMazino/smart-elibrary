{{-- Horizontal bar list for the dashboard's breakdown cards. $rows: [['name', 'total', 'w' (bar % of the largest)]].
     The first six show; the rest fold behind "Show n more". --}}
@php
  $row = fn ($r) => '<li><span class="dsh-bar-name" title="' . e($r['name']) . '">' . e($r['name']) . '</span>'
    . '<span class="dsh-bar-val">' . number_format($r['total']) . '</span>'
    . '<span class="dsh-bar-track" aria-hidden="true"><span style="width:' . $r['w'] . '%"></span></span></li>';
@endphp
@if($rows->isEmpty())
  <p class="dsh-empty">{{ $empty }}</p>
@else
  <ul class="dsh-bars" aria-label="{{ ucfirst($unit) }} by group">{!! $rows->take(6)->map($row)->implode('') !!}</ul>
  @if($rows->count() > 6)
    <details class="dsh-more">
      <summary>Show {{ $rows->count() - 6 }} more</summary>
      <ul class="dsh-bars">{!! $rows->slice(6)->map($row)->implode('') !!}</ul>
    </details>
  @endif
@endif
