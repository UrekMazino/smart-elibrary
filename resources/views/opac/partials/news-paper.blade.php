{{-- NEWS (OPAC home): the featured news as the front of a newspaper, laid on a collage of scraps from
     other newspaper pages. The latest story leads on the left; the others are listed on the right,
     newest first, in a column that scrolls on its own. "Read more" opens the article in a dialog
     (HomeController::news), with its external link opening in a new tab.

     Built to stay light: the newsprint texture and every scrap of the collage are CSS (no images);
     headlines and text use the serif fonts every device has; only the masthead loads a web font, the
     letters N-E-W-S alone (~5 KB), and only when the section comes near. Photos are the ~800 px WebP
     copies made at upload (makeNewsPrintCopy), in their own colours, loaded lazily; the list uses the
     250 px thumbnails. The lead photo drifts slowly while the section is on screen, and not at all
     for "reduce motion". --}}
@if($items->isNotEmpty())
<style>
	#sec-news { position: relative; overflow: hidden; padding: 56px 16px 64px; background: #3b3a36; }
	.np-sheet, .np-dialog {
		--ink: #1d1d1b; --ink-2: #4d4b46; --paper: #efede6;
		/* Newsprint: fine speckle and long fibres. */
		--newsprint: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='220' height='220'%3E%3Cfilter id='s'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.85' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .2 0 0 0 0 .2 0 0 0 0 .19 0 0 0 .55 -.17'/%3E%3C/filter%3E%3Cfilter id='f'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.02 .3' numOctaves='2' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .25 0 0 0 0 .25 0 0 0 0 .24 0 0 0 .22 -.08'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23s)'/%3E%3Crect width='100%25' height='100%25' filter='url(%23f)'/%3E%3C/svg%3E");
		color: var(--ink); font-family: Georgia, "Times New Roman", serif;
		background-color: var(--paper);
		background-image: var(--newsprint);
		box-shadow: 0 18px 40px rgba(0,0,0,.5), inset 0 0 40px rgba(0,0,0,.07);
		border-radius: 2px;
	}
	.np-sheet { position: relative; z-index: 1; max-width: 1240px; margin: 0 auto; padding: 16px clamp(14px, 3vw, 36px) 24px;
		/* the centre fold, and a little light falling across the page */
		background-image: var(--newsprint),
			linear-gradient(to bottom, transparent calc(50% - 2px), rgba(0,0,0,.08) 50%, rgba(255,255,255,.7) calc(50% + 1px), transparent calc(50% + 4px)),
			linear-gradient(170deg, rgba(255,255,255,.4), transparent 45%, rgba(0,0,0,.04)); }

	/* The collage behind the paper: scraps of other pages (masthead, headlines, weather, crossword,
	   classifieds, markets, comics, photos), all drawn in CSS. Decoration only (aria-hidden).
	   A pile, not placed scraps: a grid of overlapping scraps filled from the centre out, each tilted
	   and nudged a little, so it covers the background at any width. On very wide screens (or zoomed
	   out) the whole pile is drawn larger (--z) rather than with ever more scraps. Slightly out of
	   focus, so it stays behind the page yet the scraps can still be made out; it reaches past the
	   section's edges so the blur doesn't fade at them. */
	.nc { --z: 1; position: absolute; inset: -40px; z-index: 0; overflow: hidden; pointer-events: none; filter: blur(5px); }
	.nc::after { content: ""; position: absolute; inset: 0; background: radial-gradient(ellipse at 50% 50%, rgba(0,0,0,.12), rgba(0,0,0,.45)); }
	.nc-in { width: calc(100% / var(--z)); height: calc(100% / var(--z)); transform: scale(var(--z)); transform-origin: 0 0;
		display: grid; grid-template-columns: repeat(auto-fill, 230px); grid-auto-rows: 172px; justify-content: center; align-content: center; }
	@media (min-width: 2100px) { .nc { --z: 1.25; } }
	@media (min-width: 2700px) { .nc { --z: 1.6; } }
	@media (min-width: 3400px) { .nc { --z: 2; } }
	@media (min-width: 4300px) { .nc { --z: 2.5; } }
	@media (min-width: 5400px) { .nc { --z: 3; } }
	.nc-in > div { width: 290px; height: 220px; margin: -24px -30px; overflow: hidden; padding: 10px 12px 0; color: #222; background: #f2f0ea;
		box-shadow: 0 2px 7px rgba(0,0,0,.35); font: 700 26px/1 Georgia, "Times New Roman", serif; text-transform: uppercase; rotate: -1deg; }
	.nc-in > div:nth-child(7n+1) { rotate: -4deg; }  .nc-in > div:nth-child(7n+2) { rotate: 3deg; }  .nc-in > div:nth-child(7n+3) { rotate: -2deg; }
	.nc-in > div:nth-child(7n+4) { rotate: 5deg; }   .nc-in > div:nth-child(7n+5) { rotate: -6deg; } .nc-in > div:nth-child(7n+6) { rotate: 2deg; }
	.nc-in > div:nth-child(5n+2) { translate: 14px 10px; }  .nc-in > div:nth-child(5n+4) { translate: -12px -9px; }
	.nc-in > div:nth-child(3n) { background: #e9e5d8; }
	.nc-in > div:nth-child(4n+1) { background: #f6f5f1; }
	.nc .s { font-size: 15px; letter-spacing: .12em; }
	.nc .xl { font-size: 44px; }
	.nc .torn { box-shadow: none; clip-path: polygon(0 3%, 6% 0, 13% 2.5%, 21% .5%, 30% 3%, 38% 1%, 47% 3.5%, 55% .5%, 64% 2.5%, 72% 0, 81% 3%, 90% 1%, 100% 3%,
		100% 97%, 93% 100%, 85% 97.5%, 76% 99.5%, 67% 97%, 58% 99%, 49% 96.5%, 40% 99.5%, 31% 97%, 22% 99%, 13% 96.5%, 5% 99.5%, 0 97%); }
	.nc i { display: block; height: var(--h, 220px); margin-top: 8px; background: repeating-linear-gradient(#8f8d88 0 2px, transparent 2px 6px); }
	.nc i.c2 { background: repeating-linear-gradient(#8f8d88 0 2px, transparent 2px 6px) 0 0 / 47% 100% no-repeat,
		repeating-linear-gradient(#8f8d88 0 2px, transparent 2px 6px) 100% 0 / 47% 100% no-repeat; }
	.nc .m { font: 400 54px/1 "UnifrakturMaguntia", "Old English Text MT", Georgia, serif; text-transform: none; text-align: center; }
	.nc .k { display: block; margin-top: 5px; padding: 3px 0; border-block: 1px solid #222; font: 9px/1.2 Georgia, serif; letter-spacing: .2em; text-align: center; }
	.nc i.pic:first-child { margin-top: 0; }
	.nc i.pic { height: 118px; background: radial-gradient(circle, rgba(0,0,0,.5) 32%, transparent 36%) 0 0 / 5px 5px, linear-gradient(160deg, #d4d2cc, #77746d 55%, #34322e); }
	.nc i.cw { float: left; width: 150px; height: 150px; margin-right: 10px; border: 1px solid #222;
		background: linear-gradient(#222 0 0) 15px 0 / 15px 15px no-repeat, linear-gradient(#222 0 0) 75px 30px / 15px 15px no-repeat,
			linear-gradient(#222 0 0) 120px 15px / 15px 15px no-repeat, linear-gradient(#222 0 0) 45px 75px / 15px 15px no-repeat,
			linear-gradient(#222 0 0) 105px 90px / 15px 15px no-repeat, linear-gradient(#222 0 0) 15px 120px / 15px 15px no-repeat,
			linear-gradient(#222 0 0) 90px 135px / 15px 15px no-repeat, linear-gradient(#222 0 0) 60px 45px / 15px 15px no-repeat,
			linear-gradient(#222 1px, transparent 1px) 0 0 / 15px 15px, linear-gradient(90deg, #222 1px, transparent 1px) 0 0 / 15px 15px, #fbfaf7; }
	.nc i.sun { float: left; width: 44px; height: 44px; margin: 6px 10px 0 0; border-radius: 50%;
		background: radial-gradient(circle, #333 0 9px, transparent 10px 13px), repeating-conic-gradient(#333 0 8deg, transparent 8deg 30deg); }
	.nc i.cls { height: 120px; background: linear-gradient(90deg, #222 1px, transparent 1px) 0 0 / 50% 100%, linear-gradient(#222 1px, transparent 1px) 0 0 / 100% 40px,
		repeating-linear-gradient(transparent 0 8px, #9a9893 8px 9.5px, transparent 9.5px 13px); border: 1px solid #222; }
	.nc i.tbl { height: 112px; background: repeating-linear-gradient(transparent 0 5px, #8f8d88 5px 8px, transparent 8px 14px) 0 0 / 30% 100% no-repeat,
		repeating-linear-gradient(transparent 0 5px, #555 5px 8px, transparent 8px 14px) 60% 0 / 30% 100% no-repeat,
		repeating-linear-gradient(transparent 0 13px, #222 13px 14px); }
	.nc i.cmx { height: 86px; border: 2px solid #222;
		background: linear-gradient(90deg, transparent calc(33.3% - 1px), #222 0 calc(33.3% + 1px), transparent 0 calc(66.6% - 1px), #222 0 calc(66.6% + 1px), transparent 0),
			radial-gradient(18px 11px at 16% 26%, #fff 88%, #333 90% 100%, transparent 102%), radial-gradient(18px 11px at 50% 22%, #fff 88%, #333 90% 100%, transparent 102%),
			radial-gradient(18px 11px at 84% 28%, #fff 88%, #333 90% 100%, transparent 102%), radial-gradient(9px 9px at 12% 72%, #333 96%, transparent 100%),
			radial-gradient(9px 9px at 24% 74%, #555 96%, transparent 100%), radial-gradient(9px 9px at 47% 72%, #333 96%, transparent 100%),
			radial-gradient(9px 9px at 82% 70%, #555 96%, transparent 100%), #f7f6f2; }

	/* Masthead */
	.np-mast { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 16px; padding-bottom: 4px; border-bottom: 3px double var(--ink); }
	.np-title { margin: 0; font-family: "UnifrakturMaguntia", "Old English Text MT", Georgia, serif; font-weight: 400;
		font-size: clamp(58px, 9vw, 108px); line-height: 1; color: var(--ink); text-shadow: 0 1px 0 rgba(255,255,255,.35); }
	.np-ear { justify-self: start; max-width: 150px; margin: 0; padding: 6px 10px; border: 1px solid var(--ink);
		font-size: 10.5px; line-height: 1.4; letter-spacing: .14em; text-transform: uppercase; text-align: center; color: var(--ink-2); }
	.np-ear-r { justify-self: end; }
	.np-dateline { display: flex; justify-content: space-between; gap: 12px; padding: 5px 2px; border-bottom: 1px solid var(--ink);
		font-size: 11.5px; letter-spacing: .16em; text-transform: uppercase; }

	/* The front: the latest story large on the left; the others in a list on the right that scrolls on its own. */
	.np-front { display: grid; grid-template-columns: minmax(0, 1.6fr) minmax(0, 1fr); margin-top: 14px; }
	.np-front.is-single { grid-template-columns: 1fr; }
	.np-top { padding-right: 26px; border-right: 1px solid var(--ink); }
	.is-single .np-top { padding-right: 0; border-right: 0; }
	.np-kicker { margin: 0 0 4px; font-size: 12px; font-weight: 700; letter-spacing: .24em; text-transform: uppercase; text-align: center; color: var(--ink-2); }
	.np-headline { margin: 0 0 14px; font-family: Georgia, "Times New Roman", serif; font-weight: 700; font-size: clamp(24px, 3vw, 38px);
		line-height: 1.08; text-transform: uppercase; text-align: center; text-wrap: balance; color: var(--ink); }
	.np-headline::after { content: ""; display: block; width: 120px; margin: 10px auto 0; border-top: 3px double var(--ink); }
	.np-top .np-photo { margin-bottom: 16px; }
	.np-story { column-count: 2; column-gap: 22px; column-rule: 1px solid rgba(29,29,27,.35); }
	.np-lead { margin: 0; font-size: 15.5px; line-height: 1.55; text-align: justify; hyphens: auto; }
	.np-lead::first-letter { float: left; font-size: 3.6em; line-height: .82; padding: 5px 7px 0 0; font-weight: 700; }
	.np-more { display: inline-block; margin-top: 12px; padding: 6px 14px; border: 1px solid var(--ink); background: transparent; color: var(--ink);
		font: inherit; font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; cursor: pointer; }
	.np-more:hover { background: var(--ink); color: var(--paper); }
	.np-more:disabled { opacity: .6; cursor: progress; }

	/* More stories: as tall as the lead story (contain: size keeps the list from stretching the row), scrolling inside. */
	.np-list { display: flex; flex-direction: column; padding-left: 22px; }
	.np-list-h { margin: 0 0 2px; padding-bottom: 6px; border-bottom: 3px double var(--ink); font-family: Georgia, "Times New Roman", serif;
		font-size: 12px; font-weight: 700; letter-spacing: .24em; text-transform: uppercase; text-align: center; color: var(--ink); }
	.np-scroll { flex: 1; min-height: 340px; contain: size; overflow-y: auto; overscroll-behavior: contain; padding-right: 10px;
		scrollbar-width: thin; scrollbar-color: var(--ink-2) transparent;
		/* the last lines fade into the paper: there is more below */
		-webkit-mask-image: linear-gradient(#000 calc(100% - 30px), transparent); mask-image: linear-gradient(#000 calc(100% - 30px), transparent); }
	.np-scroll:focus-visible { outline: 2px dashed var(--ink-2); outline-offset: 2px; }
	.np-item { padding: 12px 0 14px; border-bottom: 1px solid rgba(29,29,27,.3); }
	.np-item:last-child { border-bottom: 0; padding-bottom: 30px; }
	.np-item::after { content: ""; display: block; clear: both; }
	.np-item .np-kicker { font-size: 10.5px; text-align: left; }
	.np-item-h { margin: 0 0 6px; font-family: Georgia, "Times New Roman", serif; font-size: 17px; line-height: 1.22; font-weight: 700;
		text-transform: uppercase; color: var(--ink); }
	.np-thumb { float: right; width: 104px; height: 78px; margin: 3px 0 6px 12px; object-fit: cover; border: 1px solid var(--ink); background: #d8d6cf; }
	.np-item-text { margin: 0; font-size: 14px; line-height: 1.5; }
	.np-item .np-more { margin-top: 8px; padding: 4px 10px; font-size: 11px; }

	/* Photos: in their own colours, in a thin printed frame. */
	.np-photo { margin: 0; }
	.np-plate { position: relative; overflow: hidden; aspect-ratio: 4 / 3; background: #d8d6cf; border: 1px solid var(--ink);
		box-shadow: 0 0 0 4px var(--paper), 0 0 0 5px var(--ink-2); }
	.np-plate img { display: block; width: 100%; height: 100%; object-fit: cover; transform: scale(1.1); }
	.np-top .np-plate { aspect-ratio: 16 / 9; }
	.np-photo figcaption { margin-top: 10px; font-size: 12px; font-style: italic; text-align: center; color: var(--ink-2); }

	/* Living photos: the lead photo drifts slowly. */
	.np-live .np-plate img, .np-dialog .np-plate img { animation: np-drift 14s ease-in-out infinite alternate; }
	@keyframes np-drift { from { transform: scale(1.1) translate(2%, 1.5%); } to { transform: scale(1.2) translate(-2.5%, -2%); } }

	.np-more:focus-visible, .np-d-close:focus-visible, .np-d-link:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }

	/* Read more */
	.np-scrim { position: fixed; inset: 0; z-index: 2000; background: rgba(15,15,15,.62); }
	.np-dialog { position: fixed; z-index: 2001; left: 50%; top: 50%; transform: translate(-50%, -50%); width: min(720px, calc(100vw - 32px));
		max-height: calc(100vh - 48px); overflow: auto; overscroll-behavior: contain; padding: 18px clamp(16px, 3vw, 30px) 26px;
		box-shadow: 0 24px 60px rgba(0,0,0,.5); }
	.np-scrim[hidden], .np-dialog[hidden] { display: none; }
	.np-d-head { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding-bottom: 6px; border-bottom: 3px double var(--ink); }
	.np-d-kicker { font-size: 11.5px; letter-spacing: .18em; text-transform: uppercase; color: var(--ink-2); }
	.np-d-close { flex: none; width: 32px; height: 32px; display: grid; place-items: center; padding: 0; border: 1px solid var(--ink); border-radius: 50%;
		background: transparent; color: var(--ink); cursor: pointer; }
	.np-d-close:hover { background: var(--ink); color: var(--paper); }
	.np-d-title { margin: 14px 0 14px; font-family: Georgia, "Times New Roman", serif; font-size: clamp(22px, 3vw, 32px); line-height: 1.12; font-weight: 700;
		text-transform: uppercase; text-align: center; text-wrap: balance; color: var(--ink); }
	.np-d-photo { max-width: 520px; margin: 0 auto 18px; }
	.np-d-body { font-size: 16px; line-height: 1.65; }
	.np-d-body img { max-width: 100%; height: auto; }
	.np-d-body a { color: inherit; text-decoration: underline; }
	.np-d-link { display: inline-block; margin-top: 14px; padding: 8px 16px; border: 1px solid var(--ink); color: var(--ink); text-decoration: none;
		font-size: 12.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
	.np-d-link:hover { background: var(--ink); color: var(--paper); }
	.np-d-msg { margin: 0; padding: 34px 10px; text-align: center; font-style: italic; }

	@media (max-width: 860px) {
		/* One column: the lead story, then the list below it, still scrolling on its own. */
		.np-front { grid-template-columns: 1fr; }
		.np-top { padding: 0 0 18px; border-right: 0; border-bottom: 1px solid var(--ink); }
		.np-list { padding: 14px 0 0; }
		.np-scroll { flex: none; height: min(520px, 70vh); }
	}
	@media (max-width: 640px) {
		#sec-news { padding: 30px 10px 38px; }
		.np-mast { grid-template-columns: 1fr; text-align: center; }
		.np-ear, .np-dateline .np-free { display: none; }
		.np-dateline { justify-content: center; }
	}
	@media (max-width: 560px) {
		.np-story { column-count: 1; }
		.np-lead { font-size: 15px; text-align: left; }
	}
	@media (prefers-reduced-motion: reduce) {
		.np-live .np-plate img, .np-dialog .np-plate img { animation: none; }
	}
</style>
<section id="sec-news" aria-labelledby="np-masthead">
	{{-- The kinds of scrap, dealt over enough cells to fill the pile in a fixed shuffled order (the same on every visit),
	     so no column of the grid repeats the same scrap the way a regular order would. --}}
	@php
		$__scraps = [
			['', '<div class="m">NEWS</div><span class="k">Vol. XII &middot; No. 48 &middot; Morning edition</span><i class="c2"></i>'],
			['s', 'Crossword<i class="cw"></i><i></i>'], ['torn', 'Harvest report<i class="c2"></i>'], ['s', 'Classifieds<i class="cls"></i>'],
			['', '<i class="pic"></i><i></i>'], ['s', 'Weather<i class="sun"></i><i></i>'], ['s torn', 'Markets<i class="tbl"></i>'],
			['s', 'Comics<i class="cmx"></i><i></i>'], ['', 'Sports<i class="c2"></i>'], ['torn', 'Science<i class="c2"></i>'],
			['s', 'Letters<i></i>'], ['', 'Local news<i class="c2"></i>'], ['torn', 'Books<i class="c2"></i>'],
			['xl', 'Extra!<i class="c2"></i>'], ['s', 'Opinion<i></i>'], ['', 'Farm &amp; field<i class="c2"></i>'],
		];
		$__deal = (new Random\Randomizer(new Random\Engine\Mt19937(2026)))->shuffleArray(range(0, 71));
	@endphp
	<div class="nc" aria-hidden="true"><div class="nc-in">
		@for($__i = 0; $__i < 72; $__i++)
			@php [$__c, $__html] = $__scraps[$__deal[$__i] % 16]; @endphp
			<div{!! $__c ? ' class="' . $__c . '"' : '' !!}>{!! $__html !!}</div>
		@endfor
	</div></div>
	<div class="np-sheet">
		@php
			$lead = $items->first();
			$more = $items->slice(1);
			$cover = newsPrintCoverUrl($lead);
		@endphp
		<div class="np-mast">
			<p class="np-ear">The library's<br>latest stories</p>
			<h2 class="np-title" id="np-masthead">NEWS</h2>
			<p class="np-ear np-ear-r">{!! $more->isNotEmpty() ? 'More stories<br>alongside' : 'The full story<br>inside' !!}</p>
		</div>
		<div class="np-dateline">
			<span>{{ now()->format('l, j F Y') }}</span>
			<span class="np-free">Free to every reader</span>
		</div>

		<div class="np-front{{ $more->isEmpty() ? ' is-single' : '' }}">
			<article class="np-top" aria-labelledby="np-h-{{ $lead->id }}">
				<p class="np-kicker">{{ $lead->id === (int) setting('news_headline_id') ? 'Headline' : 'Latest' }}</p>
				<h3 class="np-headline" id="np-h-{{ $lead->id }}">{{ $lead->news_title }}</h3>
				@if($cover)
					<figure class="np-photo">
						<div class="np-plate"><img src="{{ $cover }}" alt="" loading="lazy" decoding="async"></div>
						<figcaption>Posted {{ $lead->created_at?->format('j F Y') }}</figcaption>
					</figure>
				@endif
				<div class="np-story">
					<p class="np-lead">{{ newsExcerpt($lead, $cover ? 520 : 1000) }}</p>
					<button type="button" class="np-more" data-news="{{ $lead->id }}" aria-haspopup="dialog">Read more <span aria-hidden="true">&#9656;</span></button>
				</div>
			</article>
			@if($more->isNotEmpty())
				<aside class="np-list" aria-labelledby="np-more-h">
					<h3 class="np-list-h" id="np-more-h">More stories</h3>
					<div class="np-scroll" tabindex="0" aria-label="More stories, newest first: scroll for older ones">
						@foreach($more as $news)
							@php $thumb = newsThumbUrl($news); @endphp
							<article class="np-item">
								<p class="np-kicker">{{ $news->created_at?->format('j F Y') }}</p>
								<h4 class="np-item-h">{{ $news->news_title }}</h4>
								@if($thumb)<img class="np-thumb" src="{{ $thumb }}" alt="" loading="lazy" decoding="async">@endif
								<p class="np-item-text">{{ newsExcerpt($news, 200) }}</p>
								<button type="button" class="np-more" data-news="{{ $news->id }}" aria-haspopup="dialog">Read more <span aria-hidden="true">&#9656;</span></button>
							</article>
						@endforeach
					</div>
				</aside>
			@endif
		</div>
	</div>
	<div class="np-scrim" id="np-scrim" hidden></div>
	<div class="np-dialog" id="np-dialog" role="dialog" aria-modal="true" aria-labelledby="np-dialog-title" hidden></div>
</section>
<script nonce="{{ cspNonce() }}" type="text/javascript">
(function () {
	var desk = document.getElementById('sec-news');
	if (!desk) return;
	var top = desk.querySelector('.np-top');

	// The masthead's web font: only the letters N, E, W, S (~5 KB), fetched as the section comes near.
	function loadFont() {
		var l = document.createElement('link');
		l.rel = 'stylesheet';
		l.href = 'https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&text=NEWS&display=swap';
		document.head.appendChild(l);
	}
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(function (en, io) { if (en[0].isIntersecting) { loadFont(); io.disconnect(); } }, { rootMargin: '800px 0px' }).observe(desk);
		// The lead photo drifts only while the section is on screen.
		new IntersectionObserver(function (en) { top.classList.toggle('np-live', en[0].isIntersecting); }, { threshold: 0.15 }).observe(desk);
	} else {
		loadFont();
		top.classList.add('np-live');
	}

	// Read more: the article in a dialog; its external link opens in a new tab.
	var scrim = document.getElementById('np-scrim'), dialog = document.getElementById('np-dialog'), opener = null;
	document.body.appendChild(scrim);
	document.body.appendChild(dialog);
	function show(html) {
		dialog.innerHTML = html;
		dialog.hidden = scrim.hidden = false;
		dialog.scrollTop = 0;
		document.documentElement.style.overflow = 'hidden';
		var x = dialog.querySelector('[data-np-close]');
		if (x) x.focus();
	}
	function close() {
		dialog.hidden = scrim.hidden = true;
		dialog.innerHTML = '';
		document.documentElement.style.overflow = '';
		if (opener) opener.focus();
	}
	desk.addEventListener('click', function (e) {
		var b = e.target.closest('.np-more');
		if (!b) return;
		opener = b;
		b.disabled = true;
		fetch(@json(url('/news')) + '/' + encodeURIComponent(b.getAttribute('data-news')), { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
			.then(function (r) { if (!r.ok) throw new Error(r.status); return r.text(); })
			.then(show, function () {
				show('<div class="np-d-head"><span class="np-d-kicker">News</span><button type="button" class="np-d-close" data-np-close aria-label="Close">&times;</button></div>'
					+ '<p class="np-d-msg" id="np-dialog-title">This story could not be opened. Please try again.</p>');
			})
			.then(function () { b.disabled = false; });
	});
	dialog.addEventListener('click', function (e) { if (e.target.closest('[data-np-close]')) close(); });
	scrim.addEventListener('click', close);
	document.addEventListener('keydown', function (e) { if (e.key === 'Escape' && !dialog.hidden) close(); });
})();
</script>
@endif
