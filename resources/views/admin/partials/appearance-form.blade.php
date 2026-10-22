{{-- The Appearance form (App\Services\Appearance), shared by Appearance (a staff member's own) and
     Utilities → Theme (the library default). Each change previews at once on this page
     (public/js/admin/appearance.js); Save keeps it.
     $action: where it posts; $prefs: the current choice; $secondary: [label, action, field] or null. --}}
@php $preview = \App\Services\Appearance::previewData(); @endphp
<style>
  .ap-card { border: 1px solid #eef0f4; border-radius: 12px; box-shadow: 0 1px 2px rgba(20, 30, 60, .04); }
  .ap-legend { font-size: 0.9375rem; font-weight: 700; color: #22283b; margin-bottom: 2px; float: none; width: auto; }
  .ap-note { color: #6b7489; font-size: 0.8125rem; margin: 0 0 12px; max-width: 760px; }
  .ap-swatches { display: grid; grid-template-columns: repeat(auto-fill, minmax(190px, 1fr)); gap: 10px; }
  .ap-swatch { display: flex; align-items: center; gap: 10px; padding: 10px 12px; border: 1px solid #e3e7ef; border-radius: 10px; cursor: pointer; background: #fff; margin: 0; }
  .ap-swatch:hover { border-color: #c4cad8; }
  .btn-check:checked + .ap-swatch { border-color: var(--t-primary); box-shadow: 0 0 0 2px var(--t-primary); }
  .btn-check:focus-visible + .ap-swatch { outline: 3px solid var(--t-primary); outline-offset: 2px; }
  .ap-dot { flex: 0 0 26px; height: 26px; border-radius: 50%; border: 2px solid #fff; box-shadow: 0 0 0 1px #d5dae4; }
  .ap-name { flex: 1; font-weight: 600; color: #22283b; font-size: 0.84375rem; }
  .ap-aa { font-size: 0.6875rem; font-weight: 700; color: #0f5132; background: #d1e7dd; border-radius: 6px; padding: 1px 6px; }
  .ap-low { font-size: 0.6875rem; font-weight: 700; color: #6b4e00; background: #fff3cd; border-radius: 6px; padding: 1px 6px; }
  .ap-custom input[type=color] { width: 30px; height: 30px; padding: 0; border: 0; background: none; cursor: pointer; }
  .ap-adjust { font-size: 0.8125rem; color: #6b7489; margin: 10px 0 0; min-height: 1.2em; }
  .ap-options { display: flex; flex-wrap: wrap; gap: 8px 22px; }
  .ap-samples { display: flex; flex-wrap: wrap; gap: 6px; margin-top: 8px; }
  .ap-font-sample { font-size: 1rem; color: #22283b; margin: 2px 0 0 1.6em; }
  .ap-actions { display: flex; flex-wrap: wrap; gap: 10px; align-items: center; }
</style>

<form method="post" action="{{ $action }}" id="appearanceForm" data-preview="{{ json_encode($preview) }}">
    @csrf

    <div class="card ap-card mb-3">
        <div class="card-body">
            <fieldset>
                <legend class="ap-legend">Colour theme</legend>
                <p class="ap-note">The colour of buttons, links and the selected menu item. <span class="ap-aa">AA</span> means
                    text on it stays readable: a contrast of 4.5:1 or more (WCAG 2.2). The original indigo is a little below that.</p>
                <div class="ap-swatches">
                    @foreach(\App\Services\Appearance::ACCENTS as $key => [$label])
                        @php $p = $preview['accents'][$key]; @endphp
                        <input type="radio" class="btn-check" name="accent" id="accent-{{ $key }}" value="{{ $key }}" @checked($prefs['accent'] === $key)>
                        <label class="ap-swatch" for="accent-{{ $key }}">
                            <span class="ap-dot" style="background: {{ $p['vars']['--t-primary'] }}"></span>
                            <span class="ap-name">{{ $label }}</span>
                            @if($p['contrast'] >= $preview['min'])
                                <span class="ap-aa" title="White text on it: {{ $p['contrast'] }}:1">AA</span>
                            @else
                                <span class="ap-low" title="White text on it: {{ $p['contrast'] }}:1">{{ $p['contrast'] }}:1</span>
                            @endif
                        </label>
                    @endforeach
                    <input type="radio" class="btn-check" name="accent" id="accent-custom" value="custom" @checked($prefs['accent'] === 'custom')>
                    <label class="ap-swatch ap-custom" for="accent-custom">
                        <input type="color" name="custom" id="customColour" value="{{ $prefs['custom'] ?? '#6571ff' }}" aria-label="Custom colour">
                        <span class="ap-name">Custom colour</span>
                    </label>
                </div>
                <p class="ap-adjust" id="apAdjust" aria-live="polite"></p>
            </fieldset>
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
