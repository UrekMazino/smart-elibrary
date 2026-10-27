{{-- NEWS (OPAC home): the featured news articles as the pages of an old newspaper, latest first.
     Swipe, scroll sideways, use the arrow keys or the page buttons to turn pages; "Read more" opens
     the article in a dialog (HomeController::news), with its external link opening in a new tab.

     Built to stay light: the paper, grain and vignette are CSS (no texture images); headlines and
     text use the serif fonts every device has; only the masthead loads a web font, the letters
     N-E-W-S alone (~5 KB), and only when the section comes near. Photos are the ~800 px WebP copies
     made at upload (makeNewsPrintCopy), loaded lazily. The "living photo" motion runs only on the
     page in view while the section is on screen, and not at all for "reduce motion". --}}
@if($items->isNotEmpty())
<style>
	#sec-news { padding: 34px 16px 46px; background: #2b241e radial-gradient(ellipse at 50% 0%, #54473b 0%, #2b241e 65%); }
	.np-sheet, .np-dialog {
		--ink: #2a2118; --ink-2: #5b4a37; --paper: #efe4c8;
		color: var(--ink); font-family: Georgia, "Times New Roman", serif;
		background-color: var(--paper);
		background-image:
			url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='200' height='200'%3E%3Cfilter id='n'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='.8' numOctaves='3' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .35 0 0 0 0 .24 0 0 0 0 .1 0 0 0 .55 -.18'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23n)'/%3E%3C/svg%3E"),
			radial-gradient(ellipse at 18% 12%, rgba(255,251,238,.6), transparent 55%),
			radial-gradient(ellipse at 88% 92%, rgba(139,98,48,.22), transparent 55%);
		box-shadow: 0 22px 44px rgba(0,0,0,.5), inset 0 0 70px rgba(120,84,38,.28);
		border-radius: 2px;
	}
	.np-sheet { position: relative; max-width: 1100px; margin: 0 auto; padding: 16px clamp(14px, 3vw, 36px) 14px; }

	/* Masthead */
	.np-mast { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 16px; padding-bottom: 4px; border-bottom: 3px double var(--ink); }
	.np-title { margin: 0; font-family: "UnifrakturMaguntia", "Old English Text MT", Georgia, serif; font-weight: 400;
		font-size: clamp(58px, 9vw, 108px); line-height: 1; color: var(--ink); text-shadow: 0 1px 0 rgba(255,255,255,.35); }
	.np-ear { justify-self: start; max-width: 150px; margin: 0; padding: 6px 10px; border: 1px solid var(--ink);
		font-size: 10.5px; line-height: 1.4; letter-spacing: .14em; text-transform: uppercase; text-align: center; color: var(--ink-2); }
	.np-ear-r { justify-self: end; }
	.np-dateline { display: flex; justify-content: space-between; gap: 12px; padding: 5px 2px; border-bottom: 1px solid var(--ink);
		font-size: 11.5px; letter-spacing: .16em; text-transform: uppercase; }

	/* Pages: one article each, side by side; scroll-snap stops on each. */
	/* Each page keeps its own height; the script sizes the strip to the page showing. */
	.np-pages { display: flex; align-items: flex-start; overflow-x: auto; scroll-snap-type: x mandatory; scroll-behavior: smooth;
		overscroll-behavior-x: contain; scrollbar-width: none; margin-top: 14px; transition: height .25s ease; }
	.np-pages::-webkit-scrollbar { display: none; }
	.np-pages:focus-visible { outline: 2px dashed var(--ink-2); outline-offset: 4px; }
	.np-page { flex: 0 0 100%; min-width: 0; padding: 0 10px 6px; scroll-snap-align: start; scroll-snap-stop: always; }
	.np-kicker { margin: 0 0 4px; font-size: 12px; font-weight: 700; letter-spacing: .24em; text-transform: uppercase; text-align: center; color: var(--ink-2); }
	.np-headline { margin: 0 0 14px; font-family: Georgia, "Times New Roman", serif; font-weight: 700; font-size: clamp(24px, 3.4vw, 40px);
		line-height: 1.08; text-transform: uppercase; text-align: center; text-wrap: balance; color: var(--ink); }
	.np-headline::after { content: ""; display: block; width: 120px; margin: 10px auto 0; border-top: 3px double var(--ink); }
	.np-cols { display: grid; grid-template-columns: minmax(0, 1.05fr) minmax(0, 1fr); gap: 24px; align-items: start; }
	.np-page.no-photo .np-cols { grid-template-columns: 1fr; }
	.np-story { column-count: 2; column-gap: 22px; column-rule: 1px solid rgba(42,33,24,.35); }
	.np-page.no-photo .np-story { column-count: 3; }
	.np-lead { margin: 0; font-size: 15.5px; line-height: 1.55; text-align: justify; hyphens: auto; }
	.np-lead::first-letter { float: left; font-size: 3.6em; line-height: .82; padding: 5px 7px 0 0; font-weight: 700; }
	.np-more { display: inline-block; margin-top: 12px; padding: 6px 14px; border: 1px solid var(--ink); background: transparent; color: var(--ink);
		font: inherit; font-size: 12px; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; cursor: pointer; }
	.np-more:hover { background: var(--ink); color: var(--paper); }
	.np-more:disabled { opacity: .6; cursor: progress; }

	/* Photos: sepia print in a plate, with grain and a vignette. */
	.np-photo { margin: 0; }
	.np-plate { position: relative; overflow: hidden; aspect-ratio: 4 / 3; background: #b9a989; border: 1px solid var(--ink);
		box-shadow: 0 0 0 4px var(--paper), 0 0 0 5px var(--ink-2); }
	.np-plate img { display: block; width: 100%; height: 100%; object-fit: cover; filter: sepia(.8) contrast(1.1) brightness(.92); transform: scale(1.1); }
	.np-plate::before { content: ""; position: absolute; inset: -40%; z-index: 1; pointer-events: none; opacity: .5; mix-blend-mode: multiply;
		background: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='140' height='140'%3E%3Cfilter id='g'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='1.3' numOctaves='2' stitchTiles='stitch'/%3E%3CfeColorMatrix values='0 0 0 0 .1 0 0 0 0 .07 0 0 0 0 .03 0 0 0 .9 -.3'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23g)'/%3E%3C/svg%3E"); }
	.np-plate::after { content: ""; position: absolute; inset: 0; z-index: 2; pointer-events: none;
		background: radial-gradient(ellipse at 50% 45%, transparent 52%, rgba(45,28,12,.55) 100%); }
	.np-photo figcaption { margin-top: 10px; font-size: 12px; font-style: italic; text-align: center; color: var(--ink-2); }

	/* Living photos: the page in view drifts slowly, its grain shimmers and its light flickers. */
	.np-live .np-plate img, .np-dialog .np-plate img { animation: np-drift 14s ease-in-out infinite alternate; }
	.np-live:nth-child(even) .np-plate img { animation-name: np-drift-2; }
	.np-live .np-plate::before, .np-dialog .np-plate::before { animation: np-grain 1s steps(6) infinite; }
	.np-live .np-plate::after, .np-dialog .np-plate::after { animation: np-flicker 6s linear infinite; }
	@keyframes np-drift { from { transform: scale(1.1) translate(2%, 1.5%); } to { transform: scale(1.2) translate(-2.5%, -2%); } }
	@keyframes np-drift-2 { from { transform: scale(1.18) translate(-2%, 1%); } to { transform: scale(1.1) translate(2.5%, -1.5%); } }
	@keyframes np-grain { 0%, 100% { transform: translate(0, 0); } 17% { transform: translate(-6%, 4%); } 33% { transform: translate(5%, -5%); }
		50% { transform: translate(-3%, -6%); } 67% { transform: translate(6%, 3%); } 83% { transform: translate(-5%, 6%); } }
	@keyframes np-flicker { 0%, 41%, 44%, 77%, 79%, 100% { opacity: 1; } 42% { opacity: .78; } 78% { opacity: .88; } }

	/* Page turning, under the pages. */
	.np-foot { display: flex; align-items: center; justify-content: space-between; gap: 10px; margin-top: 16px; padding-top: 8px; border-top: 1px solid var(--ink); }
	.np-turn { border: 0; background: none; padding: 4px 2px; color: var(--ink); font: inherit; font-size: 12px; font-weight: 700; letter-spacing: .16em;
		text-transform: uppercase; cursor: pointer; }
	.np-turn:hover:not(:disabled) { text-decoration: underline; }
	.np-turn:disabled { opacity: .3; cursor: default; }
	.np-count { font-size: 12px; letter-spacing: .16em; text-transform: uppercase; color: var(--ink-2); font-variant-numeric: tabular-nums; }
	.np-more:focus-visible, .np-turn:focus-visible, .np-d-close:focus-visible, .np-d-link:focus-visible { outline: 2px solid var(--ink); outline-offset: 2px; }

	/* Read more */
	.np-scrim { position: fixed; inset: 0; z-index: 2000; background: rgba(20,14,8,.62); }
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
	.np-d-body a { color: #6b3410; }
	.np-d-link { display: inline-block; margin-top: 14px; padding: 8px 16px; border: 1px solid var(--ink); color: var(--ink); text-decoration: none;
		font-size: 12.5px; font-weight: 700; letter-spacing: .12em; text-transform: uppercase; }
	.np-d-link:hover { background: var(--ink); color: var(--paper); }
	.np-d-msg { margin: 0; padding: 34px 10px; text-align: center; font-style: italic; }

	@media (max-width: 860px) {
		.np-cols { grid-template-columns: 1fr; }
		.np-page .np-photo { max-width: 560px; margin: 0 auto; }
	}
	@media (max-width: 640px) {
		.np-mast { grid-template-columns: 1fr; text-align: center; }
		.np-ear, .np-dateline .np-free { display: none; }
		.np-dateline { justify-content: center; }
	}
	@media (max-width: 560px) {
		.np-story, .np-page.no-photo .np-story { column-count: 1; }
		.np-lead { font-size: 15px; text-align: left; }
	}
	@media (prefers-reduced-motion: reduce) {
		.np-live .np-plate img, .np-live .np-plate::before, .np-live .np-plate::after,
		.np-dialog .np-plate img, .np-dialog .np-plate::before, .np-dialog .np-plate::after { animation: none; }
		.np-pages { scroll-behavior: auto; transition: none; }
	}
</style>
<section id="sec-news" aria-labelledby="np-masthead">
	<div class="np-sheet">
		<div class="np-mast">
			<p class="np-ear">The library's<br>latest stories</p>
			<h2 class="np-title" id="np-masthead">NEWS</h2>
			<p class="np-ear np-ear-r">Turn the page<br>for more</p>
		</div>
		<div class="np-dateline">
			<span>{{ now()->format('l, j F Y') }}</span>
			<span class="np-free">Free to every reader</span>
		</div>

		<div class="np-pages" tabindex="0" aria-label="News pages: swipe, scroll sideways or use the arrow keys">
			@foreach($items as $i => $news)
				@php $cover = newsPrintCoverUrl($news); @endphp
				<article class="np-page{{ $cover ? '' : ' no-photo' }}" aria-label="Page {{ $i + 1 }} of {{ $items->count() }}">
					<p class="np-kicker">{{ $i === 0 ? 'Latest' : $news->created_at?->format('j F Y') }}</p>
					<h3 class="np-headline">{{ $news->news_title }}</h3>
					<div class="np-cols">
						@if($cover)
							<figure class="np-photo">
								<div class="np-plate"><img src="{{ $cover }}" alt="" loading="lazy" decoding="async"></div>
								<figcaption>{{ $i === 0 ? 'Posted ' : '' }}{{ $news->created_at?->format('j F Y') }}</figcaption>
							</figure>
						@endif
						<div class="np-story">
							<p class="np-lead">{{ newsExcerpt($news, $cover ? 600 : 900) }}</p>
							<button type="button" class="np-more" data-news="{{ $news->id }}" aria-haspopup="dialog">Read more <span aria-hidden="true">&#9656;</span></button>
						</div>
					</div>
				</article>
			@endforeach
		</div>

		<div class="np-foot">
			<button type="button" class="np-turn np-prev"><span aria-hidden="true">&#9666;</span> Previous</button>
			<span class="np-count" aria-live="polite">Page 1 of {{ $items->count() }}</span>
			<button type="button" class="np-turn np-next">Next <span aria-hidden="true">&#9656;</span></button>
		</div>
	</div>
	<div class="np-scrim" id="np-scrim" hidden></div>
	<div class="np-dialog" id="np-dialog" role="dialog" aria-modal="true" aria-labelledby="np-dialog-title" hidden></div>
</section>
<script nonce="{{ cspNonce() }}" type="text/javascript">
(function () {
	var desk = document.getElementById('sec-news');
	if (!desk) return;
	var track = desk.querySelector('.np-pages');
	var pages = [].slice.call(track.querySelectorAll('.np-page'));
	var prev = desk.querySelector('.np-prev'), next = desk.querySelector('.np-next'), count = desk.querySelector('.np-count');
	var current = 0, onScreen = false, queued = false;

	// The page showing; only its photo moves, and only while the section is on screen.
	function update() {
		queued = false;
		current = Math.max(0, Math.min(pages.length - 1, Math.round(track.scrollLeft / (track.clientWidth || 1))));
		count.textContent = 'Page ' + (current + 1) + ' of ' + pages.length;
		prev.disabled = current === 0;
		next.disabled = current === pages.length - 1;
		pages.forEach(function (p, i) { p.classList.toggle('np-live', onScreen && i === current); });
		track.style.height = pages[current].offsetHeight + 'px';
	}
	track.addEventListener('scroll', function () { if (!queued) { queued = true; requestAnimationFrame(update); } }, { passive: true });
	window.addEventListener('resize', update);
	prev.addEventListener('click', function () { track.scrollTo({ left: (current - 1) * track.clientWidth }); });
	next.addEventListener('click', function () { track.scrollTo({ left: (current + 1) * track.clientWidth }); });

	// The masthead's web font: only the letters N, E, W, S (~5 KB), fetched as the section comes near.
	function loadFont() {
		var l = document.createElement('link');
		l.rel = 'stylesheet';
		l.href = 'https://fonts.googleapis.com/css2?family=UnifrakturMaguntia&text=NEWS&display=swap';
		document.head.appendChild(l);
	}
	if ('IntersectionObserver' in window) {
		new IntersectionObserver(function (en, io) { if (en[0].isIntersecting) { loadFont(); io.disconnect(); } }, { rootMargin: '800px 0px' }).observe(desk);
		new IntersectionObserver(function (en) { onScreen = en[0].isIntersecting; update(); }, { threshold: 0.15 }).observe(desk);
	} else {
		loadFont();
		onScreen = true;
	}
	update();

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
