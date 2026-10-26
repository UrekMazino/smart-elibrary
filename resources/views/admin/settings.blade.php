@extends('admin.layouts.master')

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item active" aria-current="page">Settings</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-3">Settings</h6>

                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif

                    <form method="post" action="{{ url('/admin/settings') }}">
                        @csrf

                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="enable_placeholder_covers" name="enable_placeholder_covers" value="1"
                                   {{ ($settings['enable_placeholder_covers'] ?? '1') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="enable_placeholder_covers">
                                Enable placeholder coverpage
                            </label>
                        </div>
                        <p class="text-muted mb-4" style="font-size: 0.8125rem;">
                            When <strong>on</strong>, materials without an uploaded cover show an auto-generated
                            placeholder cover. When <strong>off</strong>, they show the default
                            &ldquo;no&nbsp;preview&rdquo; image. Uploaded covers are always shown either way.
                        </p>

                        @can('manage.ai.mode')
                        <hr class="my-4">

                        <h6 class="card-title mb-1">AI mode</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="ai_mode_enabled" name="ai_mode_enabled" value="1"
                                   {{ ($settings['ai_mode_enabled'] ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="ai_mode_enabled">
                                Enable AI mode in the OPAC
                            </label>
                        </div>
                        <p class="text-muted mb-3" style="font-size: 0.8125rem;">
                            Adds an <strong>AI mode</strong> toggle to the public search, answering
                            questions from the collection with sources instead of listing keyword
                            matches. When <strong>off</strong>, it disappears from the OPAC entirely
                            and its endpoints stop responding &mdash; keyword search is unaffected
                            either way.
                        </p>

                        <label for="ai_answer_provider" class="form-label mb-1" style="font-size: 0.8125rem;"><strong>Answer with</strong></label>
                        @php $aiProvider = app(\App\Services\AiSearchService::class)->provider(); @endphp
                        <select id="ai_answer_provider" name="ai_answer_provider" class="form-select form-select-sm mb-2" style="max-width: 22rem;">
                            @foreach(\App\Services\AiSearchService::PROVIDERS as $value => $label)
                                <option value="{{ $value }}" @selected($aiProvider === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                        <p class="text-muted mb-3" style="font-size: 0.8125rem;">
                            Finding the passages always runs on this server; this only chooses who writes the
                            answer from them. With <strong>Gemini</strong>, the question and those passages are
                            sent to Google (nothing about the patron). With <strong>backup</strong>, the local AI
                            answers whenever Gemini can't (no key, rate limit, outage). The Gemini key is set on
                            the AI server (<code>ProjectY\.env</code>), never here.
                        </p>

                        <div id="aiStatusBox" class="mb-3"></div>
                        @endcan

                        <hr class="my-4">

                        <h6 class="card-title mb-1">Search engine visibility</h6>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" role="switch"
                                   id="seo_discourage_indexing" name="seo_discourage_indexing" value="1"
                                   {{ ($settings['seo_discourage_indexing'] ?? '0') == '1' ? 'checked' : '' }}>
                            <label class="form-check-label" for="seo_discourage_indexing">
                                Discourage search engines from indexing this site
                            </label>
                        </div>
                        <p class="text-muted mb-3" style="font-size: 0.8125rem;">
                            Turn this <strong>on</strong> while the site is a staging or pre-launch copy: every
                            public page is served as <code>noindex, nofollow</code> so nothing is added to Google.
                            <strong class="text-danger">Turn it off before launch</strong> &mdash; left on, the
                            catalogue will never appear in search results. Note this is a request search engines
                            honour, not access control.
                        </p>

                        <label for="seo_verification_google" class="form-label" style="font-size: 0.8125rem;">
                            Google Search Console verification code
                        </label>
                        <input type="text" class="form-control mb-1" id="seo_verification_google"
                               name="seo_verification_google" autocomplete="off"
                               placeholder="e.g. AbCdEf123..."
                               value="{{ $settings['seo_verification_google'] ?? '' }}">
                        <p class="text-muted mb-3" style="font-size: 0.8125rem;">
                            The <em>content</em> value from Search Console&rsquo;s HTML-tag verification method
                            (the code only, not the whole tag). Added to every public page. Leave blank if you
                            verified by another method.
                        </p>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">Search index</h6>
                    <p class="text-muted mb-3" style="font-size: 0.8125rem;">
                        OPAC search runs on a Meilisearch index built from the catalog. Deleting or
                        re-importing records can leave stale entries behind, which makes the result
                        count disagree with the results actually shown. <strong>Re-sync</strong>
                        rebuilds the index from the database &mdash; safe to run any time.
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" id="scanBtn" class="btn btn-outline-secondary">
                            <i data-feather="activity" class="icon-sm me-1"></i>Run drift scan
                        </button>
                        <button type="button" id="reindexBtn" class="btn btn-outline-primary">
                            <i data-feather="refresh-cw" class="icon-sm me-1"></i>Re-sync search index
                        </button>
                        <span id="reindexStatus" style="font-size: 0.8125rem;"></span>
                    </div>

                    <div id="driftResult" class="mt-3" style="display:none;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">Sitemap</h6>
                    <p class="text-muted mb-3" style="font-size: 0.8125rem;">
                        Tells search engines which catalogue records exist &mdash; without it, deep record
                        pages may never be crawled. Rebuilt automatically every night; regenerate here after
                        publishing a batch, or after the site&rsquo;s address changes.
                    </p>

                    @if($sitemap['exists'])
                        <p class="mb-2" style="font-size: 0.8125rem;">
                            <span class="badge bg-success">Generated</span>
                            <span class="text-muted ms-1">
                                {{ number_format($sitemap['urls']) }} URLs &middot; last built {{ $sitemap['generated_at'] }}
                            </span>
                        </p>
                        @unless($sitemap['host_ok'])
                            <div class="alert alert-warning py-2" style="font-size: 0.8125rem;">
                                <strong>Wrong address.</strong> The sitemap lists URLs on a different domain than
                                this site is served from &mdash; it was probably generated before
                                <code>APP_URL</code> was updated. Regenerate it.
                            </div>
                        @endunless
                    @else
                        <p class="mb-2" style="font-size: 0.8125rem;">
                            <span class="badge bg-secondary">Not generated yet</span>
                        </p>
                    @endif

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" id="sitemapBtn" class="btn btn-outline-primary">
                            <i data-feather="map" class="icon-sm me-1"></i>Regenerate sitemap
                        </button>
                        @if($sitemap['exists'])
                            <a href="{{ url('/sitemap.xml') }}" target="_blank" class="btn btn-outline-secondary">
                                <i data-feather="external-link" class="icon-sm me-1"></i>View
                            </a>
                        @endif
                        <span id="sitemapStatus" style="font-size: 0.8125rem;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">Cache</h6>
                    <p class="text-muted mb-2" style="font-size: 0.8125rem;">
                        The homepage panels and the dashboard chart are cached for 10 minutes, so a
                        change can take a moment to appear &mdash; and bulk operations can leave them
                        stale for longer. Clearing is safe to run any time: it only drops cached
                        copies, never data.
                    </p>
                    <p class="text-muted mb-3" style="font-size: 0.75rem;">
                        Clears the homepage/sidebar panels, the dashboard chart, and compiled page
                        templates. Leaves everything else alone &mdash; <strong>nobody is signed
                        out</strong>, sign-in protection isn&rsquo;t reset, and the search index
                        isn&rsquo;t touched (that&rsquo;s <em>Re-sync</em> above).
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2">
                        <button type="button" id="clearCacheBtn" class="btn btn-outline-secondary">
                            <i data-feather="trash-2" class="icon-sm me-1"></i>Clear cache
                        </button>
                        <span id="clearCacheStatus" style="font-size: 0.8125rem;"></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
// Inline-styled chips (avoids the admin template's `.badge { color:#fff }` override).
function driftChip(label, val, tone) {
    // CSS values (with the theme's roles: App\Services\Appearance), so the chips follow the background.
    var bg = tone === 'danger' ? 'var(--ui-danger-soft, #ffe9ee)' : (tone === 'warn' ? 'var(--ui-warning-soft, #fff5e0)' : 'var(--ui-surface-2, #eef1f8)'),
        fg = tone === 'danger' ? 'var(--ui-danger-ink, #c92a4b)' : (tone === 'warn' ? 'var(--ui-warning-ink, #a06e14)' : 'var(--ui-text-2, #4a5a78)');
    return '<span style="display:inline-block;background:' + bg + ';color:' + fg + ';border-radius:20px;' +
           'padding:3px 10px;font-size:0.75rem;font-weight:600;margin:0 6px 6px 0;">' + label + ': ' + val + '</span>';
}

function runDriftScan(silent) {
    var $btn = $('#scanBtn'), $out = $('#driftResult');
    $btn.prop('disabled', true);
    if (!silent) $out.show().html('<span class="text-muted" style="font-size:0.8125rem;">Scanning the index…</span>');

    return $.getJSON("{{ url('/admin/settings/scan-search') }}")
        .done(function (r) {
            var head = r.in_sync
                ? '<strong class="text-success">&#10003; In sync</strong> <span class="text-muted">&mdash; the search index matches the database.</span>'
                : '<strong class="text-danger">Drift detected &mdash; ' + r.drift + ' issue(s)</strong> ' +
                  '<span class="text-muted">&mdash; click <strong>Re-sync search index</strong> to rebuild.</span>';

            var chips = driftChip('Indexed', r.index_docs) +
                        driftChip('DB rows', r.db_rows) +
                        driftChip('Orphaned', r.orphans, r.orphans ? 'danger' : null) +
                        driftChip('Stale', r.stale, r.stale ? 'warn' : null) +
                        driftChip('Missing', r.missing, r.missing ? 'warn' : null);

            $out.show().html('<div style="font-size:0.8125rem;">' + head + '<div class="mt-2">' + chips + '</div>' +
                '<div class="text-muted" style="font-size:0.75rem;">' +
                'Orphaned = indexed but deleted from the database (only a re-sync clears these) &middot; ' +
                'Stale = indexed values no longer match the database &middot; ' +
                'Missing = in the database but not indexed.</div></div>');
        })
        .fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Scan failed. Check that Meilisearch is running.';
            $out.show().html('<span class="text-danger" style="font-size:0.8125rem;">' + msg + '</span>');
        })
        .always(function () { $btn.prop('disabled', false); });
}

@can('manage.ai.mode')
// AI agent connection status. The endpoint and token live in .env (never the database,
// never sent to the browser) — this only reports whether the agent answers.
// Whether the gateway can use Gemini for the saved "Answer with" choice (key set or not; the key
// itself never leaves the AI server).
function geminiLine(r) {
    if (!r.ok) return '';
    if (!r.gemini) return 'Gemini: not supported by this gateway version &middot; ';
    var model = $('<div>').text(r.gemini.model || '').html();
    if (r.gemini.configured) return 'Gemini: key set (<code>' + model + '</code>) &middot; ';
    return '<span style="color:var(--ui-warning-ink, #a06e14);">Gemini: no key on the AI server' +
           (r.provider === 'local' ? '' : ', so answers come from the local AI') + '</span> &middot; ';
}

function renderAiStatus(r) {
    // 'loading' (models still warming up) is an amber in-between, not a hard failure.
    var warming = !r.ok && r.status === 'loading',
        color = r.ok ? 'var(--ui-success-ink, #1c7c54)' : (warming ? 'var(--ui-warning-ink, #a06e14)' : 'var(--ui-danger-ink, #c92a4b)'),
        bg    = r.ok ? 'var(--ui-success-soft, #e8f6ef)' : (warming ? 'var(--ui-warning-soft, #fff5e0)' : 'var(--ui-danger-soft, #ffe9ee)'),
        label = r.ok ? 'Connected' : (warming ? 'Starting up' : 'Unreachable');

    $('#aiStatusBox').html(
        '<div style="border:1px solid ' + bg + ';background:' + bg + ';border-radius:6px;padding:10px 12px;font-size:0.78125rem;">' +
          '<div class="d-flex justify-content-between align-items-center flex-wrap gap-2">' +
            '<span><strong style="color:' + color + ';">&#9679; ' + label + '</strong>' +
              '<span class="text-muted"> &mdash; ' + $('<div>').text(r.detail || '').html() + '</span></span>' +
            '<button type="button" id="aiTestBtn" class="btn btn-outline-secondary btn-sm">Test connection</button>' +
          '</div>' +
          '<div class="text-muted mt-2" style="font-size:0.71875rem;">' +
            geminiLine(r) +
            'Endpoint: <code>' + $('<div>').text(r.endpoint || '—').html() + '</code>' +
            ' &middot; set in <code>.env</code>, not here &mdash; secrets stay out of the database.' +
          '</div>' +
        '</div>'
    );
}

function loadAiStatus() {
    $('#aiStatusBox').html('<span class="text-muted" style="font-size:0.78125rem;">Checking the agent…</span>');
    $.getJSON("{{ url('/admin/settings/ai-status') }}")
        .done(renderAiStatus)
        .fail(function () {
            $('#aiStatusBox').html('<span class="text-danger" style="font-size:0.78125rem;">Could not read the agent status.</span>');
        });
}
@endcan

$(function () {
    if (window.feather) feather.replace();

    // Regenerate sitemap.xml + robots.txt without shell access.
    $('#sitemapBtn').on('click', function () {
        var $btn = $(this), $status = $('#sitemapStatus');
        $btn.prop('disabled', true);
        $status.removeClass('text-danger text-success').addClass('text-muted').text('Generating…');

        $.ajax({
            url: "{{ url('/admin/settings/generate-sitemap') }}",
            method: 'POST',
            data: { _token: "{{ csrf_token() }}" },
            success: function (r) {
                $status.removeClass('text-muted').addClass('text-success')
                       .text('Done — ' + (r.urls || 0).toLocaleString() + ' URLs written.');
                setTimeout(function () { location.reload(); }, 1200);
            },
            error: function () {
                $btn.prop('disabled', false);
                $status.removeClass('text-muted').addClass('text-danger')
                       .text('Could not generate the sitemap.');
            }
        });
    });

    @can('manage.ai.mode')
        loadAiStatus();
        $('#aiStatusBox').on('click', '#aiTestBtn', function () { loadAiStatus(); });
    @endcan

    $('#scanBtn').on('click', function () { runDriftScan(false); });

    $('#clearCacheBtn').on('click', function () {
        var $btn = $(this), $status = $('#clearCacheStatus');

        $btn.prop('disabled', true);
        $status.removeClass('text-success text-danger').addClass('text-muted').text('Clearing…');

        $.ajax({
            type: 'POST',
            url: "{{ url('/admin/settings/clear-cache') }}",
            data: { _token: "{{ csrf_token() }}" },
            dataType: 'json'
        }).done(function (res) {
            $status.removeClass('text-muted text-danger').addClass('text-success')
                   .text('Cleared — ' + res.cache_entries + ' cached item(s), ' + res.views_cleared + ' compiled view(s).');
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Clear cache failed. Please try again.';
            $status.removeClass('text-muted text-success').addClass('text-danger').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });

    $('#reindexBtn').on('click', function () {
        var $btn = $(this),
            $status = $('#reindexStatus');

        $btn.prop('disabled', true);
        $status.removeClass('text-success text-danger').addClass('text-muted')
               .text('Rebuilding the index… this can take a moment.');

        $.ajax({
            type: 'POST',
            url: "{{ url('/admin/settings/reindex-search') }}",
            data: { _token: "{{ csrf_token() }}" },
            dataType: 'json'
        }).done(function (res) {
            $status.removeClass('text-muted text-danger').addClass('text-success')
                   .text('Done — ' + res.rows + ' record(s) indexed.');
            runDriftScan(true);   // re-scan so the report shows it's now in sync
        }).fail(function (xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Re-sync failed. Check that Meilisearch is running.';
            $status.removeClass('text-muted text-success').addClass('text-danger').text(msg);
        }).always(function () {
            $btn.prop('disabled', false);
        });
    });
});
</script>
@endsection
