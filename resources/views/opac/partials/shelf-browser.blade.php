@php $__shelves = getShelves(45); @endphp
@if(count($__shelves))
<style>
	/* ---- eLibrary teal color scheme: multi-column shelf explorer ---- */
	.shelf-browser { padding: 52px 24px 64px; background: radial-gradient(130% 120% at 50% -10%, #f1faf8 0%, #dcefeb 62%, #cfe8e3 100%); }
	.shelf-browser .shelf-heading { text-align:center; margin-bottom:22px; }
	.shelf-browser .shelf-heading .orn { color:#00a496; font-size:20px; }
	.shelf-browser .shelf-heading h2 { font-family:Georgia,"Palatino Linotype",serif; font-size:26px; letter-spacing:2px; text-transform:uppercase; color:#0a5f56; margin:4px 0 0; font-weight:700; }

	.shelf-explorer { position:relative; max-width:1680px; margin:0 auto; padding:0 54px; }
	.shelf-cols { display:flex; gap:20px; align-items:flex-start; }
	.shelf-col { flex:1 1 0; min-width:0; display:none; border-radius:8px; overflow:hidden; align-self:stretch;
		border:1px solid #0a6e63; box-shadow: inset 0 0 0 3px rgba(255,255,255,.16), 0 24px 44px -26px rgba(4,50,45,.55);
		background: repeating-linear-gradient(90deg, rgba(0,0,0,.05) 0 3px, rgba(255,255,255,.03) 3px 9px), linear-gradient(#0a8378,#05564d); }
	.shelf-col.show { display:flex; flex-direction:column; }
	.shelf-col-head { padding:11px 14px; text-align:center; color:#eafaf7; background:linear-gradient(#064d44,#0a7268);
		border-bottom:2px solid #4fd0c2; font-family:Georgia,"Palatino Linotype",serif; font-size:16px; letter-spacing:.5px;
		white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
	.shelf-col-body { padding:4px 0 10px; }

	.col-scroll { overflow-x:auto; overflow-y:hidden; padding:10px 14px; }
	.col-scroll::-webkit-scrollbar { height:8px; }
	.col-scroll::-webkit-scrollbar-track { background:rgba(0,0,0,.22); }
	.col-scroll::-webkit-scrollbar-thumb { background:linear-gradient(#5fd6c8,#00a496); border-radius:5px; }
	.col-row { position:relative; display:flex; gap:12px; min-width:100%; width:max-content; align-items:flex-end;
		justify-content:safe center; padding:12px 4px; }
	.col-row::after { content:""; position:absolute; left:0; right:0; bottom:0; height:11px;
		background:linear-gradient(#0a7268,#064d44); border-top:2px solid #4fd0c2; box-shadow:0 7px 10px -6px rgba(0,0,0,.55); }
	.col-book { flex:0 0 auto; display:block; line-height:0; position:relative; z-index:1; transition:transform .14s ease; }
	.col-book img { height:106px; width:auto; max-width:none; border-radius:2px; background:#fff;
		box-shadow:0 2px 3px rgba(0,0,0,.4), 0 10px 12px -8px rgba(0,0,0,.6); }
	.col-book:hover { transform:translateY(-4px); z-index:2; }
	.col-book.no-link { cursor:default; }

	.shelf-page { position:absolute; top:46%; transform:translateY(-50%); width:44px; height:44px; border-radius:50%; z-index:6;
		border:1px solid #0a6e63; cursor:pointer; color:#053f39; font-size:24px; line-height:1;
		background:radial-gradient(circle at 35% 30%, #6fe0d2, #00a496 70%);
		display:flex; align-items:center; justify-content:center; box-shadow: inset 0 1px 1px rgba(255,255,255,.55), 0 2px 5px rgba(0,0,0,.35); }
	.shelf-page.prev { left:2px; } .shelf-page.next { right:2px; }
	.shelf-page:hover { filter:brightness(1.07); } .shelf-page:active { transform:translateY(calc(-50% + 1px)); }
	.shelf-page:disabled { opacity:.35; cursor:default; filter:none; }

	@media (max-width:1100px){ .col-book img { height:96px; } }
	@media (max-width:767px){ .shelf-explorer { padding:0 42px; } .shelf-page { width:36px; height:36px; font-size:20px; } }
</style>
<section class="shelf-browser">
	<div class="shelf-heading">
		<span class="orn">&#10087;</span>
		<h2>Browse the Shelves</h2>
	</div>
	<div class="shelf-explorer">
		<button type="button" class="shelf-page prev" aria-label="Previous shelves">&#8249;</button>
		<div class="shelf-cols">
			@foreach($__shelves as $i => $shelf)
				<div class="shelf-col" data-idx="{{ $i }}">
					<div class="shelf-col-head">{{ $shelf['label'] }}</div>
					<div class="shelf-col-body">
						@php $__rows = array_chunk($shelf['items'], (int) ceil(max(1, count($shelf['items'])) / 3)); @endphp
						@foreach($__rows as $rowItems)
							<div class="col-scroll">
								<div class="col-row">
									@foreach($rowItems as $it)
										<a class="col-book{{ empty($it['url']) ? ' no-link' : '' }}" href="{{ $it['url'] ?? '#' }}" @if(empty($it['url'])) onclick="return false" @endif title="{{ $it['title'] }}">
											<img loading="lazy" src="{{ $it['thumb'] }}" alt="{{ $it['title'] }}">
										</a>
									@endforeach
								</div>
							</div>
						@endforeach
					</div>
				</div>
			@endforeach
		</div>
		<button type="button" class="shelf-page next" aria-label="Next shelves">&#8250;</button>
	</div>
</section>
<script type="text/javascript">
(function () {
	var root = document.querySelector('.shelf-browser');
	if (!root) return;
	var cols = [].slice.call(root.querySelectorAll('.shelf-col'));
	if (!cols.length) return;
	var prev = root.querySelector('.shelf-page.prev');
	var next = root.querySelector('.shelf-page.next');
	var start = 0;

	function windowSize() {
		var w = window.innerWidth;
		return w < 768 ? 1 : (w < 1100 ? 2 : 3);   // max 3 shelves at once
	}

	function render() {
		var n = windowSize();
		var maxStart = Math.max(0, cols.length - n);
		if (start > maxStart) start = maxStart;
		if (start < 0) start = 0;
		cols.forEach(function (c, k) { c.classList.toggle('show', k >= start && k < start + n); });
		if (prev) prev.disabled = start <= 0;
		if (next) next.disabled = start >= maxStart;
		var hideNav = cols.length <= n;
		if (prev) prev.style.display = hideNav ? 'none' : '';
		if (next) next.style.display = hideNav ? 'none' : '';
	}

	if (prev) prev.addEventListener('click', function () { start = Math.max(0, start - 1); render(); });
	if (next) next.addEventListener('click', function () { start = start + 1; render(); });
	window.addEventListener('resize', render);
	render();
})();
</script>
@endif
