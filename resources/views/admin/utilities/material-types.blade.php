@extends("admin.layouts.master")

{{-- Administration → Utilities → Material Types. Actions call MaterialTypeController (JSON) and
     the page reloads to show the result; the server enforces every rule shown here. --}}

@section('css')
    <style>
      .ac-head { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 10px; margin-bottom: 18px; }
      .ac-head h4 { font-weight: 700; color: #22283b; margin: 0; }
      .ac-head p  { color: #8a94a6; margin: 2px 0 0; font-size: 0.8125rem; max-width: 760px; }
      .page-content .card { border: 1px solid #eef0f4; border-radius: 12px; box-shadow: 0 1px 2px rgba(20, 30, 60, .04); }
      .mt-tbl { font-size: 0.8125rem; width: 100%; margin: 0; }
      .mt-tbl thead th { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: #7987a1; white-space: nowrap; padding: 9px 10px; border-bottom: 2px solid #eef0f2; }
      .mt-tbl tbody td { padding: 9px 10px; vertical-align: middle; border-bottom: 1px solid #f1f3f7; }
      .mt-tbl tbody tr:hover { background: #f9fbfe; }
      .mt-name { font-weight: 600; color: #22283b; }
      .mt-desc { color: #6b7489; font-size: 0.78125rem; }
      .mt-count { font-variant-numeric: tabular-nums; }
      .mt-badge { font-size: 0.6875rem; font-weight: 600; border-radius: 10px; padding: 2px 8px; }
      .mt-badge.on  { background: #e3f6ef; color: #1d7a55; }
      .mt-badge.off { background: #f1f3f7; color: #7987a1; }
      .mt-code { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; background: #eef1f6; border-radius: 4px; padding: 1px 6px; font-size: 0.75rem; }
      .mt-section-title { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .5px; color: #7987a1; font-weight: 700; margin: 0 0 6px; }
      .mt-note { font-size: 0.78125rem; color: #6b7489; margin: 0 0 10px; }
      .mt-toggle-link { cursor: pointer; color: var(--t-primary); font-size: 0.8125rem; font-weight: 600; text-decoration: none; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('utilities') }}">Utilities</a></li>
            <li class="breadcrumb-item active" aria-current="page">Material Types</li>
        </ol>
    </nav>

    <div class="ac-head">
        <div>
            <h4>Material Types</h4>
            <p>The categories catalogers pick for each record (Create / Edit Record, Batch Import) and patrons filter by in the OPAC.
               <strong>Hidden</strong> types stay on their records but aren't offered in lists. A type that's in use can't be deleted —
               <strong>merge</strong> it into another type instead.</p>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="addBtn"><i data-feather="plus" class="icon-sm me-1"></i>Add material type</button>
    </div>

    <div class="card mb-3">
        <div class="card-body">
            <p class="mt-section-title">Library material types</p>
            <div class="table-responsive">
                <table class="mt-tbl">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Description <span class="text-muted text-lowercase fw-normal">(OPAC tooltip)</span></th>
                            <th class="text-end" style="width:90px;">Records</th>
                            <th style="width:110px;">Status</th>
                            <th class="text-center" style="width:100px;">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($library as $m)
                            @include('admin.utilities.partials.material-type-row', ['m' => $m])
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-4">No material types yet — add one.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                <p class="mt-section-title mb-0">MARC record types (LDR/06) <span class="text-muted text-lowercase fw-normal">— {{ count($marc) }}, protected</span></p>
                <a class="mt-toggle-link" data-bs-toggle="collapse" href="#marcTypes" role="button" aria-expanded="false" aria-controls="marcTypes">Show / hide list</a>
            </div>
            <div class="collapse" id="marcTypes">
                <p class="mt-note mt-2">These are MARC 21 "type of record" codes, not library categories (a thesis or a book is code <span class="mt-code">a</span>, "Language material").
                   Batch Import falls back on them when a record has no Material Type, so they can't be deleted or merged away.
                   They're hidden from lists by default. If records use one (e.g. a batch imported as "Mixed materials"), use
                   <strong>Move records to…</strong> to give them a real Material Type.</p>
                <div class="table-responsive">
                    <table class="mt-tbl">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Description</th>
                                <th class="text-end" style="width:90px;">Records</th>
                                <th style="width:110px;">Status</th>
                                <th class="text-center" style="width:100px;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($marc as $m)
                                @include('admin.utilities.partials.material-type-row', ['m' => $m])
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Add / edit --}}
<div class="modal fade" id="mtModal" tabindex="-1" aria-labelledby="mtModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form class="modal-content" id="mtForm" novalidate>
            <div class="modal-header">
                <h6 class="modal-title" id="mtModalTitle">Add material type</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="mtId">
                <div class="mb-3">
                    <label class="form-label" for="mtName">Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="mtName" maxlength="100" required placeholder="e.g. Conference Proceedings">
                    <div class="form-text" id="mtRenameHint" style="display:none;">Renaming updates every record that uses this type (OPAC, search and AI index).</div>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="mtTooltip">Description <span class="text-muted">(optional — shown as a tooltip in the OPAC)</span></label>
                    <textarea class="form-control" id="mtTooltip" rows="3" maxlength="500"></textarea>
                </div>
                <div class="form-check" id="mtActiveWrap">
                    <input class="form-check-input" type="checkbox" id="mtActive" checked>
                    <label class="form-check-label" for="mtActive">Show in lists (pickers and OPAC filters)</label>
                </div>
                <div class="alert alert-danger py-2 mt-3 mb-0" id="mtError" style="display:none; font-size:0.8125rem;"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary" id="mtSave">Save</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
<script>
var MT_URL = '{{ url("/admin/utilities/material-types") }}';
var CSRF   = '{{ csrf_token() }}';
// All types (for the merge target list).
var TYPES  = @json($library->concat($marc)->map(fn ($m) => ['id' => $m->id, 'name' => $m->name, 'marc' => $m->is_marc_type])->values());

function esc(s) { return $('<div>').text(s == null ? '' : String(s)).html().replace(/"/g, '&quot;'); }
function errText(xhr, fallback) {
    var e = xhr.responseJSON;
    return (e && (e.errors ? Object.values(e.errors)[0][0] : e.message)) || fallback;
}
// Show the result after the reload (per-viewer convenience; the page works without it).
function reloadWith(msg) {
    try { sessionStorage.setItem('mtFlash', msg); } catch (e) {}
    location.reload();
}
$(function () {
    if (window.feather) feather.replace();
    var msg = null;
    try { msg = sessionStorage.getItem('mtFlash'); sessionStorage.removeItem('mtFlash'); } catch (e) {}
    if (msg) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: msg, showConfirmButton: false, timer: 3200 });
});

// ---- Add / edit ----
function openModal(row) {
    $('#mtError').hide();
    $('#mtId').val(row ? row.id : '');
    $('#mtName').val(row ? row.name : '');
    $('#mtTooltip').val(row ? row.tooltip : '');
    $('#mtActive').prop('checked', true);
    $('#mtActiveWrap').toggle(!row);           // existing types use Show / Hide
    $('#mtRenameHint').toggle(!!row && row.records > 0);
    $('#mtModalTitle').text(row ? 'Edit material type' : 'Add material type');
    $('#mtModal').modal('show');
    setTimeout(function () { $('#mtName').trigger('focus'); }, 300);
}
$('#addBtn').on('click', function () { openModal(null); });
$(document).on('click', '[data-act="edit"]', function (e) { e.preventDefault(); openModal($(this).closest('tr').data('row')); });

$('#mtForm').on('submit', function (e) {
    e.preventDefault();
    var id = $('#mtId').val(), name = $('#mtName').val().trim();
    if (!name) { $('#mtError').text('Enter a name.').show(); return; }
    $('#mtSave').prop('disabled', true);
    $.ajax({
        url: id ? MT_URL + '/' + id : MT_URL, type: id ? 'PUT' : 'POST', dataType: 'json',
        data: { _token: CSRF, name: name, tooltip: $('#mtTooltip').val(), is_active: $('#mtActive').is(':checked') ? 1 : 0 }
    }).done(function (res) { reloadWith(res.message); })
      .fail(function (xhr) { if (xhr.status !== 403 && xhr.status !== 419) $('#mtError').text(errText(xhr, 'Not saved. Please try again.')).show(); })
      .always(function () { $('#mtSave').prop('disabled', false); });
});

// ---- Show / hide ----
$(document).on('click', '[data-act="toggle"]', function (e) {
    e.preventDefault();
    var row = $(this).closest('tr').data('row');
    $.post(MT_URL + '/' + row.id + '/toggle', { _token: CSRF }, null, 'json').done(function (res) { reloadWith(res.message); });
});

// ---- Merge ----
$(document).on('click', '[data-act="merge"]', function (e) {
    e.preventDefault();
    var row = $(this).closest('tr').data('row'), options = {};
    TYPES.filter(function (t) { return t.id !== row.id; })
         .sort(function (a, b) { return (a.marc - b.marc) || a.name.localeCompare(b.name); })
         .forEach(function (t) { options[t.id] = t.name + (t.marc ? ' (MARC record type)' : ''); });
    Swal.fire({
        title: (row.marc ? 'Move records from “' : 'Merge “') + esc(row.name) + (row.marc ? '” to…' : '” into…'),
        html: row.marc
            ? 'Its <strong>' + row.records + '</strong> record(s) get the type you pick. The MARC record type itself stays (the importer needs it).'
            : 'Its <strong>' + row.records + '</strong> record(s), batches and mapping presets move to the type you pick, and <strong>' + esc(row.name) + '</strong> is deleted. This can\'t be undone.',
        input: 'select', inputOptions: options, inputPlaceholder: 'Choose a material type',
        showCancelButton: true, confirmButtonText: row.marc ? 'Move records' : 'Merge', confirmButtonColor: '#dc3545', reverseButtons: true,
        inputValidator: function (v) { return v ? null : 'Choose the type to merge into.'; },
        showLoaderOnConfirm: true,
        preConfirm: function (targetId) {
            return $.post(MT_URL + '/' + row.id + '/merge', { _token: CSRF, target_id: targetId }, null, 'json')
                .catch(function (xhr) { Swal.showValidationMessage(errText(xhr, 'Not merged. Please try again.')); });
        },
        allowOutsideClick: function () { return !Swal.isLoading(); }
    }).then(function (r) { if (r.isConfirmed && r.value) reloadWith(r.value.message); });
});

// ---- Delete (only offered when unused; the server checks again) ----
$(document).on('click', '[data-act="delete"]', function (e) {
    e.preventDefault();
    var row = $(this).closest('tr').data('row');
    Swal.fire({
        icon: 'warning', title: 'Delete “' + esc(row.name) + '”?', text: 'No records use it. This can\'t be undone.',
        showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc3545', reverseButtons: true,
        showLoaderOnConfirm: true,
        preConfirm: function () {
            return $.ajax({ url: MT_URL + '/' + row.id, type: 'DELETE', data: { _token: CSRF }, dataType: 'json' })
                .catch(function (xhr) { Swal.showValidationMessage(errText(xhr, 'Not deleted. Please try again.')); });
        },
        allowOutsideClick: function () { return !Swal.isLoading(); }
    }).then(function (r) { if (r.isConfirmed && r.value) reloadWith(r.value.message); });
});
</script>
@endsection
