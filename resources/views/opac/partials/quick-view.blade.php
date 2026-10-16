{{-- Quick view (search results page): a record's main details in the page's dialog, without leaving the
     results. Rendered by ResultController::quickView. The buttons use the results page's own handlers
     (data-read / data-download / data-cite / data-save). --}}
<div class="sr-dialog-h sr-qv-h">
    <img class="sr-qv-cover" src="{{ getThumbnailLink($r->catalog_id) }}" alt="">
    <div class="sr-qv-head">
        <h2 id="sr-dialog-title">{{ $r->title_statement }}</h2>
        <div class="sr-badges">
            @if($r->material_type)<span class="sr-badge">{{ $r->material_type }}</span>@endif
            @if($read)<span class="sr-badge is-ft">Full text</span>@endif
            @if($available)<span class="sr-badge is-avail">Available to borrow</span>@endif
            @if(is_numeric($rating) && $rating > 0)<span class="rating sr-stars" title="Rated {{ $rating }} of 5"><i data-star="{{ $rating }}"></i></span>@endif
        </div>
    </div>
    <button type="button" class="sr-close" data-dialog-close aria-label="Close">
        <svg width="14" height="14" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="1.8" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>
    </button>
</div>
<dl class="sr-qv-dl">
    <dt>{{ count($authors) === 1 ? 'Author' : 'Authors' }}</dt>
    <dd>{{ $authors ? implode(' / ', $authors) : 'Not recorded' }}</dd>
    @if($r->edition)<dt>Edition</dt><dd>{{ $r->edition }}</dd>@endif
    @if($published)<dt>Published</dt><dd>{{ $published }}</dd>@endif
    @if($physical)<dt>Physical description</dt><dd>{{ $physical }}</dd>@endif
    @if($r->series_description)<dt>Series</dt><dd>{{ $r->series_description }}</dd>@endif
    @if($r->call_number)<dt>Call number</dt><dd class="sr-qv-mono">{{ $r->call_number }}</dd>@endif
    @if($r->isbn)<dt>ISBN</dt><dd>{{ $r->isbn }}</dd>@endif
    @if($r->issn)<dt>ISSN</dt><dd>{{ $r->issn }}</dd>@endif
    @if($subjects)
        <dt>Subjects</dt>
        <dd class="sr-qv-tags">@foreach($subjects as $subject)<span class="sr-tag">{{ $subject }}</span>@endforeach</dd>
    @endif
    @if($summary)<dt>Summary</dt><dd class="sr-qv-sum">{{ $summary }}</dd>@endif
</dl>
<div class="sr-qv-acts">
    <a class="sr-act sr-act-main" href="{{ $url }}">View full record</a>
    @if($read)
        <button type="button" class="sr-act" data-read="{{ $read }}" data-id="{{ $r->catalog_id }}" data-title="{{ $r->title_statement }}">Read</button>
        <button type="button" class="sr-act" data-title="{{ $r->title_statement }}" data-download="{{ $r->catalog_id }}" title="Download the PDF (sign-in required)">Download</button>
    @endif
    <button type="button" class="sr-act" data-cite="{{ $r->catalog_id }}">Cite</button>
    @auth
        <button type="button" class="sr-act" data-save="{{ $r->catalog_id }}" aria-pressed="{{ $saved ? 'true' : 'false' }}">{{ $saved ? 'Saved' : 'Save' }}</button>
    @else
        <a class="sr-act" href="{{ route('login') }}" data-login-prompt="save" title="Log in to save records">Save</a>
    @endauth
</div>
