@php $__shelves = getShelves(45); @endphp
@if(count($__shelves))
<style>
	/* ---- eLibrary teal color scheme: multi-column shelf explorer ---- */
	.shelf-browser { padding: 52px 24px 64px; background: radial-gradient(130% 120% at 50% -10%, #f1faf8 0%, #dcefeb 62%, #cfe8e3 100%); }
	/* Heading now carries the prev/next paging arrows, flanking the title. */
	.shelf-browser .shelf-heading { display:flex; align-items:center; justify-content:center; gap:20px; margin-bottom:22px; }
	.shelf-browser .shelf-title { text-align:center; }
	.shelf-browser .shelf-heading .orn { color:#00a496; font-size:20px; }
	.shelf-browser .shelf-heading h2 { font-family:Georgia,"Palatino Linotype",serif; font-size:26px; letter-spacing:2px; text-transform:uppercase; color:#0a5f56; margin:4px 0 0; font-weight:700; }

	.shelf-explorer { position:relative; max-width:1900px; margin:0 auto; padding:0 10px; }
	.shelf-viewport { overflow:hidden; }
	.shelf-cols { display:flex; gap:20px; align-items:flex-start; transition:transform .45s cubic-bezier(.4,0,.2,1); will-change:transform; }
	.shelf-col { flex:0 0 auto; min-width:0; display:flex; flex-direction:column; border-radius:8px; overflow:hidden; align-self:stretch;
		border:1px solid #0a6e63; box-shadow: inset 0 0 0 3px rgba(255,255,255,.16), 0 24px 44px -26px rgba(4,50,45,.55);
		background: repeating-linear-gradient(90deg, rgba(0,0,0,.05) 0 3px, rgba(255,255,255,.03) 3px 9px), linear-gradient(#0a8378,#05564d); }
	.shelf-col-head { padding:11px 14px; text-align:center; color:#eafaf7; background:linear-gradient(#064d44,#0a7268);
		border-bottom:2px solid #4fd0c2; font-family:Georgia,"Palatino Linotype",serif; font-size:16px; letter-spacing:.5px;
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
	.col-scroll-wrap:hover .row-arrow { opacity:1; }
	.row-arrow:hover { background:rgba(0,164,150,.85); }
	.row-arrow.prev { left:7px; } .row-arrow.next { right:7px; }
	.row-arrow[hidden] { display:none; }
	.col-row { position:relative; display:flex; gap:15px; min-width:100%; width:max-content; align-items:flex-end;
		justify-content:safe center; padding:38px 6px 26px; }
	/* Shelf board: a solid wooden ledge with a lit front edge + depth shadow under each row,
	   so every row reads as its own distinct shelf. */
	.col-row::after { content:""; position:absolute; left:0; right:0; bottom:0; height:17px;
		background:linear-gradient(#0e9284 0, #0a7268 45%, #063f39 100%);
		border-top:2px solid #7fe8db; box-shadow:0 2px 0 rgba(0,0,0,.35), 0 11px 13px -6px rgba(0,0,0,.62); }
	.col-book { flex:0 0 auto; display:block; line-height:0; position:relative; z-index:1; transition:transform .14s ease; }
	/* natural proportions, capped -> heights vary a bit like a real shelf; bottoms align via flex-end */
	.col-book img { display:block; width:auto; height:auto; max-height:186px; max-width:144px; border-radius:2px; background:#fff;
		box-shadow:0 3px 4px rgba(0,0,0,.45), 0 15px 18px -9px rgba(0,0,0,.8); }
	.col-book:hover { transform:translateY(-4px); z-index:2; }
	.col-book.no-link { cursor:default; }

	/* Minimalist paging arrows: thin flat outline circle that fills teal on hover. */
	.shelf-page { flex:0 0 auto; width:38px; height:38px; border-radius:50%;
		border:1.5px solid #14a091; background:transparent; cursor:pointer; color:#0a7268; font-size:18px; line-height:1;
		display:flex; align-items:center; justify-content:center; transition:background .16s ease, color .16s ease, border-color .16s ease; }
	.shelf-page:hover { background:#00a496; border-color:#00a496; color:#fff; }
	.shelf-page:active { transform:translateY(1px); }
	.shelf-page:disabled { opacity:.3; cursor:default; background:transparent; color:#0a7268; }

	@media (max-width:1100px){ .col-book img { max-height:162px; max-width:126px; } }
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
	<div class="shelf-explorer">
		<div class="shelf-viewport">
		<div class="shelf-cols">
			@foreach($__shelves as $i => $shelf)
				<div class="shelf-col" data-idx="{{ $i }}">
					<div class="shelf-col-head">{{ $shelf['label'] }}</div>
					<div class="shelf-col-body">
						@php $__rows = array_chunk($shelf['items'], (int) ceil(max(1, count($shelf['items'])) / 3)); @endphp
						@foreach($__rows as $rowItems)
							<div class="col-scroll-wrap">
								<button type="button" class="row-arrow prev" aria-label="Scroll left" tabindex="-1" hidden>&#8249;</button>
								<div class="col-scroll">
									<div class="col-row">
										@foreach($rowItems as $it)
											<a class="col-book{{ empty($it['url']) ? ' no-link' : '' }}" href="{{ $it['url'] ?? '#' }}" @if(empty($it['url'])) onclick="return false" @endif title="{{ $it['title'] }}">
												<img loading="lazy" src="{{ $it['thumb'] }}" alt="{{ $it['title'] }}">
											</a>
										@endforeach
									</div>
								</div>
								<button type="button" class="row-arrow next" aria-label="Scroll right" tabindex="-1" hidden>&#8250;</button>
							</div>
						@endforeach
					</div>
				</div>
			@endforeach
		</div>
		</div>
	</div>
</section>
<script type="text/javascript">
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
	});
	function updRows() { rowUpds.forEach(function (fn) { fn(); }); }

	function windowSize() {
		var w = window.innerWidth;
		return w < 768 ? 1 : (w < 1100 ? 2 : 3);   // max 3 shelves at once
	}

	function setTransform(offset, animate) {
		if (!animate) track.style.transition = 'none';
		track.style.transform = 'translateX(' + (-offset) + 'px)';
		if (!animate) { void track.offsetWidth; track.style.transition = ''; }   // restore transition after reflow
	}
	function showArrows(show) {
		var d = show ? '' : 'none';
		if (prev) { prev.style.display = d; prev.disabled = false; }
		if (next) { next.style.display = d; next.disabled = false; }
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

		// Looping + both-side peek: narrow the columns so ~PEEKF of each neighbour shows.
		var colW = (vp - (n + 1) * GAP) / (n + 2 * PEEKF);
		var PEEK = PEEKF * colW;
		cols.forEach(function (c) { c.style.width = colW + 'px'; });
		var step = colW + GAP;

		var physical = base + start;   // start may be -1..m transiently while wrapping
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

	var rt;
	window.addEventListener('resize', function () { clearTimeout(rt); rt = setTimeout(function () { refresh(false); }, 120); });
	refresh(false);
})();
</script>
@endif
