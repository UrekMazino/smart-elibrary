@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <style>
        #syncTbl { font-size: 13px; width: 100% !important; }
        #syncTbl thead th {
            font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
            color: #7987a1; white-space: nowrap; padding: 9px 10px;
        }
        #syncTbl td { vertical-align: middle; }
        .sync-stat { display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 12px; }
        .sync-stat .card { border: 1px solid #eef0f2; border-radius: 8px; padding: 10px 16px; min-width: 120px; }
        .sync-stat .n { font-size: 22px; font-weight: 700; line-height: 1; }
        .sync-stat .l { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #7987a1; }
        .sync-filters { display: flex; gap: 8px; flex-wrap: wrap; align-items: end; margin-bottom: 10px; }
        .sync-filters label { font-size: 11px; color: #7987a1; display: block; margin-bottom: 2px; }
        .sync-filters select { font-size: 13px; padding: 4px 8px; border-radius: 6px; border: 1px solid #dfe3e8; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item">Administration</li>
            <li class="breadcrumb-item active" aria-current="page">AI Sync Monitor</li>
        </ol>
    </nav>

    <div class="row">
        <div class="col-12 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-2">
                        <div>
                            <h6 class="card-title mb-1">AI Sync Monitor</h6>
                            <p class="text-muted mb-0" style="font-size: 12.5px;">
                                Catalog changes and whether the AI (RAG) search index is in sync. Re-sync anything
                                that needs re-indexing — a <strong>PDF change</strong> is heavy (re-OCR); a metadata
                                edit is light. <strong>Run sync now</strong> applies queued items immediately.
                            </p>
                        </div>
                        <button type="button" class="btn btn-sm btn-primary" id="runSyncBtn"
                                title="Ask the AI gateway to index everything currently queued">
                            <i data-feather="play" class="icon-sm"></i> Run sync now
                        </button>
                    </div>

                    <div class="sync-stat">
                        <div class="card"><div class="n text-info">{{ $counts['pending'] }}</div><div class="l">Pending</div></div>
                        <div class="card"><div class="n text-danger">{{ $counts['failed'] }}</div><div class="l">Failed</div></div>
                        <div class="card"><div class="n text-success">{{ $counts['synced'] }}</div><div class="l">Synced</div></div>
                    </div>

                    <div class="sync-filters">
                        <div>
                            <label for="fChange">Change type</label>
                            <select id="fChange" class="form-select">
                                <option value="">All changes</option>
                                <option value="added">Added</option>
                                <option value="pdf">PDF changed</option>
                                <option value="updated">Edited (metadata)</option>
                                <option value="deleted">Removed</option>
                                <option value="published">Published</option>
                                <option value="unpublished">Held</option>
                                <option value="manual">Manual re-index</option>
                            </select>
                        </div>
                        <div>
                            <label for="fStatus">Sync status</label>
                            <select id="fStatus" class="form-select">
                                <option value="">All statuses</option>
                                <option value="pending">Pending</option>
                                <option value="failed">Failed</option>
                                <option value="synced">Synced</option>
                                <option value="removed">Removed</option>
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="syncTbl" class="table table-hover table-sm align-middle mb-0">
                            <thead>
                                <tr>
                                    <th>Catalog</th>
                                    <th style="width: 130px">Change</th>
                                    <th style="width: 150px">Changed</th>
                                    <th style="width: 150px">Synced</th>
                                    <th style="width: 110px">Status</th>
                                    <th style="width: 96px" class="text-end">Action</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(document).ready(function () {
    var table = $('#syncTbl').DataTable({
        processing: true,
        serverSide: true,
        order: [],   // server orders by changed-desc
        ajax: {
            url: '{{ url("/admin/ai-sync-monitor/json") }}',
            data: function (d) {
                d.change = $('#fChange').val();
                d.sync_status = $('#fStatus').val();
            }
        },
        columns: [
            { data: 'title', orderable: false, render: function (d, t, row) {
                return '<span class="text-muted">#' + row.catalog_id + '</span> ' + d;
            }},
            { data: 'change', orderable: false },
            { data: 'when', orderable: false },
            { data: 'synced', orderable: false },
            { data: 'status', orderable: false },
            { data: 'resync', orderable: false, className: 'text-end', render: function (d) {
                return '<button type="button" class="btn btn-sm btn-outline-info resync-btn" data-id="' + d + '">Re-sync</button>';
            }},
        ],
        drawCallback: function () { if (window.feather) feather.replace(); },
        language: { search: '', searchPlaceholder: 'Search title or catalog id…' }
    });

    $('#fChange, #fStatus').on('change', function () { table.ajax.reload(); });

    // "Run sync now": ask the gateway to index what's queued. Each call is bounded (the gateway
    // shares one embedding model with search), so we keep going while items remain.
    $('#runSyncBtn').on('click', function () {
        var btn = $(this), done = 0, failed = 0, rounds = 0, MAX_ROUNDS = 40;
        btn.prop('disabled', true);
        Swal.fire({
            title: 'Indexing…', text: 'Applying queued changes.',
            allowOutsideClick: false, didOpen: function () { Swal.showLoading(); }
        });

        function round() {
            rounds++;
            $.ajax({
                url: '{{ route("ai-sync-monitor-run") }}',
                method: 'POST',
                data: { _token: '{{ csrf_token() }}', limit: 25 },
                success: function (res) {
                    done   += (res.processed || 0);
                    failed += (res.failed || 0);
                    var left = res.remaining || 0;
                    if (left > 0 && rounds < MAX_ROUNDS) {
                        Swal.update({ text: 'Indexed ' + done + '… ' + left + ' remaining.' });
                        return round();
                    }
                    btn.prop('disabled', false);
                    Swal.fire({
                        icon: failed ? 'warning' : 'success',
                        title: 'Sync complete',
                        text: 'Indexed ' + done + ' record(s)'
                              + (failed ? ', ' + failed + ' failed' : '')
                              + (left > 0 ? '. ' + left + ' still queued — click again.' : '.')
                    }).then(function () { location.reload(); });
                },
                error: function (xhr) {
                    btn.prop('disabled', false);
                    var d = (xhr.responseJSON && xhr.responseJSON.detail)
                            || 'The AI gateway did not respond. Is rag_api.py running?';
                    Swal.fire('Sync failed', d, 'error');
                }
            });
        }
        round();
    });

    // Per-row re-sync: enqueue this catalog for AI re-indexing, then refresh the table.
    $('#syncTbl').on('click', '.resync-btn', function () {
        var id = $(this).data('id');
        $.ajax({
            url: '{{ url("/admin/ai-sync-monitor/reindex") }}/' + id,   // the monitor's own route (no Cataloging access needed)
            method: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function () {
                Swal.fire({ title: 'Queued', text: 'Catalog #' + id + ' queued for AI re-sync.', icon: 'success', timer: 1200, showConfirmButton: false });
                table.ajax.reload(null, false);
            },
            error: function () { Swal.fire('Error', 'Could not queue #' + id + ' for re-sync.', 'error'); }
        });
    });
});
</script>
@endsection
