{{-- The highlight shelves (Most Downloaded, Most Viewed, Highest Rating, Recent Uploads) come first, so they are the
     shelves in view when the page opens; then a shelf per material type (its newest 45, by title A-Z). A record's
     ranks in the three top-10 lists show as medals on its cover there. The top-10 shelves hold the last 90 days
     and all time; the switch under the heading picks which shows (the last 90 days first, as the medals do). --}}
@php
	$__highlights = getHighlightShelves();
	$__shelves = array_merge($__highlights, getShelves(45));
	$__ranked = collect($__highlights)->contains('ranked', true);
@endphp
@if(count($__shelves))
<style>
	/* ---- eLibrary teal color scheme: multi-column shelf explorer ---- */
	.shelf-browser { padding: 52px 24px 64px; background: radial-gradient(130% 120% at 50% -10%, #f1faf8 0%, #dcefeb 62%, #cfe8e3 100%); }
	/* Heading now carries the prev/next paging arrows, flanking the title. */
	.shelf-browser .shelf-heading { display:flex; align-items:center; justify-content:center; gap:20px; margin-bottom:22px; }
	.shelf-browser .shelf-title { text-align:center; }
	.shelf-browser .shelf-heading .orn { color:#c32148; font-size:20px; }
	.shelf-browser .shelf-heading h2 { font-family:Georgia,"Palatino Linotype",serif; font-size:26px; letter-spacing:2px; text-transform:uppercase; color: #7f410a; margin:4px 0 0; font-weight:700; }

	.shelf-explorer { position:relative; max-width:1900px; margin:0 auto; padding:0 10px; }
	.shelf-viewport { overflow:hidden; }
	.shelf-cols { display:flex; gap:20px; align-items:flex-start; transition:transform .45s cubic-bezier(.4,0,.2,1); will-change:transform; }
	.shelf-col { flex:0 0 auto; min-width:0; display:flex; flex-direction:column; border-radius:8px; overflow:hidden; align-self:stretch;
		border: 1px solid #49290f; box-shadow: inset 0 0 0 3px rgba(255,255,255,.16), 0 24px 44px -26px rgba(4,50,45,.55);
		background: repeating-linear-gradient(90deg, rgba(0,0,0,.05) 0 3px, rgba(255,255,255,.03) 3px 9px), linear-gradient(#212529, #212529); }
	.shelf-col-head { padding:11px 14px; text-align:center; color:#eafaf7; background: linear-gradient(#67410b, #452204);
		border-bottom: 2px solid #b77844; font-family:Georgia,"Palatino Linotype",serif; font-size:16px; letter-spacing:.5px;
		white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
	.shelf-col-body { padding:4px 0 10px; }

	.col-scroll-wrap { position:relative; }
	.col-scroll { overflow-x:auto; overflow-y:hidden; padding:0 14px; scrollbar-width:none; -ms-overflow-style:none;
		/* recessed interior: shadow cast by the shelf above onto the books below */
		box-shadow: inset 0 14px 16px -12px rgba(0,0,0,.7), inset 8px 0 10px -10px rgba(0,0,0,.5), inset -8px 0 10px -10px rgba(0,0,0,.5); }
	.col-scroll::-webkit-scrollbar { display:none; }   /* scrollbar removed — use the per-row arrows */
	/* Per-row prev/next: frosted, semi-transparent, revealed on hover of the row. */
	.row-arrow { position:absolute; top:calc(50% + 4px); transform:translateY(-50%); width:26px; height:50px; z-index:4;
		border:0; border-radius:8px; cursor:pointer; color:#f2fdfb; font-size:19px; line-height:1;
		display:flex; align-items:center; justify-content:center;
		background:rgba(4,46,42,.40); -webkit-backdrop-filter:blur(3px); backdrop-filter:blur(3px);
		box-shadow:0 2px 10px rgba(0,0,0,.42), inset 0 0 0 1px rgba(255,255,255,.18);
		opacity:0; transition:opacity .22s ease, background .18s ease; }
	.col-scroll-wrap:hover .row-arrow, .col-scroll-wrap:focus-within .row-arrow { opacity:1; }
	/* Touch screens have no hover: keep the row arrows visible there. */
	@media (hover: none) { .row-arrow { opacity:.9; } }
	.row-arrow:hover { background:rgba(127,65,10,.85); }
	.row-arrow.prev { left:7px; } .row-arrow.next { right:7px; }
	.row-arrow[hidden] { display:none; }
	.col-row { position:relative; display:flex; gap:15px; min-width:100%; width:max-content; align-items:flex-end;
		justify-content:safe center; padding:38px 6px 26px; }
	/* Shelf board: a solid wooden ledge with a lit front edge + depth shadow under each row,
	   so every row reads as its own distinct shelf. */
	.col-row::after { content:""; position:absolute; left:0; right:0; bottom:0; height:17px;
		background: linear-gradient(#ffba68 0, #79490a 45%, #5b3611 100%);
		border-top: 2px solid #ffd5a7; box-shadow:0 2px 0 rgba(0,0,0,.35), 0 11px 13px -6px rgba(0,0,0,.62); }
	.col-book { flex:0 0 auto; display:block; line-height:0; position:relative; z-index:1; transition:transform .14s ease; }
	.col-link { display:block; }
	/* natural proportions, capped -> heights vary a bit like a real shelf; bottoms align via flex-end */
	.col-book img { display:block; width:auto; height:auto; max-height:186px; max-width:144px; border-radius:2px; background:#fff;
		box-shadow:0 3px 4px rgba(0,0,0,.45), 0 15px 18px -9px rgba(0,0,0,.8); }
	.col-book:hover { transform:translateY(-4px); z-index:2; }
	.col-book.no-link { cursor:default; }
	/* Highlight shelves: each top-10 book wears its rank on a small foil crest at its upper-left corner (gold, silver
	   and bronze for 1-3, a crown on #1; gold numerals on ebony for 4-10), and shows its count / rating / date on hover.
	   The crest is a metallic rim (::before) round a face (::after), both cut to a shield; the number sits on top. */
	.col-rank { position:absolute; top:-12px; left:-11px; z-index:3; width:30px; height:36px; display:grid; place-items:center; padding-bottom:7px;
		pointer-events:none; font:700 15px/1 Georgia,"Palatino Linotype",serif; font-variant-numeric:lining-nums; letter-spacing:-.02em;
		filter:drop-shadow(0 3px 3px rgba(0,0,0,.55)); }
	.col-rank::before, .col-rank::after { content:""; position:absolute; z-index:-1;
		clip-path:polygon(0 0, 100% 0, 100% 60%, 86% 78%, 50% 100%, 14% 78%, 0 60%); }
	.col-rank::before { inset:0; background:var(--rk-rim); }
	.col-rank::after { inset:2px; background:var(--rk-face); }
	.col-rank b { position:relative; }
	.col-rank.r1 { --rk-rim:linear-gradient(135deg,#7a5a0c,#f9e08b 30%,#b8860b 55%,#fff1b3 75%,#8a6510);
		--rk-face:linear-gradient(160deg,#fff6c8,#f1c94a 35%,#c9971c 62%,#f7dd84 85%,#b07d12); color:#4a3305; text-shadow:0 1px 0 rgba(255,255,255,.55); }
	.col-rank.r2 { --rk-rim:linear-gradient(135deg,#5c636c,#f5f7fa 30%,#9aa3ad 55%,#fff 75%,#6b737c);
		--rk-face:linear-gradient(160deg,#fff,#d9dee4 35%,#a7b0ba 62%,#eef1f4 85%,#8c96a1); color:#2f3640; text-shadow:0 1px 0 rgba(255,255,255,.7); }
	.col-rank.r3 { --rk-rim:linear-gradient(135deg,#5a2f0e,#f2b98a 30%,#a8622a 55%,#ffd2ad 75%,#6e3a12);
		--rk-face:linear-gradient(160deg,#ffe2c7,#e0995c 35%,#b0682d 62%,#f0b37f 85%,#8a4c1d); color:#3d1d06; text-shadow:0 1px 0 rgba(255,230,205,.6); }
	.col-rank.rn { --rk-rim:linear-gradient(135deg,#7a5a0c,#f9e08b 35%,#b8860b 60%,#8a6510); --rk-face:linear-gradient(160deg,#3d2c1d,#1b120b 60%,#2c1f15); }
	.col-rank.rn b { background:linear-gradient(180deg,#fff3c4,#e8b93f 55%,#b8860b); -webkit-background-clip:text; background-clip:text; color:transparent; }
	.col-rank .rk-crown { position:absolute; top:-9px; left:50%; width:18px; height:10px; translate:-50% 0;
		background:linear-gradient(180deg,#fff1b3,#e2b23c 60%,#a8780f); clip-path:polygon(0 100%, 0 25%, 25% 62%, 50% 0, 75% 62%, 100% 25%, 100% 100%); }
	.col-detail { position:absolute; left:0; right:0; bottom:0; z-index:2; padding:18px 5px 6px; border-radius:0 0 2px 2px; pointer-events:none;
		background:linear-gradient(transparent, rgba(30,15,3,.9)); color:#fff; font-size:11.5px; font-weight:700; line-height:1.25; text-align:center;
		opacity:0; transition:opacity .2s ease; }
	.col-book:hover .col-detail, .col-book:focus-within .col-detail { opacity:1; }
	@media (hover: none) { .col-detail { opacity:1; } }

	/* Top-10 window: the switch shows the last 90 days or all time on the top-10 shelves (clones too); each such
	   shelf names its window under its title. A list empty in the last 90 days says so and offers all time. */
	.rank-window { display:flex; align-items:center; justify-content:center; gap:12px; flex-wrap:wrap; margin:-10px 0 20px;
		font:11px/1.4 Georgia,"Palatino Linotype",serif; letter-spacing:.16em; text-transform:uppercase; }
	.rank-window-label { display:inline-flex; align-items:center; gap:12px; color:#7a5a2e; }
	.rank-window-label::after { content:""; width:22px; height:1px; background:#c9a86a; }
	.rank-window-opts { display:inline-flex; align-items:center; }
	.rank-window-opts button { position:relative; margin:0; padding:6px 0; border:0; background:none; color:#7a5a2e;
		font:inherit; letter-spacing:inherit; text-transform:inherit; cursor:pointer; transition:color .16s ease; }
	.rank-window-opts button + button { margin-left:22px; }
	.rank-window-opts button + button::before { content:"\00B7"; position:absolute; left:-13px; color:#b08a4a; pointer-events:none; }
	.rank-window-opts button::after { content:""; position:absolute; left:0; right:.16em; bottom:3px; height:1px; background:#a06e14;
		transform:scaleX(0); transition:transform .2s ease; }
	.rank-window-opts button:hover { color:#49290f; }
	.rank-window-opts button[aria-pressed="true"] { color:#7f410a; }
	.rank-window-opts button[aria-pressed="true"]::after { transform:scaleX(1); }
	.rank-window button:focus-visible, .shelf-empty-btn:focus-visible { outline:2px solid #7f410a; outline-offset:2px; }
	.shelf-col-when { display:block; margin-top:2px; font:600 11.5px/1.2 system-ui,-apple-system,"Segoe UI",Roboto,sans-serif;
		letter-spacing:.3px; color:#f0cf9c; }
	.shelf-browser:not(.is-all-time) [data-window-only="all"], .shelf-browser.is-all-time [data-window-only="recent"] { display:none; }
	.shelf-empty { margin:0; padding:52px 18px; text-align:center; color:#eafaf7; font-size:14px; line-height:1.5; }
	.shelf-empty-btn { display:inline-block; margin-top:10px; padding:4px 15px; border:1.5px solid #e2b23c; border-radius:999px;
		background:transparent; color:#ffe7b0; font-weight:600; cursor:pointer; }
	.shelf-empty-btn:hover { background:#a06e14; border-color:#a06e14; color:#fff; }

	/* Minimalist paging arrows: thin flat outline circle that fills teal on hover. */
	.shelf-page { flex:0 0 auto; width:38px; height:38px; border-radius:50%;
		border: 1.5px solid #a06e14; background:transparent; cursor:pointer; color: #49290f; font-size:18px; line-height:1;
		display:flex; align-items:center; justify-content:center; transition:background .16s ease, color .16s ease, border-color .16s ease; }
	.shelf-page:hover { background:#a06e14; border-color:#a06e14; color:#fff; }
	.shelf-page:active { transform:translateY(1px); }
	.shelf-page:disabled { opacity:.3; cursor:default; background:transparent; color:#724e0a; }

	@media (max-width:1100px){ .col-book img { max-height:162px; max-width:126px; } }
	/* Phones: one full-width shelf at a time. A horizontal swipe anywhere on the shelf changes shelf
	   (pan-y: vertical swipes still scroll the page); book rows scroll with their ‹ › arrows. */
	@media (max-width:767px){
		.shelf-browser { padding:36px 6px 44px; }
		.shelf-viewport, .col-scroll { touch-action:pan-y; }
	}
	@media (max-width:767px){ .shelf-explorer { padding:0 8px; } .shelf-page { width:34px; height:34px; font-size:16px; } .col-book img { max-height:140px; max-width:110px; } }
</style>
<section class="shelf-browser">
	<div class="shelf-heading">
		<button type="button" class="shelf-page prev" aria-label="Previous shelves">&#8249;</button>
		<div class="shelf-title">
			<span class="orn">&#10087;</span>
			<h2>Browse the Shelves</h2>
		</div>
		<button type="button" class="shelf-page next" aria-label="Next shelves">&#8250;</button>
	</div>
	@if($__ranked)
		<div class="rank-window" role="group" aria-label="Top 10 lists">
			<span class="rank-window-label">Top 10</span>
			<span class="rank-window-opts">
				<button type="button" data-rank-window="recent" aria-pressed="true">{{ ucfirst(rankingWindowLabel()) }}</button>
				<button type="button" data-rank-window="all" aria-pressed="false">All time</button>
			</span>
		</div>
	@endif
	<div class="shelf-explorer">
		<div class="shelf-viewport">
		<div class="shelf-cols">
			@foreach($__shelves as $i => $shelf)
				<div class="shelf-col" data-idx="{{ $i }}">
					<div class="shelf-col-head">{{ $shelf['label'] }}@if(!empty($shelf['ranked']))<small class="shelf-col-when"><span data-window-only="recent">{{ ucfirst(rankingWindowLabel()) }}</span><span data-window-only="all">All time</span></small>@endif</div>
					@foreach(!empty($shelf['ranked']) ? ['recent' => $shelf['items'], 'all' => $shelf['allTime']] : ['' => $shelf['items']] as $__window => $__items)
					<div class="shelf-col-body"@if($__window) data-window-only="{{ $__window }}"@endif>
						@if(!count($__items))
							{{-- Only the last 90 days can be empty: a shelf with nothing in all time is left out. --}}
							<p class="shelf-empty">{{ $shelf['none'] }}<br><button type="button" class="shelf-empty-btn" data-rank-window="all">See all time</button></p>
						@endif
						@php
							// Three rows (fewer for a short shelf), read left to right and then down, as a ranking is read;
							// a cover left over goes on an upper row (10 -> 4, 3, 3).
							$__n = count($__items); $__r = min(3, $__n); $__rows = []; $__at = 0;
							for ($__k = 0; $__k < $__r; $__k++) {
								$__len = intdiv($__n, $__r) + ($__k < $__n % $__r ? 1 : 0);
								$__rows[] = array_slice($__items, $__at, $__len);
								$__at += $__len;
							}
						@endphp
						@foreach($__rows as $rowItems)
							<div class="col-scroll-wrap">
								<button type="button" class="row-arrow prev" aria-label="Scroll left" tabindex="-1" hidden>&#8249;</button>
								<div class="col-scroll">
									<div class="col-row">
										@foreach($rowItems as $it)
											@php
												$__won = isset($shelf['ranked']) ? [] : recordMedals($it['catalog_id'] ?? 0);   // medals: type shelves only
												$__lines = array_map(fn ($m) => '#' . $m['rank'] . ' ' . $m['list'], $__won);
												$__tip = $it['title'] . (!empty($it['detail']) ? ' — ' . $it['detail'] : '') . ($__lines ? ' — ' . implode(', ', $__lines) : '');
											@endphp
											<div class="col-book{{ empty($it['url']) ? ' no-link' : '' }}">
												@if(!empty($shelf['ranked']))<span class="col-rank {{ ['r1', 'r2', 'r3'][$it['rank'] - 1] ?? 'rn' }}" aria-hidden="true">@if($it['rank'] === 1)<i class="rk-crown"></i>@endif<b>{{ $it['rank'] }}</b></span>@endif
												@include('opac.partials.rank-medals', ['medals' => $__won, 'decorative' => true])   {{-- named by the hover text --}}
												{{-- A book without a record page is a plain picture, not a dead link. --}}
												@if(!empty($it['url']))<a class="col-link" href="{{ $it['url'] }}" title="{{ $__tip }}">@else<span class="col-link" title="{{ $__tip }}">@endif
													<img loading="lazy" src="{{ $it['thumb'] }}" alt="{{ $it['title'] }}">
												@if(!empty($it['url']))</a>@else</span>@endif
												@if(!empty($it['detail']))<span class="col-detail">{{ $it['detail'] }}</span>@elseif($__lines)<span class="col-detail">{!! implode('<br>', array_map('e', $__lines)) !!}</span>@endif
												@if(!empty($it['catalog_id']))
													@include('opac.partials.save-star', ['id' => $it['catalog_id'], 'title' => $it['title']])
												@endif
											</div>
										@endforeach
									</div>
								</div>
								<button type="button" class="row-arrow next" aria-label="Scroll right" tabindex="-1" hidden>&#8250;</button>
							</div>
						@endforeach
					</div>
					@endforeach
				</div>
			@endforeach
		</div>
		</div>
	</div>
</section>
<script nonce="{{ cspNonce() }}" type="text/javascript">
(function () {
	var root = document.querySelector('.shelf-browser');
	if (!root) return;
	var viewport = root.querySelector('.shelf-viewport');
	var track = root.querySelector('.shelf-cols');
	if (!track || !viewport) return;
	var realCols = [].slice.call(track.children);
	var m = realCols.length;
	if (!m) return;
	var prev = root.querySelector('.shelf-page.prev');
	var next = root.querySelector('.shelf-page.next');
	var GAP = 20, PEEKF = 0.30, start = 0, animating = false;

	// Infinite loop: clone the first/last shelves at each end so paging past an edge
	// slides seamlessly and wraps around. (Only when there's more than one shelf.)
	var LOOP = m > 1;
	var CLONES = Math.min(3, m);   // >= the max shelves ever visible at once
	if (LOOP) {
		var frag = document.createDocumentFragment(), i;
		for (i = 0; i < CLONES; i++) {                                   // append clones of the FIRST shelves
			var a = realCols[i].cloneNode(true); a.classList.add('shelf-clone'); frag.appendChild(a);
		}
		track.appendChild(frag);
		for (i = CLONES - 1; i >= 0; i--) {                             // prepend clones of the LAST shelves
			var p = realCols[m - CLONES + i].cloneNode(true); p.classList.add('shelf-clone'); track.insertBefore(p, track.firstChild);
		}
	}
	var cols = [].slice.call(track.children);
	var base = LOOP ? CLONES : 0;   // physical index of real shelf 0

	// Per-row horizontal arrows on every column (real + clone).
	var rowUpds = [];
	[].slice.call(root.querySelectorAll('.col-scroll-wrap')).forEach(function (wrap) {
		var sc = wrap.querySelector('.col-scroll');
		var rp = wrap.querySelector('.row-arrow.prev');
		var rn = wrap.querySelector('.row-arrow.next');
		if (!sc || !rp || !rn) return;
		function updRow() {
			var overflow = sc.scrollWidth > sc.clientWidth + 4;
			rp.hidden = rn.hidden = !overflow;
			if (!overflow) return;
			var mx = sc.scrollWidth - sc.clientWidth - 2;
			rp.style.visibility = sc.scrollLeft <= 2 ? 'hidden' : 'visible';
			rn.style.visibility = sc.scrollLeft >= mx ? 'hidden' : 'visible';
		}
		rp.addEventListener('click', function () { sc.scrollBy({ left: -sc.clientWidth * 0.8, behavior: 'smooth' }); });
		rn.addEventListener('click', function () { sc.scrollBy({ left: sc.clientWidth * 0.8, behavior: 'smooth' }); });
		sc.addEventListener('scroll', updRow);
		rowUpds.push(updRow);
		wrap._updRow = updRow;
	});
	function updRows() { rowUpds.forEach(function (fn) { fn(); }); }

	// Top-10 window: the switch, or an empty list's "See all time", shows the last 90 days or all time on the top-10
	// shelves. The rows just shown are measured for their arrows (hidden, they measured nothing). "See all time"
	// disappears as it is used, so the keyboard focus goes to the switch.
	root.addEventListener('click', function (e) {
		var btn = e.target.closest('[data-rank-window]');
		if (!btn) return;
		var all = btn.getAttribute('data-rank-window') === 'all';
		root.classList.toggle('is-all-time', all);
		[].slice.call(root.querySelectorAll('.rank-window [data-rank-window]')).forEach(function (b) {
			b.setAttribute('aria-pressed', String((b.getAttribute('data-rank-window') === 'all') === all));
		});
		if (btn.classList.contains('shelf-empty-btn')) root.querySelector('.rank-window [data-rank-window="all"]').focus({ preventScroll: true });
		updRows();
	});

	// The covers are lazy-loaded and have no fixed size, so a row only overflows (and needs its
	// arrows) once its images arrive — after the first check ran. Re-check as they load / as the
	// row changes size; previously the arrows stayed hidden on rows that did overflow.
	[].slice.call(root.querySelectorAll('.col-scroll-wrap')).forEach(function (wrap) {
		var upd = wrap._updRow;
		if (!upd) return;
		[].slice.call(wrap.querySelectorAll('img')).forEach(function (img) {
			if (!img.complete) { img.addEventListener('load', upd); img.addEventListener('error', upd); }
		});
		if (window.ResizeObserver) {
			var ro = new ResizeObserver(function () { upd(); });
			ro.observe(wrap.querySelector('.col-scroll'));
			var row = wrap.querySelector('.col-row'); if (row) ro.observe(row);
		}
	});
	window.addEventListener('load', updRows);

	function windowSize() {
		var w = window.innerWidth;
		return w < 768 ? 1 : (w < 1100 ? 2 : 3);   // max 3 shelves at once
	}

	var curOffset = 0;   // the track's resting offset (swipe-to-drag starts from here)
	function setTransform(offset, animate) {
		curOffset = offset;
		if (!animate) track.style.transition = 'none';
		track.style.transform = 'translateX(' + (-offset) + 'px)';
		if (!animate) { void track.offsetWidth; track.style.transition = ''; }   // restore transition after reflow
	}
	// When every shelf fits, the paging arrows stay in place but disabled (rather than vanishing),
	// so the layout is stable and it's clear there's nothing more to page to.
	function showArrows(enabled) {
		[prev, next].forEach(function (btn) {
			if (!btn) return;
			btn.disabled = !enabled;
			btn.title = enabled ? '' : 'All shelves are shown';
		});
	}

	function refresh(animate) {
		var n = windowSize();
		var vp = viewport.clientWidth;

		if (!LOOP || m <= n) {
			// Every shelf fits — fill the width, no peek, no paging.
			var cwFit = (vp - (m - 1) * GAP) / m;
			cols.forEach(function (c) { c.style.width = cwFit + 'px'; });
			setTransform(base * (cwFit + GAP), animate);
			showArrows(false);
			updRows();
			return;
		}

		var physical = base + start;   // start may be -1..m transiently while wrapping

		if (n === 1) {
			// Phones: one shelf fills the whole width (no peeking neighbours); swipe or use the arrows.
			cols.forEach(function (c) { c.style.width = vp + 'px'; });
			setTransform(physical * (vp + GAP), animate);
			showArrows(true);
			updRows();
			return;
		}

		// Looping + both-side peek: narrow the columns so ~PEEKF of each neighbour shows.
		var colW = (vp - (n + 1) * GAP) / (n + 2 * PEEKF);
		var PEEK = PEEKF * colW;
		cols.forEach(function (c) { c.style.width = colW + 'px'; });
		var step = colW + GAP;

		setTransform(physical * step - GAP - PEEK, animate);
		showArrows(true);
		updRows();
	}

	function go(delta) {
		if (animating) return;   // ignore clicks mid-slide
		animating = true;
		start += delta;
		refresh(true);
		setTimeout(function () {
			if (start < 0 || start > m - 1) {   // slid onto a clone -> jump to the matching real shelf
				start = ((start % m) + m) % m;
				refresh(false);
			}
			animating = false;
		}, 470);
	}
	if (prev) prev.addEventListener('click', function () { go(-1); });
	if (next) next.addEventListener('click', function () { go(1); });

	// ---- Swipe between shelves (phones) -------------------------------------------------------
	// A horizontal swipe anywhere on the shelf moves to the previous / next shelf: the shelf follows
	// the finger, then pages if the swipe was far or fast enough, else springs back. Vertical swipes
	// scroll the page (CSS touch-action: pan-y). Book rows scroll with their arrows.
	(function () {
		var x0, y0, t0, dx, mode;   // mode: null (undecided) | 'shelf' | 'page'
		var swipedAt = 0;           // a swipe that started on a book must not also open it
		viewport.addEventListener('touchstart', function (e) {
			if (windowSize() !== 1 || !LOOP || animating || e.touches.length !== 1) { mode = 'page'; return; }
			x0 = e.touches[0].clientX; y0 = e.touches[0].clientY; t0 = Date.now(); dx = 0; mode = null;
		}, { passive: true });
		viewport.addEventListener('touchmove', function (e) {
			if (mode === 'page') return;
			var mx = e.touches[0].clientX - x0, my = e.touches[0].clientY - y0;
			if (mode === null) {
				if (Math.abs(mx) < 8 && Math.abs(my) < 8) return;            // not decided yet
				if (Math.abs(my) > Math.abs(mx)) { mode = 'page'; return; }   // vertical: page scroll
				mode = 'shelf';
				track.style.transition = 'none';
			}
			e.preventDefault();                                              // we own this gesture
			dx = mx;
			track.style.transform = 'translateX(' + (-curOffset + dx) + 'px)';
		}, { passive: false });
		function end() {
			if (mode !== 'shelf') { mode = null; return; }
			mode = null;
			swipedAt = Date.now();
			track.style.transition = '';
			var fast = Math.abs(dx) > 30 && (Date.now() - t0) < 280;
			if (Math.abs(dx) > viewport.clientWidth * 0.2 || fast) go(dx < 0 ? 1 : -1);
			else refresh(true);                                              // spring back
		}
		viewport.addEventListener('touchend', end);
		viewport.addEventListener('touchcancel', end);
		viewport.addEventListener('click', function (e) {
			if (Date.now() - swipedAt < 400) { e.preventDefault(); e.stopPropagation(); }
		}, true);
	})();

	var rt;
	window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(function () { refresh(false); }, 120); });
	refresh(false);
})();
</script>
@endif
