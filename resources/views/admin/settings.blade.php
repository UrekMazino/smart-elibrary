@extends('admin.layouts.master')
{{-- Administration → Settings (SettingController). Two kinds of control, kept apart so nobody wonders what Save covers:
     Options (covers, AI answers, search engines), saved together by the save bar, which counts unsaved changes and
     asks before the page is left with some; and Tools (search index, sitemap, cached pages), which run at once.
     "Now in effect" at the top shows the saved values. --}}

@section('css')
    <style>
      .st-wrap { max-width: 920px; }
      .ac-head { margin-bottom: 18px; }
      .ac-head h4 { font-weight: 700; color: var(--ui-text, #22283b); margin: 0; }
      .ac-head p  { color: var(--ui-muted, #8a94a6); margin: 2px 0 0; font-size: 0.8125rem; }

      /* After a save: what changed, or why nothing was saved. */
      .st-flash { display: flex; gap: 10px; align-items: flex-start; padding: 12px 14px; border-radius: 10px; margin-bottom: 18px; font-size: 0.8125rem; }
      .st-flash svg { flex: 0 0 auto; width: 18px; height: 18px; margin-top: 1px; }
      .st-flash p { margin: 0; color: var(--ui-text, #22283b); line-height: 1.5; }
      .st-flash.is-ok  { background: var(--ui-success-soft, #e8f6ef); color: var(--ui-success-ink, #1c7c54); }
      .st-flash.is-bad { background: var(--ui-danger-soft, #ffe9ee); color: var(--ui-danger-ink, #c92a4b); }

      /* Now in effect: one tile per area, each a link to its section. */
      .st-label { font-size: 0.6875rem; font-weight: 700; letter-spacing: .5px; text-transform: uppercase; color: var(--ui-muted, #7987a1); margin: 0 0 8px; }
      .st-glance { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 28px; }
      .st-tile {
        display: flex; gap: 10px; align-items: center; padding: 10px 12px; border: 1px solid var(--ui-border, #eef0f4); border-radius: 10px;
        background: var(--ui-surface, #fff); color: inherit; text-decoration: none; transition: border-color .12s, box-shadow .12s;
      }
      .st-tile:hover, .st-tile:focus-visible { border-color: color-mix(in srgb, var(--t-primary) 47%, var(--ui-surface, #fff)); box-shadow: 0 4px 14px rgba(var(--t-primary-rgb), .10); color: inherit; }
      .st-tile-icon, .st-icon {
        flex: 0 0 auto; display: flex; align-items: center; justify-content: center; border-radius: 9px;
        background: color-mix(in srgb, var(--t-primary) 12%, var(--ui-surface, #fff)); color: var(--ui-accent-text, var(--t-primary));
      }
      .st-tile-icon { width: 32px; height: 32px; }
      .st-tile-icon svg { width: 16px; height: 16px; }
      .st-tile-label { display: block; font-size: 0.71875rem; color: var(--ui-muted, #7987a1); }
      .st-tile-value { display: block; font-size: 0.8125rem; font-weight: 600; color: var(--ui-text, #22283b); line-height: 1.3; }
      .st-tile.is-warn { background: var(--ui-warning-soft, #fff7e6); border-color: var(--ui-warning-line, #f3d9a4); }
      .st-tile.is-warn .st-tile-icon { background: transparent; color: var(--ui-warning-ink, #a06e14); }

      /* Groups and their cards */
      .st-group { margin-bottom: 30px; }
      .st-group-head { margin: 0 0 10px; }
      .st-group-head h5 { margin: 0; font-weight: 700; font-size: 1rem; color: var(--ui-text, #22283b); }
      .st-group-head p { margin: 2px 0 0; font-size: 0.78125rem; color: var(--ui-muted, #7987a1); }
      .st-card {
        background: var(--ui-surface, #fff); border: 1px solid var(--ui-border, #eef0f4); border-radius: 12px; padding: 18px 20px;
        margin-bottom: 12px; box-shadow: 0 1px 2px rgba(20, 30, 60, .04); scroll-margin-top: 84px;
      }
      .st-card-head { display: flex; gap: 12px; align-items: flex-start; }
      .st-icon { width: 36px; height: 36px; }
      .st-icon svg { width: 18px; height: 18px; }
      .st-card-title { margin: 0; font-weight: 700; font-size: 0.9375rem; color: var(--ui-text, #22283b); }
      .st-card-sub { margin: 2px 0 0; font-size: 0.78125rem; color: var(--ui-text-2, #6b7489); line-height: 1.5; }

      /* One option: its words on the left, its switch on the right */
      .st-option, .st-field, .st-fieldset { margin-top: 14px; padding-top: 14px; border-top: 1px solid var(--ui-border, #eef0f4); }
      .st-option { display: flex; align-items: flex-start; justify-content: space-between; gap: 16px; }
      .st-option-label, .st-fieldset legend, .st-field .form-label {
        display: block; font-weight: 600; font-size: 0.8125rem; line-height: 1.5; color: var(--ui-text, #22283b); margin: 0 0 2px; padding: 0;
      }
      .st-fieldset legend { float: left; width: 100%; }   /* inside the box, not on its border; Bootstrap clears the next element */
      .st-option-label { cursor: pointer; }
      .st-optional { font-weight: 400; color: var(--ui-muted, #7987a1); }
      .st-help { margin: 0; font-size: 0.78125rem; color: var(--ui-text-2, #6b7489); line-height: 1.5; }
      .st-switch { display: flex; align-items: center; gap: 8px; flex: 0 0 auto; margin: 0; padding: 0; min-height: 0; }
      .st-switch .form-check-input { float: none; margin: 0; width: 2.6em; height: 1.4em; cursor: pointer; }
      .st-state { min-width: 24px; font-size: 0.75rem; font-weight: 600; color: var(--ui-muted, #7987a1); }
      .st-switch .form-check-input:checked + .st-state { color: var(--ui-accent-text, var(--t-primary)); }

      /* Covers: the two looks, the one the switch gives marked */
      .st-covers { display: flex; flex-wrap: wrap; gap: 10px; margin-top: 12px; }
      .st-cover {
        display: flex; gap: 10px; align-items: center; margin: 0; padding: 8px 14px 8px 8px; border: 1px solid var(--ui-border, #eef0f4);
        border-radius: 10px; opacity: .5; transition: opacity .15s, border-color .15s;
      }
      .st-cover img { width: 46px; height: 60px; object-fit: cover; border-radius: 4px; box-shadow: 0 1px 3px rgba(0, 0, 0, .15); }
      .st-cover figcaption { font-size: 0.75rem; color: var(--ui-text-2, #6b7489); line-height: 1.4; }
      .st-cover figcaption strong { display: block; color: var(--ui-text, #22283b); }
      .st-cover.is-current { opacity: 1; border-color: color-mix(in srgb, var(--t-primary) 55%, var(--ui-surface, #fff)); }

      /* AI server status */
      .st-status { margin-top: 12px; padding: 10px 12px; border-radius: 8px; font-size: 0.78125rem; background: var(--ui-surface-2, #f4f6fa); color: var(--ui-text-2, #4a5a78); }
      .st-status.is-ok   { background: var(--ui-success-soft, #e8f6ef); }
      .st-status.is-warn { background: var(--ui-warning-soft, #fff5e0); }
      .st-status.is-bad  { background: var(--ui-danger-soft, #ffe9ee); }
      .st-status-row { display: flex; align-items: center; justify-content: space-between; gap: 8px 12px; flex-wrap: wrap; }
      .st-status-title { font-weight: 700; color: var(--ui-text, #22283b); }
      .is-ok .st-status-title   { color: var(--ui-success-ink, #1c7c54); }
      .is-warn .st-status-title { color: var(--ui-warning-ink, #a06e14); }
      .is-bad .st-status-title  { color: var(--ui-danger-ink, #c92a4b); }
      .st-status-more { margin-top: 4px; }
      .st-tech { margin-top: 6px; font-size: 0.75rem; }
      .st-tech summary { cursor: pointer; color: var(--ui-muted, #7987a1); width: max-content; }
      .st-tech div { margin-top: 4px; color: var(--ui-text-2, #4a5a78); word-break: break-word; }

      /* Who writes the answers */
      .st-choices { display: grid; gap: 8px; margin-top: 8px; }
      .st-choice {
        display: flex; gap: 10px; align-items: flex-start; margin: 0; padding: 10px 12px; border: 1px solid var(--ui-border, #e3e7ef);
        border-radius: 10px; cursor: pointer; transition: border-color .12s, background-color .12s;
      }
      .st-choice:hover { border-color: color-mix(in srgb, var(--t-primary) 35%, var(--ui-surface, #fff)); }
      .st-choice .form-check-input { flex: 0 0 auto; float: none; margin: 2px 0 0; cursor: pointer; }
      .st-choice.is-checked { border-color: color-mix(in srgb, var(--t-primary) 60%, var(--ui-surface, #fff)); background: color-mix(in srgb, var(--t-primary) 6%, var(--ui-surface, #fff)); }
      .st-choice-title { display: block; font-weight: 600; font-size: 0.8125rem; color: var(--ui-text, #22283b); }
      .st-choice-note { display: block; font-size: 0.75rem; color: var(--ui-text-2, #6b7489); line-height: 1.45; }
      .st-tag { display: inline-block; margin-left: 2px; padding: 1px 7px; border-radius: 10px; font-size: 0.65625rem; font-weight: 700; vertical-align: 1px; background: var(--ui-surface-2, #eef1f8); color: var(--ui-text-2, #4a5a78); }

      /* A warning that belongs to an option */
      .st-note { display: flex; gap: 8px; align-items: flex-start; margin-top: 10px; padding: 8px 12px; border-radius: 8px; font-size: 0.78125rem; background: var(--ui-warning-soft, #fff5e0); color: var(--ui-warning-ink, #8a5a00); }
      .st-note svg { flex: 0 0 auto; width: 16px; height: 16px; margin-top: 1px; }
      .st-note[hidden] { display: none; }
      .st-field .form-control { max-width: 460px; }
      .st-kept { color: var(--ui-success-ink, #1c7c54); margin-top: 4px; }

      /* The save bar: low-key while nothing has changed, then held at the bottom of the screen until saved. */
      .st-savebar {
        display: flex; align-items: center; justify-content: space-between; gap: 10px 16px; flex-wrap: wrap; padding: 12px 16px;
        border-radius: 12px; border: 1px solid var(--ui-border, #eef0f4); background: var(--ui-surface, #fff);
      }
      .st-savebar.is-dirty {
        position: sticky; bottom: 12px; z-index: 5; border-color: color-mix(in srgb, var(--t-primary) 55%, var(--ui-surface, #fff));
        box-shadow: 0 8px 24px rgba(20, 30, 60, .16);
      }
      .st-savebar-text { font-size: 0.8125rem; color: var(--ui-muted, #7987a1); }
      .st-savebar.is-dirty .st-savebar-text { color: var(--ui-text, #22283b); font-weight: 600; }
      .st-savebar-actions { display: flex; flex-wrap: wrap; gap: 8px; }

      /* Tools */
      .st-meta { margin: 12px 0 0; font-size: 0.78125rem; color: var(--ui-text-2, #4a5a78); }
      .st-actions { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; margin-top: 14px; }
      .st-result:empty { display: none; }
      .st-result { margin-top: 12px; padding: 10px 12px; border-radius: 8px; font-size: 0.8125rem; background: var(--ui-surface-2, #f4f6fa); color: var(--ui-text, #22283b); line-height: 1.5; }
      .st-result.is-ok   { background: var(--ui-success-soft, #e8f6ef); }
      .st-result.is-warn { background: var(--ui-warning-soft, #fff5e0); }
      .st-result.is-bad  { background: var(--ui-danger-soft, #ffe9ee); }
      .st-chips { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
      .st-chip { padding: 3px 10px; border-radius: 20px; font-size: 0.75rem; font-weight: 600; background: var(--ui-surface, #fff); border: 1px solid var(--ui-border, #e3e7ef); color: var(--ui-text-2, #4a5a78); }
      .st-quiet { color: var(--ui-muted, #7987a1); }

      @media (max-width: 575.98px) {
        .st-card { padding: 16px; }
        .st-savebar.is-dirty { bottom: 8px; }
        .st-savebar-actions { width: 100%; }
        .st-savebar-actions .btn { flex: 1 1 auto; }
      }
    </style>
@endsection

@section('content')
@php
    $ai = \App\Services\AiSearchService::class;
    $canAi = auth()->user()->can('manage.ai.mode');
    $on = fn ($key) => $current[$key] === '1';

    // What is saved, for the save bar to compare the form with.
    $saved = [
        'enable_placeholder_covers' => $on('enable_placeholder_covers'),
        'ai_mode_enabled'           => $on('ai_mode_enabled'),
        'ai_answer_provider'        => $current['ai_answer_provider'],
        'seo_discourage_indexing'   => $on('seo_discourage_indexing'),
        'seo_verification_google'   => $current['seo_verification_google'],
    ];
    // The form: what is saved, or after a refused save what was sent, so no edit is lost.
    $form = $saved;
    if ($errors->any()) {
        foreach (['enable_placeholder_covers', 'ai_mode_enabled', 'seo_discourage_indexing'] as $key) {
            $form[$key] = old($key) === '1';
        }
        $form['ai_answer_provider'] = (string) old('ai_answer_provider', $saved['ai_answer_provider']);
        $form['seo_verification_google'] = (string) old('seo_verification_google', '');
    }

    $providers = [
        'gemini_local' => ['short' => 'Gemini, local AI as backup',
                           'note'  => 'Google Gemini writes the answer. When it can’t (no key, limit reached, outage), the local AI does. The question and the passages go to Google, nothing about the patron.'],
        'gemini'       => ['short' => 'Gemini only',
                           'note'  => 'Google Gemini writes every answer, with no local backup. The question and the passages go to Google, nothing about the patron.'],
        'local'        => ['short' => 'Local AI only',
                           'note'  => 'The library’s own AI writes every answer. Nothing is sent to Google.'],
    ];

    $addresses = number_format($sitemap['urls']) . ' ' . \Illuminate\Support\Str::plural('address', $sitemap['urls']);
    $sitemapLine = ! $sitemap['exists'] ? 'Not built yet'
        : ($sitemap['host_ok'] ? $addresses . ' · built ' . $sitemap['generated_at'] : 'Wrong address: rebuild it');
@endphp
<div class="page-content">
<div class="st-wrap">
    @include('admin.partials.breadcrumb', ['page' => 'settings'])

    <div class="ac-head">
        <h4>Settings</h4>
        <p>How the public catalogue (OPAC) works for every visitor, and tools that keep its search up to date.</p>
    </div>

    @if ($errors->any())
        <div class="st-flash is-bad" role="alert" id="notSaved">
            <i data-feather="alert-circle"></i>
            <p><strong>Not saved.</strong> {{ $errors->first() }} Nothing was changed; your edits are still below.
               <a href="#{{ $errors->has('ai_answer_provider') ? 'ai' : 'search-engines' }}">Go to the field</a></p>
        </div>
    @elseif (session()->has('saved'))
        <div class="st-flash is-ok" role="status">
            <i data-feather="check-circle"></i>
            <p>
                @if (session('saved'))
                    <strong>Saved.</strong> {{ implode(' ', session('saved')) }}
                @else
                    <strong>Nothing to save.</strong> These settings were already in effect.
                @endif
            </p>
        </div>
    @endif

    <h5 class="st-label" id="glance-title">Now in effect</h5>
    <div class="st-glance" aria-labelledby="glance-title">
        <a class="st-tile" href="#covers">
            <span class="st-tile-icon"><i data-feather="image"></i></span>
            <span>
                <span class="st-tile-label">Covers</span>
                <span class="st-tile-value">{{ $saved['enable_placeholder_covers'] ? 'Made for records without one' : '“No preview” for records without one' }}</span>
            </span>
        </a>
        @if ($canAi)
            <a class="st-tile" href="#ai">
                <span class="st-tile-icon"><i data-feather="message-circle"></i></span>
                <span>
                    <span class="st-tile-label">AI answers</span>
                    <span class="st-tile-value">{{ $saved['ai_mode_enabled'] ? 'On · ' . ($providers[$saved['ai_answer_provider']]['short'] ?? '') : 'Off' }}</span>
                </span>
            </a>
        @endif
        <a class="st-tile {{ $saved['seo_discourage_indexing'] ? 'is-warn' : '' }}" href="#search-engines">
            <span class="st-tile-icon"><i data-feather="{{ $saved['seo_discourage_indexing'] ? 'eye-off' : 'globe' }}"></i></span>
            <span>
                <span class="st-tile-label">Search engines</span>
                <span class="st-tile-value">{{ $saved['seo_discourage_indexing'] ? 'Asked not to list the site' : 'May list the site' }}</span>
            </span>
        </a>
        <a class="st-tile {{ $sitemap['exists'] && $sitemap['host_ok'] ? '' : 'is-warn' }}" href="#sitemap" id="sitemapTile">
            <span class="st-tile-icon"><i data-feather="map"></i></span>
            <span>
                <span class="st-tile-label">Sitemap</span>
                <span class="st-tile-value">{{ $sitemapLine }}</span>
            </span>
        </a>
    </div>

    <form method="POST" action="{{ url('/admin/settings') }}" id="settingsForm" novalidate>
        @csrf
        <div class="st-group">
            <div class="st-group-head">
                <h5>Options</h5>
                <p>Change what you need, then press <strong>Save changes</strong>. Visitors see nothing new until you save.</p>
            </div>

            <section class="st-card" id="covers" aria-labelledby="covers-title">
                <div class="st-card-head">
                    <span class="st-icon"><i data-feather="image"></i></span>
                    <div>
                        <h6 class="st-card-title" id="covers-title">Covers</h6>
                        <p class="st-card-sub">What the OPAC shows for a record without an uploaded cover. Uploaded covers always show.</p>
                    </div>
                </div>
                <div class="st-option">
                    <div>
                        <label class="st-option-label" for="enable_placeholder_covers">Make a cover for records without one</label>
                        <p class="st-help" id="covers-help">The cover shows the record’s title, first author and material type on a coloured background. Off: “No preview available”.</p>
                    </div>
                    <div class="form-check form-switch st-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="enable_placeholder_covers" name="enable_placeholder_covers"
                               value="1" aria-describedby="covers-help" @checked($form['enable_placeholder_covers'])>
                        <span class="st-state" data-state-for="enable_placeholder_covers" aria-hidden="true">{{ $form['enable_placeholder_covers'] ? 'On' : 'Off' }}</span>
                    </div>
                </div>
                @if ($coverSample)
                    <div class="st-covers" aria-hidden="true">
                        <figure class="st-cover {{ $form['enable_placeholder_covers'] ? 'is-current' : '' }}" data-cover="on">
                            <img src="{{ asset($coverSample) }}" alt="" width="46" height="60" loading="lazy">
                            <figcaption><strong>On</strong>A cover made from the record</figcaption>
                        </figure>
                        <figure class="st-cover {{ $form['enable_placeholder_covers'] ? '' : 'is-current' }}" data-cover="off">
                            <img src="{{ asset('frontpages/no-preview.jpg') }}" alt="" width="46" height="60" loading="lazy">
                            <figcaption><strong>Off</strong>“No preview available”</figcaption>
                        </figure>
                    </div>
                @endif
            </section>

            @if ($canAi)
                <section class="st-card" id="ai" aria-labelledby="ai-title">
                    <div class="st-card-head">
                        <span class="st-icon"><i data-feather="message-circle"></i></span>
                        <div>
                            <h6 class="st-card-title" id="ai-title">AI answers</h6>
                            <p class="st-card-sub">An AI mode in the OPAC search that answers a question from the collection and lists the records it used. Keyword search works either way.</p>
                        </div>
                    </div>
                    <div class="st-option">
                        <div>
                            <label class="st-option-label" for="ai_mode_enabled">Offer AI answers in the OPAC search</label>
                            <p class="st-help" id="ai-help">Off: the AI mode button disappears from the OPAC, and its addresses stop answering.</p>
                        </div>
                        <div class="form-check form-switch st-switch">
                            <input class="form-check-input" type="checkbox" role="switch" id="ai_mode_enabled" name="ai_mode_enabled"
                                   value="1" aria-describedby="ai-help" @checked($form['ai_mode_enabled'])>
                            <span class="st-state" data-state-for="ai_mode_enabled" aria-hidden="true">{{ $form['ai_mode_enabled'] ? 'On' : 'Off' }}</span>
                        </div>
                    </div>
                    <div id="aiStatus" class="st-status" role="status" aria-live="polite">Checking the AI server…</div>

                    <fieldset class="st-fieldset" aria-describedby="provider-help">
                        <legend>Who writes the answers</legend>
                        <p class="st-help" id="provider-help">The passages are always found on the library’s AI server; this chooses who writes the answer from them.</p>
                        <div class="st-choices">
                            @foreach ($ai::PROVIDERS as $value => $label)
                                <label class="st-choice {{ $form['ai_answer_provider'] === $value ? 'is-checked' : '' }}">
                                    <input class="form-check-input" type="radio" name="ai_answer_provider" value="{{ $value }}"
                                           aria-describedby="provider-{{ $value }}" @checked($form['ai_answer_provider'] === $value)>
                                    <span>
                                        <span class="st-choice-title">{{ $label }} @if ($value === $ai::DEFAULT_PROVIDER)<span class="st-tag">Default</span>@endif</span>
                                        <span class="st-choice-note" id="provider-{{ $value }}">{{ $providers[$value]['note'] ?? '' }}</span>
                                    </span>
                                </label>
                            @endforeach
                        </div>
                    </fieldset>
                </section>
            @endif

            <section class="st-card" id="search-engines" aria-labelledby="se-title">
                <div class="st-card-head">
                    <span class="st-icon"><i data-feather="globe"></i></span>
                    <div>
                        <h6 class="st-card-title" id="se-title">Search engines</h6>
                        <p class="st-card-sub">Whether Google and other search engines list the catalogue’s pages.</p>
                    </div>
                </div>
                <div class="st-option">
                    <div>
                        <label class="st-option-label" for="seo_discourage_indexing">Ask search engines not to list this site</label>
                        <p class="st-help" id="se-help">For a test or pre-launch copy: every public page asks search engines not to list it. It is a request they honour, not a lock.</p>
                    </div>
                    <div class="form-check form-switch st-switch">
                        <input class="form-check-input" type="checkbox" role="switch" id="seo_discourage_indexing" name="seo_discourage_indexing"
                               value="1" aria-describedby="se-help" @checked($form['seo_discourage_indexing'])>
                        <span class="st-state" data-state-for="seo_discourage_indexing" aria-hidden="true">{{ $form['seo_discourage_indexing'] ? 'On' : 'Off' }}</span>
                    </div>
                </div>
                <div class="st-note" id="hiddenNote" @unless ($form['seo_discourage_indexing']) hidden @endunless>
                    <i data-feather="alert-triangle"></i>
                    <span><strong>Turn this off before launch.</strong> While it is on, the catalogue won’t appear in Google, and every admin page shows a reminder.</span>
                </div>
                <div class="st-field">
                    <label class="form-label" for="seo_verification_google">Google Search Console verification code <span class="st-optional">(optional)</span></label>
                    <input type="text" class="form-control @error('seo_verification_google') is-invalid @enderror" id="seo_verification_google"
                           name="seo_verification_google" autocomplete="off" spellcheck="false" placeholder="e.g. AbCdEf123…"
                           aria-describedby="code-help" value="{{ $form['seo_verification_google'] }}">
                    @error('seo_verification_google')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <p class="st-help mt-1" id="code-help">
                        Only for verifying this site with Search Console’s HTML tag method: paste the code, the part in quotes after
                        <code>content=</code>. If you paste the whole tag, only the code is kept. Leave it blank if you verified another way.
                    </p>
                    <p class="st-help st-kept" id="codeKept" hidden>Only the code was kept from the tag you pasted.</p>
                </div>
            </section>

            <div class="st-savebar" id="saveBar" role="region" aria-label="Save settings">
                <span class="st-savebar-text" id="saveState" aria-live="polite">Change an option above, then save it here.</span>
                <div class="st-savebar-actions">
                    <button type="button" class="btn btn-outline-secondary" id="discardBtn" hidden>Discard changes</button>
                    <button type="submit" class="btn btn-primary" id="saveBtn">Save changes</button>
                </div>
            </div>
        </div>
    </form>

    <div class="st-group">
        <div class="st-group-head">
            <h5>Tools</h5>
            <p>Each one runs as soon as you press its button. There is nothing to save.</p>
        </div>

        <section class="st-card" id="search-index" aria-labelledby="si-title">
            <div class="st-card-head">
                <span class="st-icon"><i data-feather="search"></i></span>
                <div>
                    <h6 class="st-card-title" id="si-title">Catalogue search</h6>
                    <p class="st-card-sub">
                        The OPAC search keeps its own copy of the catalogue, the search index. If results look wrong (a deleted
                        record still shows, or the count doesn’t match the list), check the index, then rebuild it if needed.
                    </p>
                </div>
            </div>
            <div class="st-actions">
                <button type="button" id="checkBtn" class="btn btn-outline-secondary">
                    <i data-feather="check-circle" class="icon-sm me-1"></i>Check search
                </button>
                <button type="button" id="rebuildBtn" class="btn btn-outline-primary">
                    <i data-feather="refresh-cw" class="icon-sm me-1"></i>Rebuild search index
                </button>
            </div>
            <div id="searchResult" class="st-result" role="status" aria-live="polite"></div>
        </section>

        <section class="st-card" id="sitemap" aria-labelledby="sm-title">
            <div class="st-card-head">
                <span class="st-icon"><i data-feather="map"></i></span>
                <div>
                    <h6 class="st-card-title" id="sm-title">Sitemap for search engines</h6>
                    <p class="st-card-sub">
                        A list of every public record page, so search engines can find them all. It is rebuilt every night;
                        rebuild it now after publishing many records, or after the site’s address changes.
                    </p>
                </div>
            </div>
            <p class="st-meta" id="sitemapMeta">
                @if ($sitemap['exists'])
                    Built {{ $sitemap['generated_at'] }} · {{ $addresses }} listed
                @else
                    Not built yet.
                @endif
            </p>
            <div class="st-note" id="sitemapWarn" @if (! $sitemap['exists'] || $sitemap['host_ok']) hidden @endif>
                <i data-feather="alert-triangle"></i>
                <span><strong>Wrong address.</strong> The sitemap lists pages on another address than this site’s (it was
                      probably built before <code>APP_URL</code> changed). Rebuild it.</span>
            </div>
            <div class="st-actions">
                <button type="button" id="sitemapBtn" class="btn btn-outline-primary">
                    <i data-feather="refresh-cw" class="icon-sm me-1"></i>Rebuild sitemap
                </button>
                <a href="{{ url('/sitemap.xml') }}" target="_blank" rel="noopener" class="btn btn-outline-secondary" id="sitemapView" @unless ($sitemap['exists']) hidden @endunless>
                    <i data-feather="external-link" class="icon-sm me-1"></i>View sitemap
                </a>
            </div>
            <div id="sitemapResult" class="st-result" role="status" aria-live="polite"></div>
        </section>

        <section class="st-card" id="cache" aria-labelledby="cache-title">
            <div class="st-card-head">
                <span class="st-icon"><i data-feather="zap"></i></span>
                <div>
                    <h6 class="st-card-title" id="cache-title">Cached pages</h6>
                    <p class="st-card-sub">
                        The home page panels and the dashboard chart are kept for 10 minutes, so a change can take that long to
                        show. Refresh them to show the latest data now. Safe at any time: no data is deleted, nobody is signed
                        out, and the search index isn’t touched.
                    </p>
                </div>
            </div>
            <div class="st-actions">
                <button type="button" id="cacheBtn" class="btn btn-outline-secondary">
                    <i data-feather="zap" class="icon-sm me-1"></i>Refresh cached pages
                </button>
            </div>
            <div id="cacheResult" class="st-result" role="status" aria-live="polite"></div>
        </section>
    </div>

</div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
$(function () {
    if (window.feather) feather.replace();

    var token = @json(csrf_token());
    var urls = {
        scan: @json(url('/admin/settings/scan-search')),
        reindex: @json(url('/admin/settings/reindex-search')),
        sitemap: @json(url('/admin/settings/generate-sitemap')),
        cache: @json(url('/admin/settings/clear-cache')),
        ai: @json(url('/admin/settings/ai-status'))
    };
    var spinner = '<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>';

    function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html(); }
    function count(n, one, many) { return Number(n || 0).toLocaleString() + ' ' + (n === 1 ? one : many); }

    // ---- Options: the save bar compares the form with what is saved ----------------------------------------
    var form = document.getElementById('settingsForm');
    var saved = @json($saved);
    var areas = { enable_placeholder_covers: 'Covers', ai_mode_enabled: 'AI answers', ai_answer_provider: 'AI answers',
                  seo_discourage_indexing: 'Search engines', seo_verification_google: 'Search engines' };
    var submitting = false;

    function field(name) { return $(form).find('[name="' + name + '"]'); }

    // A field's value as `saved` holds it; undefined for a section this admin doesn't see.
    function value(name) {
        var $f = field(name);
        if (!$f.length) return undefined;
        if ($f.attr('type') === 'checkbox') return $f.prop('checked');
        if ($f.attr('type') === 'radio') return $f.filter(':checked').val();
        return $.trim($f.val());
    }

    function unsaved() {
        return Object.keys(saved).filter(function (name) {
            var v = value(name);
            return v !== undefined && v !== saved[name];
        });
    }

    function refresh() {
        var names = unsaved(), dirty = names.length > 0;
        var where = names.map(function (n) { return areas[n]; }).filter(function (a, i, all) { return all.indexOf(a) === i; });

        $('#saveBar').toggleClass('is-dirty', dirty);
        $('#saveState').text(dirty ? 'Unsaved changes: ' + where.join(', ') : 'No unsaved changes.');
        $('#saveBtn').prop('disabled', !dirty);
        $('#discardBtn').prop('hidden', !dirty);

        $(form).find('[data-state-for]').each(function () {
            this.textContent = document.getElementById(this.dataset.stateFor).checked ? 'On' : 'Off';
        });
        $('[data-cover]').each(function () {
            $(this).toggleClass('is-current', (this.dataset.cover === 'on') === value('enable_placeholder_covers'));
        });
        $(form).find('.st-choice').each(function () {
            $(this).toggleClass('is-checked', $(this).find('input').prop('checked'));
        });
        $('#hiddenNote').prop('hidden', !value('seo_discourage_indexing'));
        if (lastAi) showAiStatus(lastAi);   // its wording depends on the AI switch
    }

    $(form).on('input change', refresh);

    $('#discardBtn').on('click', function () {
        Object.keys(saved).forEach(function (name) {
            var $f = field(name);
            if ($f.attr('type') === 'checkbox') $f.prop('checked', saved[name]);
            else if ($f.attr('type') === 'radio') $f.each(function () { this.checked = this.value === saved[name]; });
            else $f.val(saved[name]).removeClass('is-invalid');
        });
        $('#codeKept').prop('hidden', true);
        $('#notSaved').remove();   // it was about the edits just discarded
        refresh();
    });

    $('#seo_verification_google').on('input', function () { $(this).removeClass('is-invalid'); });

    // Search Console's whole tag pasted: keep its code, as the server would.
    $('#seo_verification_google').on('paste change', function () {
        var input = this;
        setTimeout(function () {
            var m = /content\s*=\s*["']([^"']*)["']/i.exec(input.value);
            if (m) {
                input.value = $.trim(m[1]);
                $('#codeKept').prop('hidden', false);
            }
            refresh();
        }, 0);
    });

    $(form).on('submit', function () {
        submitting = true;
        $('#saveBtn').prop('disabled', true).html(spinner + 'Saving…');
    });

    window.addEventListener('beforeunload', function (e) {
        if (submitting || !unsaved().length) return;
        e.preventDefault();
        e.returnValue = '';
    });

    // ---- AI server status: read-only; the address and token live in .env, never here -----------------------
    var lastAi = null, shownAi = '';

    function showAiStatus(r) {
        lastAi = r;
        var aiOn = value('ai_mode_enabled'), tone, title, text, more = '';

        if (r.ok) {
            tone = 'ok'; title = 'AI server connected';
            text = String(r.detail || '').replace(/^Connected\s*[—-]\s*/, '');
            var provider = value('ai_answer_provider');
            if (r.gemini && !r.gemini.configured && provider !== 'local') {
                more = 'No Gemini key on the AI server, so the local AI writes the answers.';
            }
        } else if (r.status === 'loading') {
            tone = 'warn'; title = 'AI server starting up';
            text = 'It is still loading. Check again in a minute.';
        } else if (aiOn) {
            tone = 'bad'; title = 'AI server not connected';
            text = 'AI answers won’t work until it is.';
        } else {
            tone = ''; title = 'AI server not connected';
            text = 'Only needed while AI answers are on.';
        }

        var tech = [];
        if (!r.ok && r.detail) tech.push(esc(r.detail));
        if (r.gemini) tech.push('Gemini key: ' + (r.gemini.configured ? 'set (<code>' + esc(r.gemini.model) + '</code>)' : 'not set') + '.');
        tech.push('AI server address: <code>' + esc(r.endpoint || '—') + '</code>, set in <code>.env</code> on this server. The Gemini key is set on the AI server (<code>ProjectY\\.env</code>), never here.');

        var html = '<div class="st-status-row">' +
                '<span><span class="st-status-title">' + title + '</span> · ' + esc(text) + '</span>' +
                '<button type="button" class="btn btn-outline-secondary btn-xs" id="aiCheckBtn">Check again</button>' +
            '</div>' +
            (more ? '<div class="st-status-more">' + esc(more) + '</div>' : '') +
            '<details class="st-tech"><summary>Technical details</summary><div>' + tech.join('<br>') + '</div></details>';
        // Redrawn only when the words change: a screen reader reads the box again, and open details would close.
        if (html === shownAi) return $('#aiCheckBtn').prop('disabled', false);
        shownAi = html;
        $('#aiStatus').attr('class', 'st-status' + (tone ? ' is-' + tone : '')).html(html);
    }

    function loadAiStatus() {
        $('#aiCheckBtn').prop('disabled', true);
        $.getJSON(urls.ai).done(showAiStatus).fail(function () {
            lastAi = null; shownAi = '';
            $('#aiStatus').attr('class', 'st-status is-warn').text('Couldn’t read the AI server’s status. Reload the page to try again.');
        });
    }

    if (document.getElementById('aiStatus')) {
        loadAiStatus();
        $('#aiStatus').on('click', '#aiCheckBtn', loadAiStatus);
    }

    refresh();

    // ---- Tools: each runs at once and reports in plain words -----------------------------------------------
    function result($box, tone, html) { $box.attr('class', 'st-result' + (tone ? ' is-' + tone : '')).html(html); }

    // Why a tool failed, in words, with the server's message folded away.
    function failure(xhr, what) {
        if (xhr.status === 429) return 'Too many tries in a minute. Wait a moment, then try again.';
        var msg = (xhr.responseJSON && xhr.responseJSON.message) || ('HTTP ' + xhr.status);
        return what + '<details class="st-tech"><summary>Technical details</summary><div>' + esc(msg) + '</div></details>';
    }

    function busy($btn, label) { $btn.prop('disabled', true).data('label', $btn.html()).html(spinner + label); }
    function done($btn) { $btn.prop('disabled', false).html($btn.data('label')); if (window.feather) feather.replace(); }

    $('#checkBtn').on('click', function () {
        var $btn = $(this), $out = $('#searchResult');
        busy($btn, 'Checking…');
        result($out, '', 'Comparing the search index with the catalogue…');
        $.getJSON(urls.scan).done(function (r) {
            if (r.in_sync) {
                result($out, 'ok', '<strong>Search is up to date.</strong> All ' + count(r.db_rows, 'record', 'records') + ' match the catalogue.');
                return;
            }
            var chips = [];
            if (r.orphans) chips.push(count(r.orphans, 'deleted record', 'deleted records') + ' still in search');
            if (r.stale) chips.push(count(r.stale, 'record', 'records') + ' with an old published or deleted status');
            if (r.missing) chips.push(count(r.missing, 'record', 'records') + ' missing from search');
            result($out, 'warn', '<strong>Search is out of date:</strong> ' + count(r.drift, 'record needs', 'records need') +
                ' fixing. Press <strong>Rebuild search index</strong> to fix ' + (r.drift === 1 ? 'it' : 'them') + '.' +
                '<div class="st-chips">' + chips.map(function (c) { return '<span class="st-chip">' + esc(c) + '</span>'; }).join('') + '</div>');
        }).fail(function (xhr) {
            result($out, 'bad', failure(xhr, '<strong>Couldn’t check the search index.</strong> The search service may be stopped.'));
        }).always(function () { done($btn); });
    });

    $('#rebuildBtn').on('click', function () {
        var $btn = $(this), $out = $('#searchResult');

        function rebuild() {
            busy($btn, 'Rebuilding…');
            $('#checkBtn').prop('disabled', true);
            result($out, '', 'Rebuilding the search index. This can take a minute or two.');
            $.ajax({ type: 'POST', url: urls.reindex, data: { _token: token }, dataType: 'json' }).done(function (r) {
                result($out, 'ok', '<strong>Rebuilt:</strong> ' + count(r.rows, 'record was', 'records were') + ' copied into search. ' +
                    'Search takes a moment to catch up; press <strong>Check search</strong> in a minute to confirm.');
            }).fail(function (xhr) {
                result($out, 'bad', failure(xhr, '<strong>The rebuild didn’t finish.</strong> The search service may be stopped.'));
            }).always(function () { done($btn); $('#checkBtn').prop('disabled', false); });
        }

        if (!window.Swal) return rebuild();
        Swal.fire({
            title: 'Rebuild the search index?',
            text: 'Every record is copied into search again. It takes a minute or two, and until it finishes OPAC searches may miss some records.',
            icon: 'question', showCancelButton: true, reverseButtons: true, focusCancel: true,
            confirmButtonText: 'Rebuild now', cancelButtonText: 'Cancel',
            confirmButtonColor: 'var(--t-primary)', cancelButtonColor: '#6c757d'   // the template paints Cancel red
        }).then(function (answer) { if (answer.isConfirmed) rebuild(); });
    });

    $('#sitemapBtn').on('click', function () {
        var $btn = $(this), $out = $('#sitemapResult');
        busy($btn, 'Rebuilding…');
        result($out, '', 'Rebuilding the sitemap…');
        $.ajax({ type: 'POST', url: urls.sitemap, data: { _token: token }, dataType: 'json' }).done(function (r) {
            var line = 'Built ' + r.when + ' · ' + count(r.urls, 'address', 'addresses') + ' listed';
            $('#sitemapMeta').text(line);
            $('#sitemapWarn').prop('hidden', r.host_ok);
            $('#sitemapView').prop('hidden', false);
            $('#sitemapTile').toggleClass('is-warn', !r.host_ok)
                .find('.st-tile-value').text(r.host_ok ? count(r.urls, 'address', 'addresses') + ' · built ' + r.when : 'Wrong address: rebuild it');
            result($out, r.host_ok ? 'ok' : 'warn', r.host_ok
                ? '<strong>Rebuilt:</strong> ' + count(r.urls, 'address', 'addresses') + ' listed.'
                : '<strong>Rebuilt, but on another address.</strong> Check <code>APP_URL</code> in <code>.env</code>.');
        }).fail(function (xhr) {
            result($out, 'bad', failure(xhr, '<strong>Couldn’t rebuild the sitemap.</strong>'));
        }).always(function () { done($btn); });
    });

    $('#cacheBtn').on('click', function () {
        var $btn = $(this), $out = $('#cacheResult');
        busy($btn, 'Refreshing…');
        $.ajax({ type: 'POST', url: urls.cache, data: { _token: token }, dataType: 'json' }).done(function (r) {
            result($out, 'ok', '<strong>Done.</strong> The home page and the dashboard now show the latest data. ' +
                '<span class="st-quiet">(' + count(r.cache_entries, 'cached item', 'cached items') + ' and ' +
                count(r.views_cleared, 'compiled template', 'compiled templates') + ' cleared)</span>');
        }).fail(function (xhr) {
            result($out, 'bad', failure(xhr, '<strong>Couldn’t refresh the cached pages.</strong> Try again.'));
        }).always(function () { done($btn); });
    });
});
</script>
@endsection
