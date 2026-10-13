{{-- Ranked "Top 10" carousel. Reuses the .cc-* carousel (prev/next arrows, no scrollbar);
     adds a rank number overlay and a hover badge with the total/rating detail.
     Expects: $title (string), $items (array of ['catalog_id','url','thumb','title','detail']).
     Each cover has a save star (opac.partials.save-star). --}}
@if(!empty($items))
<div class="cover-carousel ranked">
	<div class="main_title"><h2>{{ $title }}</h2></div>
	<div class="cc-strip">
		<button type="button" class="cc-arrow prev" aria-label="Previous">&#8249;</button>
		<div class="cc-track">
			@foreach($items as $idx => $it)
				<div class="cc-item rk-item">
					<div class="cc-cover rk-cover">
						<span class="rk-num">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span>
						<span class="cv-fig">
							<a class="cv-link" href="{{ $it['url'] }}">
								<img loading="lazy" src="{{ $it['thumb'] }}" alt="{{ $it['title'] }}">
								<span class="rk-badge">{{ $it['detail'] }}</span>
							</a>
							@include('opac.partials.save-star', ['id' => $it['catalog_id'], 'title' => $it['title']])
						</span>
					</div>
					<a class="cc-cap" href="{{ $it['url'] }}" title="{{ $it['title'] }}">{{ $it['title'] }}</a>
				</div>
			@endforeach
		</div>
		<button type="button" class="cc-arrow next" aria-label="Next">&#8250;</button>
	</div>
</div>
@endif
