{{-- The OPAC breadcrumb, the same on every page that has one (search results, record page).
     $crumbs:  [[label, url], ..., [label, null]] — the last item is the current page. An item with
               'back' => true returns to the previous page when that was the search results, so the
               patron gets their search, filters and scroll position back.
     $variant: 'header' — desktop, inside the site header before the bell (fill the layout's
                          @section('header_breadcrumb') with it; styles live in the layout);
               'strip'  — tablets and phones, a slim strip under the header (the header's menu
                          becomes the slide-out menu there, so the breadcrumb can't sit in it). --}}
@php $variant = $variant ?? 'header'; @endphp
@if($variant === 'strip')
<style>
    .opac-crumbbar { display: none; background: #fff; border-bottom: 1px solid #e1e7ea; font-size: 13px; color: #5d6973; }
    .opac-crumbbar .container { padding-top: 8px; padding-bottom: 8px; }
    .opac-crumbbar a { color: #00756b; font-weight: 500; text-decoration: none; }
    .opac-crumbbar a:hover { text-decoration: underline; color: #005f57; }
    .opac-crumbbar [aria-hidden] { margin: 0 6px; }
    @media (max-width: 991.98px) { .opac-crumbbar { display: block; } }
</style>
<nav class="opac-crumbbar" aria-label="Breadcrumb"><div class="container">
@endif
@if($variant !== 'strip')<nav aria-label="Breadcrumb">@endif
@foreach($crumbs as $i => $crumb)
    @if($i)<span aria-hidden="true">›</span>@endif
    @if($crumb[1] ?? null)
        <a href="{{ $crumb[1] }}" @if(!empty($crumb['back'])) data-crumb-back @endif>{{ $crumb[0] }}</a>
    @else
        <span aria-current="page">{{ $crumb[0] }}</span>
    @endif
@endforeach
@if($variant === 'strip')</div></nav>@else</nav>@endif
