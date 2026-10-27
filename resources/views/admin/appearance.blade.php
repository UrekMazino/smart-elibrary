@extends("admin.layouts.master")

{{-- A staff member's own appearance (AppearanceController::edit), from Appearance in the profile menu. --}}

@section('css')
    <style>
      .ac-head { margin-bottom: 18px; }
      .ac-head h4 { font-weight: 700; color: var(--ui-text, #22283b); margin: 0; }
      .ac-head p  { color: var(--ui-muted, #8a94a6); margin: 2px 0 0; font-size: 0.8125rem; max-width: 760px; }
      .ap-now { font-size: 0.8125rem; color: var(--ui-text, #22283b); background: var(--ui-surface-2, #f4f6fa); border: 1px solid var(--ui-border, #e3e7ef); border-radius: 10px; padding: 9px 12px; margin-bottom: 16px; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <div class="ac-head">
        <h4>Appearance</h4>
        <p>How the admin looks for you: colours, background, contrast, status colours, text size, motion and font. Only you see your choice, and it follows
           you to any computer you sign in on.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <div class="ap-now">
        @if($personal)
            You use your own appearance. <strong>Use the library default</strong> goes back to the library's
            ({{ \App\Services\Appearance::describe($default) }}).
        @else
            You follow the library default: {{ \App\Services\Appearance::describe($default) }}. Saving here gives you your own.
        @endif
    </div>

    @include('admin.partials.appearance-form', [
        'action' => route('appearance'),
        'secondary' => $personal ? ['label' => 'Use the library default', 'action' => url('/admin/appearance/reset')] : null,
    ])
</div>
@endsection

@section('js')
<script src="{{ versionedAsset('js/admin/appearance.js') }}"></script>
@endsection
