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

    <div class="row">
        <div class="col-md-8 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">Search index</h6>
                    <p class="text-muted mb-3" style="font-size: 13px;">
                        OPAC search runs on a Meilisearch index built from the catalog. Deleting or
                        re-importing records can leave stale entries behind, which makes the result
                        count disagree with the results actually shown. <strong>Re-sync</strong>
                        rebuilds the index from the database &mdash; safe to run any time.
                    </p>

                    <button type="button" id="reindexBtn" class="btn btn-outline-primary">
                        <i data-feather="refresh-cw" class="icon-sm me-1"></i>Re-sync search index
                    </button>
                    <span id="reindexStatus" class="ms-2" style="font-size: 13px;"></span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
$(function () {
    if (window.feather) feather.replace();

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
