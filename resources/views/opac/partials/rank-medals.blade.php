{{-- A record's medals from the home page's top-10 lists (recordMedals): one per list it ranks in, so at most three;
     gold #1, silver #2, bronze #3, plain #4-10. Sits at the upper left of a cover whose wrapper is position: relative.
       $medals      recordMedals($catalogId)
       $size        'small' (search results) | 'large' (record page) | omitted
       $decorative  true where the same words are already on the page (the shelves' hover text)
     The styles come with the first medals on a page, so pages without any carry none. --}}
@if(!empty($medals))
@once
<style>
	.rank-medals { position:absolute; top:-8px; left:-8px; z-index:3; display:flex; gap:3px; }
	.rank-medals.is-decorative { pointer-events:none; }
	.medal { position:relative; flex:none; width:24px; height:24px; border-radius:50%; display:grid; place-items:center;
		font:700 11px/1 Georgia,"Palatino Linotype",serif; color:#3b2a05; box-shadow:0 2px 4px rgba(0,0,0,.55), inset 0 0 0 1.5px rgba(255,255,255,.6); }
	.medal::before { content:""; position:absolute; z-index:-1; left:50%; top:-8px; width:12px; height:11px; translate:-50% 0;
		background:linear-gradient(90deg, #c32148 0 50%, #1f5fa8 50%); clip-path:polygon(0 0, 100% 0, 80% 100%, 20% 100%); }
	.m-gold   { background:radial-gradient(circle at 35% 30%, #fff7cc, #f2c230 45%, #a37406); }
	.m-silver { background:radial-gradient(circle at 35% 30%, #ffffff, #c8ced6 45%, #7b838e); }
	.m-bronze { background:radial-gradient(circle at 35% 30%, #ffe0c2, #cd7f32 45%, #7a4313); color:#fff; }
	.m-plain  { background:radial-gradient(circle at 35% 30%, #c9efe9, #1f8f82 45%, #0a5f56); color:#fff; }
	.rank-medals.is-small { top:-5px; left:-5px; gap:2px; }
	.rank-medals.is-small .medal { width:18px; height:18px; font-size:9px; }
	.rank-medals.is-small .medal::before { top:-6px; width:9px; height:8px; }
	.rank-medals.is-large { top:-10px; left:-10px; gap:4px; }
	.rank-medals.is-large .medal { width:30px; height:30px; font-size:13px; }
	.rank-medals.is-large .medal::before { top:-10px; width:15px; height:13px; }
</style>
@endonce
@php $__words = array_map(fn ($m) => '#' . $m['rank'] . ' ' . $m['list'] . ', ' . $m['when'], $medals); @endphp
<span class="rank-medals{{ !empty($size) ? ' is-' . $size : '' }}{{ !empty($decorative) ? ' is-decorative' : '' }}"
	@if(!empty($decorative)) aria-hidden="true" @else role="img" aria-label="{{ implode(', ', $__words) }}" @endif>@foreach($medals as $__i => $m)<span class="medal {{ ['m-gold', 'm-silver', 'm-bronze'][$m['rank'] - 1] ?? 'm-plain' }}" title="{{ $__words[$__i] }}">{{ $m['rank'] }}</span>@endforeach</span>
@endif
