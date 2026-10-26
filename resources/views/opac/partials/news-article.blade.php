{{-- NEWS section "Read more" (OPAC home): one featured article in the section's dialog. Rendered by
     HomeController::news. The body is cleaned again here, not only when saved: rows saved before
     cleanNewsHtml existed are shown safely too. --}}
@php
    $cover = newsPrintCoverUrl($news);
    $link = preg_match('#^https?://#i', (string) $news->redirect_url) ? $news->redirect_url : null;
@endphp
<div class="np-d-head">
    <span class="np-d-kicker">News &middot; {{ $news->created_at?->format('l, j F Y') }}</span>
    <button type="button" class="np-d-close" data-np-close aria-label="Close">
        <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>
    </button>
</div>
<h2 id="np-dialog-title" class="np-d-title">{{ $news->news_title }}</h2>
@if($cover)
    <figure class="np-photo np-d-photo">
        <div class="np-plate"><img src="{{ $cover }}" alt=""></div>
    </figure>
@endif
<div class="np-d-body">{!! cleanNewsHtml($news->news_body) !!}</div>
@if($link)
    <a class="np-d-link" href="{{ $link }}" target="_blank" rel="noopener noreferrer">
        Read the original story <span aria-hidden="true">&#8599;</span><span class="visually-hidden"> (opens in a new tab)</span>
    </a>
@endif
