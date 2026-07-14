{{-- Ranked "Top 10" carousel. Reuses the .cc-* carousel (prev/next arrows, no scrollbar);
     adds a rank number overlay and a hover badge with the total/rating detail.
     Expects: $title (string), $items (array of ['url','thumb','title','detail']). --}}
@if(!empty($items))
<div class="cover-carousel ranked">
	<div class="main_title"><h2>{{ $title }}</h2></div>
	<div class="cc-strip">
		<button type="button" class="cc-arrow prev" aria-label="Previous">&#8249;</button>
		<div class="cc-track">
			@foreach($items as $idx => $it)
				<div class="cc-item rk-item">
					<a class="cc-cover rk-cover" href="{{ $it['url'] }}">
						<span class="rk-num">{{ str_pad($idx + 1, 2, '0', STR_PAD_LEFT) }}</span>
						<img loading="lazy" src="{{ $it['thumb'] }}" alt="{{ $it['title'] }}">
						<span class="rk-badge">{{ $it['detail'] }}</span>
					</a>
					<a class="cc-cap" href="{{ $it['url'] }}" title="{{ $it['title'] }}">{{ $it['title'] }}</a>
				</div>
			@endforeach
		</div>
		<button type="button" class="cc-arrow next" aria-label="Next">&#8250;</button>
	</div>
</div>
@endif
