@extends("admin.layouts.master")

{{-- Administration → Utilities → Theme: the appearance every staff member starts with
     (AppearanceController::editDefault). Staff who chose their own under Appearance keep it. --}}

@section('css')
    <style>
      .ac-head { margin-bottom: 18px; }
      .ac-head h4 { font-weight: 700; color: var(--ui-text, #22283b); margin: 0; }
      .ac-head p  { color: var(--ui-muted, #8a94a6); margin: 2px 0 0; font-size: 0.8125rem; max-width: 760px; }
      .ap-now { display: flex; flex-wrap: wrap; align-items: center; gap: 8px 16px; font-size: 0.8125rem; color: var(--ui-text, #22283b); background: var(--ui-surface-2, #f4f6fa); border: 1px solid var(--ui-border, #e3e7ef); border-radius: 10px; padding: 9px 12px; margin-bottom: 16px; }
      .ap-now p { margin: 0; flex: 1 1 420px; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('utilities') }}">Utilities</a></li>
            <li class="breadcrumb-item active" aria-current="page">Theme</li>
        </ol>
    </nav>

    <div class="ac-head">
        <h4>Theme</h4>
        <p>The appearance every staff member starts with. Anyone can still choose their own under <strong>Appearance</strong> in the
           profile menu; this default doesn't change theirs. This page always shows the default you are editing; a change
           previews here at once, and reaches other staff when you save.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    @if($personal)
        <div class="ap-now" id="personalNotice">
            <p>You use your own appearance ({{ \App\Services\Appearance::describe($personal) }}), so the rest of the admin
               keeps it for you; only this page shows the library default.</p>
            <form method="POST" action="{{ url('/admin/appearance/reset') }}" class="m-0">
                @csrf
                <input type="hidden" name="from" value="theme">
                <button type="submit" class="btn btn-outline-primary btn-sm">Follow the library default myself</button>
            </form>
        </div>
    @endif

    @include('admin.partials.appearance-form', [
        'action' => route('utilities.theme'),
        'saveLabel' => 'Save as the library default',
        'secondary' => ['label' => 'Back to original', 'action' => route('utilities.theme'), 'fields' => ['reset' => 1]],
    ])
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('js/admin/appearance.js') }}"></script>
@endsection
