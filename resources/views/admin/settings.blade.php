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
                        <p class="text-muted mb-4" style="font-size: 13px;">
                            When <strong>on</strong>, materials without an uploaded cover show an auto-generated
                            placeholder cover. When <strong>off</strong>, they show the default
                            &ldquo;no&nbsp;preview&rdquo; image. Uploaded covers are always shown either way.
                        </p>

                        <button type="submit" class="btn btn-primary">Save Settings</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
