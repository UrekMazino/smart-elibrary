@extends('opac.layout.master')

@section('meta_title', 'AI search — DOST-PCAARRD eLibrary')
@section('robots', 'noindex, follow')


@section('css')
<style>
	/* ---- AI mode results — answer first, sources below ---- */
	#results { position: -webkit-sticky; position: sticky; top: 0; z-index: 1001; box-shadow: 0 3px 10px rgba(0,0,0,.15); }
	#breadcrumb.breadcrumb_inline { background: transparent; padding: 0; }
	@media (max-width: 767px) { #breadcrumb.breadcrumb_inline { padding: 0 0 12px; } }

	.ai-page { max-width: 820px; margin: 0 auto; padding: 34px 15px 60px; min-height: calc(100vh - 320px); }

	.ai-question { font-size: 26px; font-weight: 700; color: #222; line-height: 1.3; margin: 0 0 20px; }

	/* Preview banner — shown while answers are placeholders (service reports stub:true) */
	.ai-preview {
		display: flex; gap: 10px; align-items: flex-start;
		background: #fff8e8; border: 1px solid #f6e3bc; border-radius: 8px;
		padding: 11px 13px; font-size: 12.5px; color: #7a5b16; line-height: 1.5; margin-bottom: 16px;
	}
	.ai-preview b { font-weight: 700; }

	.ai-card { background: #fff; border: 1px solid #e5eceb; border-radius: 12px; box-shadow: 0 1px 3px rgba(0,0,0,.05); overflow: hidden; }
	.ai-card-head {
		display: flex; align-items: center; justify-content: space-between; gap: 12px;
		padding: 13px 18px; background: linear-gradient(90deg, #e7f5f3, #f4faf9); border-bottom: 1px solid #eef0f2;
	}
	.ai-badge { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; font-weight: 700; color: #00796b; }
	.ai-badge .spark { font-size: 15px; line-height: 1; }
	.ai-grounded { font-size: 11.5px; color: #6b7280; }

	.ai-body { padding: 20px 18px; font-size: 15.5px; line-height: 1.72; color: #2b3138; }
	.ai-body p { margin: 0 0 12px; }
	.ai-body p:last-child { margin-bottom: 0; }

	/* Inline citation chips [1] -> jump to the matching source */
	.ai-cite {
		display: inline-flex; align-items: center; justify-content: center; min-width: 18px; height: 18px;
		padding: 0 5px; margin: 0 1px; border-radius: 5px; background: #e4f1ef; color: #00796b;
		font-size: 11px; font-weight: 700; text-decoration: none; vertical-align: 1px; transition: background .12s, color .12s;
	}
	.ai-cite:hover { background: #00857a; color: #fff; }

	.ai-foot { padding: 11px 18px; border-top: 1px solid #f0f2f4; background: #fbfcfd; font-size: 11.5px; color: #8a94a6; display: flex; justify-content: space-between; gap: 10px; flex-wrap: wrap; }

	/* Loading skeleton */
	.sk { background: linear-gradient(90deg,#eef1f4 25%,#f6f8fa 37%,#eef1f4 63%); background-size: 400% 100%; animation: sk 1.3s ease infinite; border-radius: 5px; height: 13px; margin-bottom: 11px; }
	.sk.w90 { width: 90%; } .sk.w75 { width: 75%; } .sk.w60 { width: 60%; }
	@keyframes sk { 0% { background-position: 100% 50%; } 100% { background-position: 0 50%; } }
	.ai-thinking { display: flex; align-items: center; gap: 9px; font-size: 12.5px; color: #6b7280; margin-bottom: 15px; }
	.ai-dot { width: 6px; height: 6px; border-radius: 50%; background: #00a496; animation: pulse 1.2s ease-in-out infinite; }
	.ai-dot:nth-child(2) { animation-delay: .18s; } .ai-dot:nth-child(3) { animation-delay: .36s; }
	@keyframes pulse { 0%,100% { opacity: .25; transform: scale(.8); } 50% { opacity: 1; transform: scale(1); } }

	/* Notices: abstained / unavailable / error */
	.ai-notice { border-radius: 12px; padding: 22px 20px; text-align: center; border: 1px solid #e5eceb; background: #fff; }
	.ai-notice .icn { font-size: 30px; line-height: 1; margin-bottom: 10px; display: block; }
	.ai-notice h4 { font-size: 16px; font-weight: 700; color: #222; margin: 0 0 6px; }
	.ai-notice p { font-size: 13.5px; color: #6b7280; margin: 0 auto; max-width: 460px; line-height: 1.6; }
	.ai-notice.warn { background: #fffaf2; border-color: #f6e3bc; }

	/* Sources */
	.ai-sources-h { font-size: 15px; font-weight: 700; color: #222; margin: 30px 0 12px; }
	.ai-src { display: flex; gap: 14px; padding: 14px; border: 1px solid #eef0f2; border-radius: 10px; background: #fff; margin-bottom: 10px; scroll-margin-top: 90px; transition: border-color .15s, box-shadow .15s; }
	.ai-src:hover { border-color: #cfe6e2; box-shadow: 0 2px 10px rgba(0,0,0,.05); }
	.ai-src.flash { border-color: #00a496; box-shadow: 0 0 0 3px rgba(0,164,150,.14); }
	.ai-src-n { flex: 0 0 22px; height: 22px; border-radius: 6px; background: #e4f1ef; color: #00796b; font-size: 11px; font-weight: 700; display: flex; align-items: center; justify-content: center; }
	.ai-src-cover { flex: 0 0 52px; }
	.ai-src-cover img { width: 52px; height: 72px; object-fit: cover; border-radius: 3px; box-shadow: 0 1px 5px rgba(0,0,0,.2); }
	.ai-src-body { flex: 1; min-width: 0; }
	.ai-src-title { font-size: 14.5px; font-weight: 600; color: #0176d3; text-decoration: none; line-height: 1.3; }
	.ai-src-title:hover { text-decoration: underline; }
	.ai-src-meta { font-size: 12px; color: #8a8a8a; margin-top: 3px; }
	.ai-src-meta span + span::before { content: '·'; margin: 0 6px; color: #ccc; }
	.ai-src-snippet { font-size: 12.5px; color: #55606b; line-height: 1.55; margin-top: 7px; padding-left: 9px; border-left: 2px solid #e4f1ef; font-style: italic; }
	.ai-src-score { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; color: #8a94a6; margin-top: 6px; }
	.ai-bar { width: 44px; height: 4px; border-radius: 3px; background: #eef0f2; overflow: hidden; }
	.ai-bar i { display: block; height: 100%; background: #00a496; }

	/* Follow-up */
	.ai-followup { margin-top: 26px; }
	.ai-followup .input-group { box-shadow: 0 1px 3px rgba(0,0,0,.06); border-radius: 30px; overflow: hidden; border: 1px solid #dfe3e8; background: #fff; }
	.ai-followup input { border: 0; padding: 13px 18px; font-size: 14px; flex: 1; outline: none; }
	.ai-followup input:focus { outline: none; box-shadow: none; }
	.ai-followup button { border: 0; background: #00857a; color: #fff; padding: 0 24px; font-size: 13.5px; font-weight: 600; cursor: pointer; transition: background .15s; }
	.ai-followup button:hover { background: #006f65; }
	.ai-followup .hint { font-size: 11.5px; color: #a3adc2; text-align: center; margin-top: 9px; }

	.ai-switch { text-align: center; margin-top: 22px; }
	.ai-switch a { font-size: 13px; color: #00796b; font-weight: 600; text-decoration: none; }
	.ai-switch a:hover { text-decoration: underline; }

	@media (max-width: 575px) {
		.ai-question { font-size: 21px; }
		.ai-src-cover { display: none; }
	}
</style>
@endsection

@section('content')
<div id="results" style="background-color: #00857a;">
	<div class="container">
		<div class="row align-items-center">
			<div class="col-md-6">
				<div id="breadcrumb" class="breadcrumb_inline">
					<ul>
						<li><a href="{{ url('/home') }}">Home</a></li>
						<li>AI Answer</li>
					</ul>
				</div>
			</div>
		</div>
	</div>
</div>

<div class="ai-page">
	<h1 class="ai-question">{{ $query }}</h1>

	<div id="ai-result"></div>

	<div class="ai-followup">
		<form id="ai-followup-frm" method="get" action="{{ url('/results') }}" autocomplete="off">
			<input type="hidden" name="mode" value="ai">
			<div class="input-group">
				<input type="text" name="query" placeholder="Ask a follow-up&hellip;" aria-label="Ask a follow-up">
				<button type="submit">Ask</button>
			</div>
		</form>
		{{-- Single-shot for now: a follow-up re-asks from scratch. Multi-turn arrives when
		     conversation_id + history land (RAG_INTEGRATION_PLAN.md §10.4) — same box. --}}
		<div class="hint">Each question is answered on its own for now.</div>
	</div>

	<div class="ai-switch">
		<a href="{{ url('/results') }}?query={{ urlencode($query) }}&amp;layout=grid&amp;page=1&amp;series_statement_id=0&amp;seachtype[]=0">
			See keyword results instead &rarr;
		</a>
	</div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
(function () {
	var endpoint  = "{{ url('/search/ai-answer') }}";
	var query     = @json($query);
	var authed    = @json($authed ?? false);
	var loginUrl  = "{{ url('/login') }}";
	var regUrl    = "{{ url('/register') }}";
	var $out      = $('#ai-result');

	function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }

	// Members-only feature: prompt guests to sign in instead of answering.
	function loginNotice() {
		$('.ai-followup').hide();
		$out.html(
			'<div class="ai-notice">' +
			  '<span class="icn">&#128274;</span>' +
			  '<h4>Sign in to use AI mode</h4>' +
			  '<p>Asking the library&rsquo;s AI is available to registered members. ' +
			     'Please log in or create a free account to continue.</p>' +
			  '<div style="margin-top:16px; display:flex; gap:10px; justify-content:center;">' +
			    '<a href="' + esc(loginUrl) + '" class="btn_1">Log in</a>' +
			    '<a href="' + esc(regUrl) + '" class="btn_1 outline">Register</a>' +
			  '</div>' +
			'</div>'
		);
	}

	function skeleton() {
		return '<div class="ai-card">' +
		         '<div class="ai-card-head">' +
		           '<span class="ai-badge"><span class="spark">&#10022;</span> AI answer</span>' +
		         '</div>' +
		         '<div class="ai-body">' +
		           '<div class="ai-thinking"><span class="ai-dot"></span><span class="ai-dot"></span><span class="ai-dot"></span> Searching the collection&hellip;</div>' +
		           '<div class="sk"></div><div class="sk w90"></div><div class="sk w75"></div><div class="sk w60"></div>' +
		         '</div>' +
		       '</div>';
	}

	function notice(icon, title, body, tone) {
		return '<div class="ai-notice ' + (tone || '') + '">' +
		         '<span class="icn">' + icon + '</span>' +
		         '<h4>' + esc(title) + '</h4>' +
		         '<p>' + esc(body) + '</p>' +
		       '</div>';
	}

	// Turn [1] markers into chips that jump to the matching source. Escape FIRST, so the
	// answer text can never inject markup — only our own chips are added afterwards.
	function withCitations(text, sourceCount) {
		return esc(text).replace(/\[(\d+)\]/g, function (whole, n) {
			if (parseInt(n, 10) > sourceCount) return whole;   // no such source -> leave as text
			return '<a class="ai-cite" href="#src-' + n + '" data-n="' + n + '">' + n + '</a>';
		});
	}

	function paragraphs(text, sourceCount) {
		return String(text).split(/\n{2,}/).map(function (p) {
			return '<p>' + withCitations(p, sourceCount) + '</p>';
		}).join('');
	}

	function sourceHtml(s) {
		var meta = '';
		if (s.author) meta += '<span>' + esc(s.author) + '</span>';
		if (s.year)   meta += '<span>' + esc(s.year) + '</span>';
		if (s.type)   meta += '<span>' + esc(s.type) + '</span>';
		if (s.page)   meta += '<span>p.' + esc(s.page) + '</span>';

		return '<div class="ai-src" id="src-' + s.n + '">' +
		         '<div class="ai-src-n">' + s.n + '</div>' +
		         (s.thumb ? '<div class="ai-src-cover"><a href="' + esc(s.url) + '"><img src="' + esc(s.thumb) + '" alt=""></a></div>' : '') +
		         '<div class="ai-src-body">' +
		           '<a class="ai-src-title" href="' + esc(s.url) + '">' + esc(s.title) + '</a>' +
		           '<div class="ai-src-meta">' + meta + '</div>' +
		           (s.snippet ? '<div class="ai-src-snippet">' + esc(s.snippet) + '</div>' : '') +
		           (s.score != null
		              ? '<div class="ai-src-score"><span class="ai-bar"><i style="width:' + Math.round(s.score * 100) + '%"></i></span> relevance ' + esc(s.score) + '</div>'
		              : '') +
		         '</div>' +
		       '</div>';
	}

	function render(d) {
		// The assistant couldn't ground an answer — say so rather than invent one (§10.2).
		if (d.abstained) {
			$out.html(notice('&#128533;', 'No answer from the collection',
				'The library’s materials don’t cover this well enough to answer confidently. Try rephrasing, or search by keyword instead.', 'warn'));
			return;
		}

		var n = (d.sources || []).length;
		var html = '<div class="ai-card">' +
		             '<div class="ai-card-head">' +
		               '<span class="ai-badge"><span class="spark">&#10022;</span> AI answer</span>' +
		               (n ? '<span class="ai-grounded">grounded in ' + n + ' document' + (n === 1 ? '' : 's') + '</span>' : '') +
		             '</div>' +
		             '<div class="ai-body">' + paragraphs(d.answer, n) + '</div>' +
		             '<div class="ai-foot">' +
		               '<span>' + (d.answered_by === 'gemini'
		                   ? 'Written by Google Gemini from the library collection'
		                   : (d.answered_by === 'local' ? 'Written by the library’s own AI from the collection'
		                                                : 'Generated from the library collection')) +
		                 ' &mdash; always check the sources.</span>' +
		               (d.took_ms != null ? '<span>' + esc(d.took_ms) + ' ms</span>' : '') +
		             '</div>' +
		           '</div>';

		if (n) {
			html += '<h2 class="ai-sources-h">Sources (' + n + ')</h2>';
			d.sources.forEach(function (s) { html += sourceHtml(s); });
		}

		$out.html(html);
	}

	function fail(xhr) {
		var r = (xhr.responseJSON || {});
		if (r.reason === 'auth') {          // session expired mid-use, or a direct call
			loginNotice();
		} else if (r.reason === 'unavailable') {
			$out.html(notice('&#9200;', 'AI mode is temporarily unavailable',
				r.message || 'The assistant is busy or offline. Keyword search is still available.', 'warn'));
		} else {
			$out.html(notice('&#9888;', 'Couldn’t generate an answer',
				r.message || 'Something went wrong. Please try again.', 'warn'));
		}
	}

	// Guests never reach the gateway: show the sign-in prompt up front.
	if (!authed) {
		loginNotice();
	} else {
		$out.html(skeleton());
		$.getJSON(endpoint, { q: query }).done(render).fail(fail);
	}

	// Citation chip -> highlight the source it points at.
	$out.on('click', '.ai-cite', function (e) {
		e.preventDefault();
		var $t = $('#src-' + $(this).data('n'));
		if (!$t.length) return;
		$('html, body').animate({ scrollTop: $t.offset().top - 90 }, 220);
		$t.addClass('flash');
		setTimeout(function () { $t.removeClass('flash'); }, 1200);
	});

	$('#ai-followup-frm').on('submit', function () {
		return $.trim($(this).find('input[name=query]').val()).length > 0;
	});
})();
</script>
@endsection
