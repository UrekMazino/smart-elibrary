{{-- The Appearance form (App\Services\Appearance), shared by Appearance (a staff member's own) and
     Utilities → Theme (the library default). Each change previews at once on this page: the server works
     out the colours (AppearanceController::preview) and public/js/admin/appearance.js applies them; Save keeps them.
     $action: where it posts; $prefs: the current choice; $secondary: [label, action, fields] or null. --}}
@php $options = \App\Services\Appearance::options(); @endphp
<style>
  .ap-card { border: 1px solid var(--ui-border, #eef0f4); border-radius: 12px; box-shadow: 0 1px 2px rgba(20, 30, 60, .04); }
  .ap-legend { font-size: 0.9375rem; font-weight: 700; color: var(--ui-text, #22283b); margin-bottom: 2px; float: none; width: auto; }
  .ap-note { color: var(--ui-text-2, #6b7489); font-size: 0.8125rem; margin: 0 0 12px; max-width: 760px; }
  .ap-group { font-size: 0.6875rem; font-weight: 700; letter-spacing: .4px; text-transform: uppercase; color: var(--ui-muted, #7987a1); margin: 14px 0 6px; }
  .ap-swatches { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
  .ap-swatch { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid var(--ui-border, #e3e7ef); border-radius: 10px; cursor: pointer; background: var(--ui-surface, #fff); margin: 0; }
  .ap-swatch:hover { border-color: var(--ui-border, #c4cad8); }
  .btn-check:checked + .ap-swatch { border-color: var(--t-primary); box-shadow: 0 0 0 2px var(--t-primary); }
  .btn-check:focus-visible + .ap-swatch { outline: 3px solid var(--t-primary); outline-offset: 2px; }
  .ap-dot { flex: 0 0 26px; height: 26px; border-radius: 50%; border: 2px solid var(--ui-surface, #fff); box-shadow: 0 0 0 1px var(--ui-border, #d5dae4); }
  .ap-bg { flex: 0 0 44px; height: 32px; border-radius: 7px; box-shadow: 0 0 0 1px var(--ui-border, #d5dae4); display: flex; align-items: center; justify-content: center; overflow: hidden; }
  .ap-bg span { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 18px; border-radius: 4px; font-size: 0.6875rem; font-weight: 700; }
  .ap-bg-split { width: 22px; height: 32px; display: flex; align-items: center; justify-content: center; }
  .ap-name { flex: 1; font-weight: 600; color: var(--ui-text, #22283b); font-size: 0.84375rem; }
  .ap-aa { font-size: 0.6875rem; font-weight: 700; color: var(--ui-success-ink, #0f5132); background: var(--ui-success-soft, #d1e7dd); border-radius: 6px; padding: 1px 6px; }
  .ap-low { font-size: 0.6875rem; font-weight: 700; color: var(--ui-warning-ink, #6b4e00); background: var(--ui-warning-soft, #fff3cd); border-radius: 6px; padding: 1px 6px; }
  .ap-custom input[type=color] { width: 30px; height: 30px; padding: 0; border: 0; background: none; cursor: pointer; }
  .ap-adjust { font-size: 0.8125rem; color: var(--ui-text-2, #6b7489); margin: 10px 0 0; min-height: 1.2em; }
  .ap-options { display: flex; flex-wrap: wrap; gap: 8px 22px; }
  .ap-samples { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
  .ap-font-sample { font-size: 1rem; color: var(--ui-text, #22283b); margin: 2px 0 0 1.6em; }
  .ap-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
</style>

<form method="post" action="{{ $action }}" id="appearanceForm" data-preview-url="{{ url('/admin/appearance/preview') }}">
    @csrf

    <div class="card ap-card mb-3">
        <div class="card-body">
            <fieldset>
                <legend class="ap-legend">Colour theme</legend>
                <p class="ap-note">The colour of buttons, links and the selected menu item. <span class="ap-aa">AA</span> means
                    text on it stays readable: a contrast of 4.5:1 or more (WCAG 2.2). The original indigo is a little below that.</p>
                <div class="ap-swatches">
                    @foreach($options['accents'] as $key => $a)
                        <input type="radio" class="btn-check" name="accent" id="accent-{{ $key }}" value="{{ $key }}" @checked($prefs['accent'] === $key)>
                        <label class="ap-swatch" for="accent-{{ $key }}">
                            <span class="ap-dot" style="background: {{ $a['colour'] }}"></span>
                            <span class="ap-name">{{ $a['label'] }}</span>
                            @if($a['contrast'] >= $options['min'])
                                <span class="ap-aa" title="White text on it: {{ $a['contrast'] }}:1">AA</span>
                            @else
                                <span class="ap-low" title="White text on it: {{ $a['contrast'] }}:1">{{ $a['contrast'] }}:1</span>
                            @endif
                        </label>
                    @endforeach
                    <input type="radio" class="btn-check" name="accent" id="accent-custom" value="custom" @checked($prefs['accent'] === 'custom')>
                    <label class="ap-swatch ap-custom" for="accent-custom">
                        <input type="color" name="custom" id="customColour" value="{{ $prefs['custom'] ?? '#6571ff' }}" aria-label="Custom accent colour">
                        <span class="ap-name">Custom colour</span>
                    </label>
                </div>
            </fieldset>
        </div>
    </div>

    <div class="card ap-card mb-3">
        <div class="card-body">
            <fieldset class="mb-4">
                <legend class="ap-legend">Background</legend>
                <p class="ap-note">The page and its panels. <strong>Warm cream</strong> and <strong>Cool grey</strong> cut the glare of white
                    (cream is often easier for readers with dyslexia); <strong>Dim</strong> is a softer dark. A custom colour is made light
                    or dark enough for text on it to stay readable.</p>
                @php
                    $groups = ['Light' => ['standard', 'white', 'cream', 'cool'], 'Dark' => ['dark', 'dim', 'black'], 'Other' => ['auto']];
                @endphp
                @foreach($groups as $group => $keys)
                    <p class="ap-group">{{ $group }}</p>
                    <div class="ap-swatches">
                        @foreach($keys as $key)
                            @php $b = $options['backgrounds'][$key]; @endphp
                            <input type="radio" class="btn-check" name="background" id="background-{{ $key }}" value="{{ $key }}" @checked($prefs['background'] === $key)>
                            <label class="ap-swatch" for="background-{{ $key }}">
                                @if($key === 'auto')
                                    <span class="ap-bg" aria-hidden="true">
                                        <span class="ap-bg-split" style="background: {{ $b['page'] }}; color: #000;">A</span>
                                        <span class="ap-bg-split" style="background: {{ $b['darkPage'] }}; color: #fff;">a</span>
                                    </span>
                                @else
                                    <span class="ap-bg" style="background: {{ $b['page'] }};" aria-hidden="true"><span style="background: {{ $b['surface'] }}; color: {{ $b['text'] }};">Aa</span></span>
                                @endif
                                <span class="ap-name">{{ $b['label'] }}</span>
                            </label>
                        @endforeach
                        @if($group === 'Other')
                            <input type="radio" class="btn-check" name="background" id="background-custom" value="custom" @checked($prefs['background'] === 'custom')>
                            <label class="ap-swatch ap-custom" for="background-custom">
                                <input type="color" name="background_custom" id="customBackground" value="{{ $prefs['background_custom'] ?? '#f6efdf' }}" aria-label="Custom background colour">
                                <span class="ap-name">Custom background</span>
                            </label>
                        @endif
                    </div>
                @endforeach
            </fieldset>

            <fieldset class="mb-4">
                <legend class="ap-legend">Sidebar</legend>
                <p class="ap-note">With a dark background the sidebar is always dark.</p>
                <div class="ap-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sidebar" id="sidebar-dark" value="dark" @checked($prefs['sidebar'] === 'dark')>
                        <label class="form-check-label" for="sidebar-dark">Dark</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="sidebar" id="sidebar-light" value="light" @checked($prefs['sidebar'] === 'light')>
                        <label class="form-check-label" for="sidebar-light">Light, like the page</label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="ap-legend">Contrast</legend>
                <p class="ap-note">High contrast uses pure white or black backgrounds (by the background's light or dark), text and links at
                    7:1 or more (WCAG AAA), stronger borders, underlined links and a thicker focus outline.</p>
                <div class="ap-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="contrast" id="contrast-standard" value="standard" @checked($prefs['contrast'] === 'standard')>
                        <label class="form-check-label" for="contrast-standard">Standard</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="contrast" id="contrast-high" value="high" @checked($prefs['contrast'] === 'high')>
                        <label class="form-check-label" for="contrast-high">High contrast</label>
                    </div>
                </div>
            </fieldset>
            <p class="ap-adjust" id="apAdjust" aria-live="polite"></p>
        </div>
    </div>

    <div class="card ap-card mb-3">
        <div class="card-body">
            <fieldset class="mb-4">
                <legend class="ap-legend">Status colours</legend>
                <p class="ap-note">The colours of states such as Approved, Declined and Pending. The colour-blind-safe set uses colours
                    people with red–green colour blindness can tell apart, dark enough for white text. Statuses always show their words too.</p>
                <div class="ap-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status-standard" value="standard" @checked($prefs['status'] === 'standard')>
                        <label class="form-check-label" for="status-standard">Standard</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="status" id="status-colorblind" value="colorblind" @checked($prefs['status'] === 'colorblind')>
                        <label class="form-check-label" for="status-colorblind">Colour-blind safe</label>
                    </div>
                </div>
                <div class="ap-samples" aria-hidden="true">
                    <span class="badge bg-success">Approved</span>
                    <span class="badge bg-danger">Declined</span>
                    <span class="badge bg-warning">Pending</span>
                    <span class="badge bg-info">Information</span>
                    <span class="badge bg-primary">Selected</span>
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="ap-legend">Text size</legend>
                <p class="ap-note">Makes text, buttons and spacing larger on every admin page. Your browser's own zoom (Ctrl and +) still works on top.</p>
                <div class="btn-group" role="group" aria-label="Text size">
                    @foreach(\App\Services\Appearance::TEXT as $size)
                        <input type="radio" class="btn-check" name="text" id="text-{{ $size }}" value="{{ $size }}" @checked($prefs['text'] === $size)>
                        <label class="btn btn-outline-primary" for="text-{{ $size }}">{{ $size }}%{{ $size === 100 ? ' (standard)' : '' }}</label>
                    @endforeach
                </div>
            </fieldset>

            <fieldset class="mb-4">
                <legend class="ap-legend">Motion</legend>
                <p class="ap-note">Sliding menus, fades and hover effects. Reducing them helps with motion sensitivity, dizziness and distraction.</p>
                <div class="ap-options">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="motion" id="motion-auto" value="auto" @checked($prefs['motion'] === 'auto')>
                        <label class="form-check-label" for="motion-auto">Follow my device (reduce when its accessibility settings ask for it)</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="motion" id="motion-reduce" value="reduce" @checked($prefs['motion'] === 'reduce')>
                        <label class="form-check-label" for="motion-reduce">Always reduce motion</label>
                    </div>
                </div>
            </fieldset>

            <fieldset>
                <legend class="ap-legend">Font</legend>
                <p class="ap-note">Atkinson Hyperlegible was designed by the Braille Institute for readers with low vision: letters that look alike elsewhere are easy to tell apart.</p>
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="font" id="font-standard" value="standard" @checked($prefs['font'] === 'standard')>
                    <label class="form-check-label" for="font-standard">Standard (Roboto)</label>
                    <p class="ap-font-sample" style="font-family: Roboto, Helvetica, sans-serif;">Il1 O0 rn m · 2008 Annual Report</p>
                </div>
                <div class="form-check mt-2">
                    <input class="form-check-input" type="radio" name="font" id="font-readable" value="readable" @checked($prefs['font'] === 'readable')>
                    <label class="form-check-label" for="font-readable">Easier to read (Atkinson Hyperlegible)</label>
                    <p class="ap-font-sample" style="font-family: 'Atkinson Hyperlegible', sans-serif;">Il1 O0 rn m · 2008 Annual Report</p>
                </div>
            </fieldset>
        </div>
    </div>

    <div class="ap-actions mb-4">
        <button type="submit" class="btn btn-primary"><i data-feather="check" class="icon-sm me-1"></i>{{ $saveLabel ?? 'Save' }}</button>
        @if($secondary)
            <button type="submit" class="btn btn-outline-secondary" form="appearanceSecondary">{{ $secondary['label'] }}</button>
        @endif
        <span class="text-muted" style="font-size: 0.8125rem;">Changes show on this page straight away; they are kept only when you save.</span>
    </div>
</form>

@if($secondary)
    <form method="post" action="{{ $secondary['action'] }}" id="appearanceSecondary">
        @csrf
        @foreach($secondary['fields'] ?? [] as $name => $value)<input type="hidden" name="{{ $name }}" value="{{ $value }}">@endforeach
    </form>
@endif
