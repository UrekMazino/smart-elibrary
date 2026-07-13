@php $__shelves = getShelves(45); @endphp
@if(count($__shelves))
<style>
	/* ---- Wood + gold category shelf browser (OpenLibrary explore concept) ---- */
	.shelf-browser { padding: 58px 0 66px; background: radial-gradient(130% 120% at 50% -10%, #f3ead2 0%, #e9dab6 60%, #e2cfa4 100%); }
	.shelf-browser .container { max-width: 100%; padding-left: 30px; padding-right: 30px; }
	.shelf-browser .shelf-heading { text-align:center; margin-bottom:22px; }
	.shelf-browser .shelf-heading .orn { color:#b58a20; font-size:20px; }
	.shelf-browser .shelf-heading h2 { font-family:Georgia,"Palatino Linotype",serif; font-size:26px; letter-spacing:2px; text-transform:uppercase; color:#4a3a25; margin:4px 0 0; font-weight:700; }
	.shelf-frame { max-width:none; margin:0 auto; border-radius:9px; overflow:hidden; border:1px solid #a9812b;
		box-shadow: inset 0 0 0 3px rgba(201,162,39,.35), 0 30px 60px -30px rgba(50,32,10,.7);
		background: repeating-linear-gradient(90deg, rgba(0,0,0,.05) 0 3px, rgba(255,255,255,.02) 3px 9px), linear-gradient(#5a3d26,#432c1a); }
	.shelf-head { display:flex; align-items:center; gap:14px; padding:12px 18px; color:#f4e7c6;
		background:linear-gradient(#3c2716,#583a24); border-bottom:2px solid #c9a227;
		box-shadow: 0 2px 0 rgba(201,162,39,.4); }
	.shelf-label { flex:1 1 auto; text-align:center; margin:0; font-family:Georgia,"Palatino Linotype",serif;
		font-size:20px; letter-spacing:1px; color:#f7ebca; text-shadow:0 1px 0 rgba(0,0,0,.55); white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
	.shelf-count { flex:0 0 auto; font-size:12px; font-weight:700; color:#e8cf7e; min-width:46px; text-align:center; }
	.shelf-nav { flex:0 0 auto; width:40px; height:40px; border-radius:50%; border:1px solid #a9812b; cursor:pointer;
		background:radial-gradient(circle at 35% 30%, #f4e09a, #c9a227 70%); color:#3a2610; font-size:22px; line-height:1;
		display:flex; align-items:center; justify-content:center; box-shadow: inset 0 1px 1px rgba(255,255,255,.6), 0 2px 4px rgba(0,0,0,.4); }
	.shelf-nav:hover { filter:brightness(1.08); }
	.shelf-nav:active { transform:translateY(1px); }
	.shelf-panel { display:none; }
	.shelf-panel.active { display:block; }
	.shelf-scroll { overflow-x:auto; overflow-y:hidden; padding:14px 20px 20px; }
	.shelf-scroll::-webkit-scrollbar { height:10px; }
	.shelf-scroll::-webkit-scrollbar-track { background:rgba(0,0,0,.25); }
	.shelf-scroll::-webkit-scrollbar-thumb { background:linear-gradient(#e0c56f,#c9a227); border-radius:6px; }
	.shelf-row { position:relative; display:flex; gap:16px; width:max-content; align-items:flex-end;
		padding:16px 8px 16px; margin-bottom:6px; }
	.shelf-row::after { content:""; position:absolute; left:0; right:0; bottom:0; height:14px;
		background:linear-gradient(#7c5533 0%, #5f3f25 60%, #4a3019 100%);
		border-top:2px solid #c9a227; box-shadow:0 8px 12px -7px rgba(0,0,0,.7); border-radius:1px; }
	.shelf-book { flex:0 0 auto; display:block; line-height:0; position:relative; z-index:1; transition:transform .14s ease; }
	.shelf-book img { height:132px; width:auto; max-width:none; border-radius:2px; background:#fff;
		box-shadow: 0 2px 3px rgba(0,0,0,.45), 0 12px 14px -9px rgba(0,0,0,.7); }
	.shelf-book:hover { transform:translateY(-5px); z-index:2; }
	.shelf-book:hover img { box-shadow: 0 4px 6px rgba(0,0,0,.5), 0 18px 20px -10px rgba(0,0,0,.8); }
	@media (max-width:991px){ .shelf-book img { height:116px; } .shelf-label{ font-size:17px; } }
	@media (max-width:575px){ .shelf-book img { height:98px; } .shelf-nav{ width:34px; height:34px; font-size:19px; } }
</style>
<section class="shelf-browser">
	<div class="container">
		<div class="shelf-heading">
			<span class="orn">&#10087;</span>
			<h2>Browse the Shelves</h2>
		</div>
		<div class="shelf-frame">
			<div class="shelf-head">
				<button type="button" class="shelf-nav prev" aria-label="Previous shelf">&#8249;</button>
				<h2 class="shelf-label">{{ $__shelves[0]['label'] }}</h2>
				<span class="shelf-count">1 / {{ count($__shelves) }}</span>
				<button type="button" class="shelf-nav next" aria-label="Next shelf">&#8250;</button>
			</div>
			<div class="shelf-stage">
				@foreach($__shelves as $i => $shelf)
					<div class="shelf-panel {{ $i === 0 ? 'active' : '' }}" data-label="{{ $shelf['label'] }}">
						<div class="shelf-scroll">
							@php $__rows = array_chunk($shelf['items'], (int) ceil(max(1, count($shelf['items'])) / 3)); @endphp
							@foreach($__rows as $rowItems)
								<div class="shelf-row">
									@foreach($rowItems as $it)
										<a class="shelf-book" href="{{ $it['url'] ?? '#' }}" @if(empty($it['url'])) onclick="return false" @endif title="{{ $it['title'] }}">
											<img loading="lazy" src="{{ $it['thumb'] }}" alt="{{ $it['title'] }}">
										</a>
									@endforeach
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
	var panels = [].slice.call(root.querySelectorAll('.shelf-panel'));
	if (!panels.length) return;
	var label = root.querySelector('.shelf-label');
	var count = root.querySelector('.shelf-count');
	var idx = 0;

	function show(i) {
		idx = (i + panels.length) % panels.length;
		panels.forEach(function (p, k) { p.classList.toggle('active', k === idx); });
		if (label) label.textContent = panels[idx].getAttribute('data-label');
		if (count) count.textContent = (idx + 1) + ' / ' + panels.length;
		var sc = panels[idx].querySelector('.shelf-scroll'); if (sc) sc.scrollLeft = 0;
	}

	var prev = root.querySelector('.shelf-nav.prev');
	var next = root.querySelector('.shelf-nav.next');
	if (prev) prev.addEventListener('click', function () { show(idx - 1); });
	if (next) next.addEventListener('click', function () { show(idx + 1); });
})();
</script>
@endif
