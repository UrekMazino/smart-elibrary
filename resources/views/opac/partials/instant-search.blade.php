{{-- Reusable Meilisearch instant search (search-as-you-type) for OPAC search boxes.
     Usage: add class "js-instant" to a text <input> inside a <form>, then
     @include('opac.partials.instant-search') once inside that page's @section('js').
     Talks only to /search/suggest (Meilisearch stays server-side). --}}
<style>
	.instant-results { position: absolute; z-index: 20000; background: #fff; border: 1px solid #e1e6ea; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,.18); overflow: hidden; display: none; }
	.instant-list { max-height: 380px; overflow-y: auto; }
	.instant-row { display: flex; gap: 10px; padding: 9px 12px; text-decoration: none; color: #222; border-bottom: 1px solid #f0f2f4; }
	.instant-row:last-child { border-bottom: 0; }
	.instant-row:hover, .instant-row.active { background: #f1faf7; }
	.instant-thumb { width: 34px; height: 46px; object-fit: cover; border-radius: 3px; flex: 0 0 auto; box-shadow: 0 1px 3px rgba(0,0,0,.22); }
	.instant-text { min-width: 0; }
	.instant-title { font-size: 13.5px; font-weight: 600; color: #0f6ea3; line-height: 1.25; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }
	.instant-meta { font-size: 11.5px; color: #777; margin-top: 2px; }
	.instant-type, .instant-year { margin-left: 8px; }
	.instant-empty, .instant-loading { padding: 14px; color: #888; font-size: 13px; text-align: center; }
	.instant-seeall { display: block; padding: 9px 12px; text-align: center; font-size: 12.5px; font-weight: 700; color: #029978; background: #f6f9f8; text-decoration: none; border-top: 1px solid #eef0f2; }
	.instant-seeall:hover { background: #eef7f4; }
</style>

<script type="text/javascript">
(function () {
	var endpoint = "{{ url('/search/suggest') }}";
	function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

	$('input.js-instant').each(function () {
		var input = this;
		var $input = $(input).attr('autocomplete', 'off');
		var $form  = $input.closest('form');

		var $box = $('<div class="instant-results"><div class="instant-list"></div><a href="#" class="instant-seeall"></a></div>').appendTo('body');
		var $list = $box.find('.instant-list');
		var $seeall = $box.find('.instant-seeall');
		var timer = null, lastQ = null, activeIdx = -1;

		function position() {
			var r = input.getBoundingClientRect();
			$box.css({ top: (r.bottom + window.pageYOffset + 6) + 'px', left: (r.left + window.pageXOffset) + 'px', width: r.width + 'px' });
		}
		function open()  { position(); $box.show(); }
		function close() { $box.hide(); activeIdx = -1; }

		function render(data) {
			activeIdx = -1;
			if (!data.hits || !data.hits.length) {
				$list.html('<div class="instant-empty">No matches for &ldquo;' + esc(data.query) + '&rdquo;</div>');
				$seeall.hide(); open(); return;
			}
			var html = '';
			data.hits.forEach(function (h) {
				html += '<a class="instant-row" href="' + esc(h.url) + '">'
				     +    (h.thumb ? '<img class="instant-thumb" src="' + esc(h.thumb) + '" alt="">' : '')
				     +    '<div class="instant-text">'
				     +      '<div class="instant-title" title="' + esc(h.title).replace(/"/g, '&quot;') + '">' + esc(h.title) + '</div>'
				     +      '<div class="instant-meta">'
				     +        (h.author ? esc(h.author) : '')
				     +        (h.type ? '<span class="instant-type">' + esc(h.type) + '</span>' : '')
				     +        (h.year ? '<span class="instant-year">' + esc(h.year) + '</span>' : '')
				     +      '</div>'
				     +    '</div>'
				     +  '</a>';
			});
			$list.html(html);
			$seeall.text('See all ' + data.total + ' result' + (data.total == 1 ? '' : 's')).show();
			open();
		}

		function run(q) {
			$.getJSON(endpoint, { q: q, type: 0 }).done(render).fail(close);
		}

		$input.on('input', function () {
			var q = $.trim($input.val());
			if (q === lastQ) return;
			lastQ = q;
			clearTimeout(timer);
			if (q.length < 2) { close(); return; }
			$list.html('<div class="instant-loading">Searching&hellip;</div>');
			$seeall.hide(); open();
			timer = setTimeout(function () { run(q); }, 160);
		});

		// "See all N results" -> run the real Meilisearch on /results
		$seeall.on('click', function (e) { e.preventDefault(); if ($form.length) $form.trigger('submit'); });

		$input.on('keydown', function (e) {
			if (e.key === 'Escape') { close(); return; }
			var $rows = $list.find('.instant-row');
			if (!$rows.length) return;
			if (e.key === 'ArrowDown')      { e.preventDefault(); activeIdx = Math.min(activeIdx + 1, $rows.length - 1); }
			else if (e.key === 'ArrowUp')   { e.preventDefault(); activeIdx = Math.max(activeIdx - 1, 0); }
			else if (e.key === 'Enter' && activeIdx >= 0) { e.preventDefault(); window.location = $rows.eq(activeIdx).attr('href'); return; }
			else return;
			$rows.removeClass('active').eq(activeIdx).addClass('active');
		});

		$input.on('focus', function () {
			if (lastQ && lastQ.length >= 2 && $list.children().length) open();
		});

		// Close on outside click
		$(document).on('mousedown', function (e) {
			if (e.target !== input && !$(e.target).closest($box).length) close();
		});

		// Keep the dropdown aligned to the (possibly sticky) search bar
		$(window).on('scroll resize', function () { if ($box.is(':visible')) position(); });
	});
})();
</script>
