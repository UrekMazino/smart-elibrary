@extends("admin.layouts.master")

{{-- Administration → Utilities → Theme: the appearance every staff member starts with
     (AppearanceController::editDefault). Staff who chose their own under Appearance keep it. --}}

@section('css')
    <style>
      .ac-head { margin-bottom: 18px; }
      .ac-head h4 { font-weight: 700; color: #22283b; margin: 0; }
      .ac-head p  { color: #8a94a6; margin: 2px 0 0; font-size: 0.8125rem; max-width: 760px; }
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
           profile menu; this default doesn't change theirs. The preview shows on this page for you only until you save.</p>
    </div>

    @if(session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

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
