@extends("admin.layouts.master")

{{-- Import report of a batch (CatalogingController::batchReport): the records skipped at import,
     with their original MARC to download, fix and import again, and the records imported but
     flagged for review. Saving an edit re-checks a flagged record; "Mark reviewed" accepts it as
     it is. The rules are in App\Services\CatalogRecordCheck. --}}

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.css') }}">
    <style>
      .ir-meta { font-size: 0.78125rem; color: var(--ui-muted, #7987a1); }
      .ir-meta strong { color: var(--ui-text, #33415c); font-weight: 600; }
      .ir-stats { display: flex; gap: 12px; flex-wrap: wrap; margin: 16px 0 8px; }
      .ir-stat { flex: 1 1 140px; border: 1px solid var(--ui-border, #eef0f2); border-radius: 8px; padding: 10px 14px; }
      .ir-stat .n { font-size: 1.375rem; font-weight: 700; color: var(--ui-text, #22283b); font-variant-numeric: tabular-nums; line-height: 1.2; }
      .ir-stat .l { font-size: 0.75rem; color: var(--ui-muted, #7987a1); }
      .ir-stat.ok .n { color: var(--ui-success-ink, #1d7a55); }
      .ir-stat.review .n { color: var(--ui-warning-ink, #a15c00); }
      .ir-stat.skip .n { color: var(--ui-danger-ink, #b42318); }
      .ir-section { margin-top: 28px; }
      .ir-section h6 { margin-bottom: 2px; }
      .ir-help { font-size: 0.78125rem; color: var(--ui-muted, #7987a1); margin-bottom: 10px; }
      .ir-table { font-size: 0.8125rem; width: 100% !important; }
      .ir-table thead th { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1); white-space: nowrap; padding: 9px 10px; border-bottom: 2px solid var(--ui-border, #eef0f2); }
      /* The template keeps table cells on one line; titles here must wrap. */
      .ir-table tbody td { padding: 7px 10px; vertical-align: top; white-space: normal; }
      .ir-table td.ir-nowrap { white-space: nowrap; }
      .ir-title { font-weight: 600; color: var(--ui-text, #22283b); overflow-wrap: anywhere; min-width: 200px; display: inline-block; }
      .ir-muted { color: var(--ui-muted-2, #a3adc2); font-size: 0.75rem; }
      .ir-problems { margin: 0; padding-left: 16px; min-width: 220px; }
      .ir-problems li.error { color: var(--ui-danger-ink, #b42318); }
      .ir-table tr.is-reviewed td { opacity: .6; }
    </style>
@endsection

@section('content')
@php
    $reviewOpen = $review->filter(fn ($i) => ! $i->reviewed_at && ! $i->record_deleted_at)->count();
    $reviewDone = $review->count() - $reviewOpen;
    $hasMarc    = $skipped->contains(fn ($i) => $i->has_marc);
@endphp
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/batch-list') }}">Batch list</a></li>
            <li class="breadcrumb-item"><a href="{{ url('/admin/batch-records/' . $batch->id) }}">Batch #{{ $batch->id }}</a></li>
            <li class="breadcrumb-item active" aria-current="page">Import report</li>
        </ol>
    </nav>

    <div class="card">
        <div class="card-body">
            <h6 class="card-title mb-1">Batch #{{ $batch->id }} — Import report</h6>
            <div class="ir-meta">
                <strong>{{ $batch->subject ?: 'No subject' }}</strong>
                @if($batch->file_name) · {{ $batch->file_name }} @endif
                · imported {{ optional($batch->created_at)->format('M j, Y g:i A') }}
            </div>

            @if(is_null($batch->imported_count))
                <div class="alert alert-secondary py-2 mt-3 mb-0" style="font-size: 0.8125rem;" role="alert">
                    This batch was imported before import reports were kept, so its skipped and flagged records weren't recorded.
                </div>
            @else
                <div class="ir-stats">
                    <div class="ir-stat"><div class="n">{{ number_format($batch->no_of_records) }}</div><div class="l">Records in the file</div></div>
                    <div class="ir-stat ok"><div class="n">{{ number_format($batch->imported_count) }}</div><div class="l">Imported</div></div>
                    <div class="ir-stat review"><div class="n">{{ number_format($reviewOpen) }}</div><div class="l">Imported, to review</div></div>
                    <div class="ir-stat skip"><div class="n">{{ number_format($batch->skipped_count) }}</div><div class="l">Skipped (not imported)</div></div>
                </div>
            @endif

            {{-- Skipped --}}
            <div class="ir-section">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h6>Skipped — not imported ({{ $skipped->count() }})</h6>
                        <div class="ir-help">A record is imported when it has a title and at least one of author, publication year or publisher. Fix these in the source system or in the downloaded file, then import them as a new batch.</div>
                    </div>
                    @if($hasMarc)
                        <a href="{{ route('batch-report-skipped', ['id' => $batch->id]) }}" class="btn btn-sm btn-outline-primary">
                            <i data-feather="download" class="icon-sm me-1"></i> Download skipped records (.mrc)
                        </a>
                    @endif
                </div>
                @if($skipped->isEmpty())
                    <p class="ir-muted mb-0">None.</p>
                @else
                    <div class="table-responsive">
                    <table id="tbl-skipped" class="table table-sm ir-table">
                        <thead><tr><th style="width: 70px">No.</th><th style="width: 140px">Control no.</th><th>Title</th><th style="width: 38%">Why</th></tr></thead>
                        <tbody>
                        @foreach($skipped as $i)
                            <tr>
                                <td data-order="{{ $i->record_no }}">{{ $i->record_no }}</td>
                                <td>{{ $i->control_number ?: '—' }}</td>
                                <td><span class="ir-title">{{ $i->title ?: '(no title)' }}</span></td>
                                <td><ul class="ir-problems">@foreach($i->problems as $p)<li class="{{ $p['level'] }}">{{ $p['message'] }}</li>@endforeach</ul></td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>

            {{-- Imported, flagged for review --}}
            <div class="ir-section">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2">
                    <div>
                        <h6>Imported — flagged for review ({{ $reviewOpen }} open{{ $reviewDone ? ', ' . $reviewDone . ' done' : '' }})</h6>
                        <div class="ir-help">Edit a record to complete it: saving checks it again and clears what was fixed. Or mark it reviewed to accept it as it is.</div>
                    </div>
                    @if($reviewDone)
                        <div class="form-check form-switch mt-1">
                            <input class="form-check-input" type="checkbox" id="showDone">
                            <label class="form-check-label" for="showDone" style="font-size: 0.8125rem;">Show reviewed</label>
                        </div>
                    @endif
                </div>
                @if($review->isEmpty())
                    <p class="ir-muted mb-0">None.</p>
                @else
                    <div class="table-responsive">
                    <table id="tbl-review" class="table table-sm ir-table">
                        <thead><tr><th style="width: 70px">No.</th><th>Title</th><th style="width: 34%">Problems</th><th style="width: 130px">Status</th><th style="width: 170px" class="text-end">Action</th></tr></thead>
                        <tbody>
                        @foreach($review as $i)
                            @php $done = $i->reviewed_at || $i->record_deleted_at; @endphp
                            <tr class="{{ $done ? 'is-reviewed' : '' }}" data-done="{{ $done ? 1 : 0 }}">
                                <td data-order="{{ $i->record_no }}">{{ $i->record_no }}</td>
                                <td>
                                    @if($i->record_deleted_at)
                                        <span class="ir-title">{{ $i->title }}</span>
                                    @else
                                        <a href="{{ route('show-record', ['id' => $i->catalog_id]) }}" class="ir-title">{{ $i->title }}</a>
                                    @endif
                                    <div class="ir-muted">Record #{{ $i->catalog_id }}{{ $i->control_number ? ' · 001 ' . $i->control_number : '' }}</div>
                                </td>
                                <td><ul class="ir-problems">@foreach($i->problems as $p)<li class="{{ $p['level'] }}">{{ $p['message'] }}</li>@endforeach</ul></td>
                                <td>
                                    @if($i->record_deleted_at)
                                        <span class="badge bg-secondary">Record deleted</span>
                                    @elseif($i->reviewed_at)
                                        <span class="badge bg-success">Reviewed</span>
                                        <div class="ir-muted">{{ $i->reviewed_at->format('M j, Y') }}{{ $i->reviewer ? ' · ' . $i->reviewer : '' }}</div>
                                    @else
                                        <span class="badge bg-warning text-dark">To review</span>
                                    @endif
                                </td>
                                <td class="text-end ir-nowrap">
                                    @if(! $done)
                                        @can('edit.catalog')
                                            <a href="{{ route('edit-record', ['id' => $i->catalog_id]) }}" class="btn btn-xs btn-outline-primary">Edit</a>
                                            <form method="post" action="{{ route('import-issue-reviewed', ['issue' => $i->id]) }}" class="d-inline">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-success">Mark reviewed</button>
                                            </form>
                                        @endcan
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    // Search and paging only when a list is long enough to need them.
    function opts(table) {
        var rows = $(table).find('tbody tr').length;
        var o = { pageLength: 25, paging: rows > 25, info: rows > 25, order: [[0, 'asc']], autoWidth: false, language: { search: 'Find:' } };
        if (rows <= 10) o.dom = 't';   // no search box (filtering stays on for "Show reviewed")
        return o;
    }
    if ($('#tbl-skipped').length) $('#tbl-skipped').DataTable(opts('#tbl-skipped'));
    if (!$('#tbl-review').length) return;

    // Reviewed (or deleted) records are hidden until "Show reviewed" is on.
    $.fn.dataTable.ext.search.push(function (settings, data, index) {
        if (settings.nTable.id !== 'tbl-review' || $('#showDone').is(':checked')) return true;
        return $(settings.aoData[index].nTr).data('done') != 1;
    });
    var review = $('#tbl-review').DataTable($.extend(opts('#tbl-review'), { columnDefs: [{ orderable: false, targets: [2, 4] }] }));
    $('#showDone').on('change', function () { review.draw(); });
});
</script>
@endsection
