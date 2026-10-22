@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
        .in-b {
            display: inline-block !important;
        }
        .in-f {
            display: flex !important;
        }

        .block {
            display: block !important;
        }

        .width45 {
            width: 45% !important;
        }
        .width25 {
            width: 35% !important;
        }

        .width8 {
            width: 8% !important;
        }
        .mr {
            margin-right: 10px !important;
        }

        .mr5 {
            margin-right: 5px !important;
        }

        .sm-font {
            width: 18px !important;
            height: 18px !important;
        
        }

        .danger-clr {
            color: red;
        }

        .dull-clr {
            color: gray;
        }

        .danger-clr:hover {
            color: darkred;
        }

        .success-clr {
            color: green;
        }

        .success-clr:hover {
            color: lightgreen;
        }

        .pull-right {
            float: right!important;
        }

        .pull-left {
            float: left!important;
        }

        .clearfix::after {
            content: "";
            clear: both;
            display: table;
        }

        .strong {
            font-weight: bold;
        }
 
        .btn-info {
            color: #fff !important; 
        }
        #loadingSpinnerContainer {
          position: fixed; 
          top: 50%; 
          left: 50%; 
          transform: translate(-50%, -50%); 
          z-index: 9999; 
          display: none;
        }

        #loadingSpinner {
            display: inline-block;
        }
        #loadingOverlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5); /* Semi-transparent black background */
            z-index: 9998; /* Place it below the loading spinner */
            display: none; /* Initially hide it */
        }
        #progressBarContainer {
          position: fixed; 
          top: 60%; 
          left: 50%; 
          z-index: 9999; 
          display: none;
        }

        /* ---- Material type mapping ---- */
        .bm-card { border: 1px solid #e4e8f0; border-radius: 8px; background: #fafbfd; padding: 12px 14px; }
        .bm-hint { color: #7987a1; font-size: 0.75rem; display: block; margin-top: 3px; }
        .bm-status { font-size: 0.78125rem; margin-top: 6px; color: #55606b; min-height: 18px; }
        .bm-status.err { color: #c92a4b; }
        .bm-preset { min-width: 260px; }
        .bm-table { font-size: 0.78125rem; background: #fff; }
        .bm-table th { font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: #7987a1; }
        .bm-table td { vertical-align: middle; }
        .bm-table .form-select { font-size: 0.78125rem; padding-top: 2px; padding-bottom: 2px; }
        .bm-val { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; background: #eef1f6; border-radius: 4px; padding: 1px 6px; }
        .bm-summary { font-size: 0.78125rem; color: #33415c; }
        .bm-summary .chip { display: inline-block; background: #e8eefc; color: #33509c; border-radius: 10px; padding: 1px 8px; margin: 2px 4px 2px 0; font-weight: 600; }
        .bm-summary .chip.def { background: #f1f3f7; color: #55606b; }
        .bm-save { max-width: 520px; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <div id="loadingOverlay"></div> 
    <div id="loadingSpinnerContainer">
        <div id="loadingSpinner" class="text-center">
            @php for($i = 0; $i < 3; $i++ ) { @endphp
            <div class="spinner-grow text-info" style="width: 2rem; height: 2rem;" role="status">
              <span class="visually-hidden">Loading...</span>
            </div>
            @php } @endphp

            <div class="progress">
                <div class="progress-bar" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">Importing please wait...</div>
            </div>
        </div>

    </div>
    <!-- <div id="progressBarContainer">
        <div class="progress">
            <div class="progress-bar" role="progressbar" aria-valuenow="25" aria-valuemin="0" aria-valuemax="100">Importing please wait...</div>
        </div>
    </div> -->
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/batch-list') }}">Batch Import</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>
    <div class="row">
		<div class="col-lg-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h6 class="card-title">Batch Import MARC Records</h6>
                        </div>
                        <div class="pull-right">
                            <!-- Right Value here -->
                        </div>      
                    </div>
                    <!-- <a href="{{ url('/admin/get-marc-template-details?template_id=15') }}">Test</a> -->
                    <form method="post" action="{{ url('/admin/create-batch/store') }}" id="createBatchFrm" enctype="multipart/form-data">
                        @csrf
                        <div class="container" id="main-el">
                            @if ($errors->any())
                            <div class="alert alert-danger py-2" style="font-size: 0.8125rem;">{{ $errors->first() }}</div>
                            @endif
							<div class="mb-3">
                                <label class="form-label" for="batchFile"><strong>MARC file</strong> <span class="text-muted">(.mrc, max 40 MB)</span></label>
                                <input class="form-control" type="file" id="batchFile" name="batch_file" accept=".mrc">
                                <div id="analyzeStatus" class="bm-status" aria-live="polite"></div>
							</div>

                            <div class="mb-3">
                                <label class="form-label" for="batchSubject"><strong>Subject</strong> <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="batchSubject" name="subject" maxlength="255" required
                                       value="{{ old('subject') }}" placeholder="What this batch is about, e.g. FNRI nutrition publications 1978–2023">
                                <span class="bm-hint">Shown in the Batch Import list and on the batch's page.</span>
                            </div>

                            {{-- Per-record material type mapping (shown once a file has been analyzed). --}}
                            <div id="mapCard" class="bm-card mb-3" style="display:none;">
                                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-1">
                                    <div>
                                        <h6 class="mb-0">Material type mapping <span class="text-muted fw-normal" style="font-size:0.75rem;">(optional)</span></h6>
                                        <p class="bm-hint mb-2">Give each record its own Material Type from a value in the file, e.g. Koha's item type (942$c).
                                            Values you leave on <em>Use default</em>, and records without the field, get the <strong>Default Material Type</strong> below.</p>
                                    </div>
                                    <div class="bm-preset">
                                        <label class="form-label mb-1" for="presetSelect">Preset</label>
                                        <div class="input-group input-group-sm">
                                            <select class="form-select" id="presetSelect">
                                                <option value="">— none —</option>
                                                @foreach($presets as $p)
                                                    <option value="{{ $p->id }}">{{ $p->name }}</option>
                                                @endforeach
                                            </select>
                                            <button type="button" class="btn btn-outline-danger" id="presetDelete" title="Delete this preset" disabled><i data-feather="trash-2" class="icon-sm"></i></button>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-2">
                                    <label class="form-label mb-1" for="mapField">Identify material type by</label>
                                    <select class="form-select form-select-sm" id="mapField"></select>
                                </div>

                                <div id="mapBody" style="display:none;">
                                    <div class="table-responsive">
                                        <table class="table table-sm bm-table mb-2">
                                            <thead><tr><th>Value in file</th><th class="text-end" style="width:90px;">Records</th><th style="width:44%;">Material Type</th></tr></thead>
                                            <tbody id="mapRows"></tbody>
                                        </table>
                                    </div>
                                    <div id="mapSummary" class="bm-summary mb-2"></div>
                                    <div class="input-group input-group-sm bm-save">
                                        <input type="text" class="form-control" id="presetName" maxlength="100" placeholder="Save this mapping as a preset, e.g. Koha – FNRI">
                                        <button type="button" class="btn btn-outline-primary" id="presetSave"><i data-feather="save" class="icon-sm me-1"></i>Save preset</button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="material_map_field" id="materialMapField">
                            <input type="hidden" name="material_map" id="materialMap">

                            <div class="mb-3">
                                <label for="materialTypeSelect" class="form-label"><strong>Default Material Type</strong> <span class="text-danger">*</span></label>
                                <select class="form-select" name="material_type_id" id="materialTypeSelect" required>
                                    <option value="" selected disabled>Select Material Type</option>
                                    @foreach($materials as $material)
                                        <option value="{{$material->id}}" class="{{ $material->code }}" @selected(old('material_type_id') == $material->id)>{{ $material->name }}</option>
                                    @endforeach
                                </select>
                                <span class="bm-hint">Used for every record when no mapping is set, and for unmapped values / records without the mapped field.</span>
                            </div>
                            <div class="mb-3">
                                <label for="batchComment" class="form-label">Comment</label>
                                <textarea class="form-control" id="batchComment" rows="4" name="comment">{{ old('comment') }}</textarea>
                            </div>
                            <div class="mb-4">
                                <div class="form-check mb-2">
                                    <input type="checkbox" class="form-check-input" id="isAANR" name="is_aanr" @checked(old('is_aanr'))>
                                    <label class="form-check-label" for="isAANR">
                                        <strong>AANR Publication/s?</strong> (Check if yes)
                                    </label>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-info" type="button" onclick="saveImport()" style="color:#fff;">Import</button>
                    </form>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- success -->
<!--View add field Modal -->
@include('admin.modals.add-field-modal')

@include('admin.modals.single-import-modal')
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>
// ---- Batch Import: material type mapping ----------------------------------------------
// Choosing a file analyzes it (nothing is imported): fields that can identify a record's
// material type, with their values. Map values -> Material Type; unmapped values and records
// without the field get the Default Material Type. The mapping is posted with the import.
var ANALYZE_URL = '{{ url("/admin/create-batch/analyze") }}';
var PRESET_URL  = '{{ url("/admin/material-maps") }}';
var CSRF        = '{{ csrf_token() }}';
var PRESETS     = @json($presets->keyBy('id'));
var analysis    = null;   // {total, fields:[{field, label, suggested, records, repeats, values:[{value,key,count}]}]}

function escapeHtml(s) { return $('<div>').text(s == null ? '' : String(s)).html().replace(/"/g, '&quot;'); }
function setStatus(html, isErr) { $('#analyzeStatus').toggleClass('err', !!isErr).html(html); }
function materialOptions() {
    return $('#materialTypeSelect option').filter(function () { return this.value !== ''; })
        .map(function () { return { id: this.value, name: $(this).text() }; }).get();
}
function fieldInfo() {
    var v = $('#mapField').val();
    return analysis && v ? analysis.fields.find(function (f) { return f.field === v; }) : null;
}
function currentMap() {
    var m = {};
    $('.bm-mat').each(function () { if ($(this).val()) m[$(this).data('key')] = parseInt($(this).val(), 10); });
    return m;
}
function ajaxError(xhr, fallback) {
    var e = xhr.responseJSON;
    return (e && (e.errors ? Object.values(e.errors)[0][0] : e.message)) || fallback;
}

$('#batchFile').on('change', function () {
    analysis = null;
    $('#mapCard').hide(); $('#mapRows').empty(); $('#mapSummary').empty();
    var file = this.files[0];
    if (!file) { setStatus(''); return; }
    setStatus('<span class="spinner-border spinner-border-sm me-1" role="status"></span>Reading the file…');
    var fd = new FormData(); fd.append('batch_file', file); fd.append('_token', CSRF);
    $.ajax({ url: ANALYZE_URL, type: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(function (res) {
            analysis = res;
            setStatus('<strong>' + res.total + '</strong> record(s) found in this file.');
            renderFieldOptions();
            $('#mapCard').show();
            if ($('#presetSelect').val()) $('#presetSelect').trigger('change');   // re-apply a chosen preset
            if (window.feather) feather.replace();
        })
        .fail(function (xhr) {
            if (xhr.status === 403 || xhr.status === 419) { setStatus(''); return; }   // the global handler explains
            setStatus(escapeHtml(ajaxError(xhr, 'This file could not be read.')), true);
        });
});

function renderFieldOptions() {
    var $s = $('#mapField').empty().append($('<option>').val('').text("— Don't map: use the default type for every record —"));
    var groups = [['Suggested', analysis.fields.filter(function (f) { return f.suggested; })],
                  ['Other fields in this file', analysis.fields.filter(function (f) { return !f.suggested; })]];
    groups.forEach(function (g) {
        if (!g[1].length) return;
        var $g = $('<optgroup>').attr('label', g[0]);
        g[1].forEach(function (f) {
            $g.append($('<option>').val(f.field).text(f.field + (f.label ? ' — ' + f.label : '') +
                '   (' + f.values.length + ' value' + (f.values.length === 1 ? '' : 's') + ' in ' + f.records + ' of ' + analysis.total + ' records)'));
        });
        $s.append($g);
    });
    // Start on the first suggested field that actually varies (e.g. 942$c), if any.
    var pick = groups[0][1].find(function (f) { return f.values.length > 1; });
    $s.val(pick ? pick.field : '');
    renderRows();
}

$('#mapField').on('change', function () { renderRows(); });

function renderRows(prefill) {
    var f = fieldInfo(), $b = $('#mapRows').empty();
    if (!f) { $('#mapBody').hide(); updateSummary(); return; }
    var mats = materialOptions();
    f.values.forEach(function (v) {
        var $sel = $('<select class="form-select form-select-sm bm-mat">').attr('data-key', v.key)
            .append($('<option>').val('').text('Use default'));
        mats.forEach(function (m) { $sel.append($('<option>').val(m.id).text(m.name)); });
        if (prefill && prefill[v.key]) $sel.val(String(prefill[v.key]));
        $b.append($('<tr>').append(
            $('<td>').append($('<span class="bm-val">').text(v.value)),
            $('<td class="text-end">').text(v.count),
            $('<td>').append($sel)));
    });
    $('#mapBody').show();
    updateSummary();
}

$(document).on('change', '.bm-mat, #materialTypeSelect', updateSummary);

function updateSummary() {
    var f = fieldInfo(), $s = $('#mapSummary');
    if (!f || !analysis) { $s.empty(); return; }
    var $def = $('#materialTypeSelect option:selected');
    var defName = $def.val() ? $def.text() : null;
    var counts = {}, defCount = analysis.total - f.records;   // records without the field -> default
    $('.bm-mat').each(function () {
        var key = $(this).data('key'), v = f.values.find(function (x) { return x.key === key; }), n = v ? v.count : 0;
        if ($(this).val()) { var nm = $(this).find('option:selected').text(); counts[nm] = (counts[nm] || 0) + n; }
        else defCount += n;
    });
    var html = (f.repeats ? '≈ ' : '') + 'Will import: ';
    Object.keys(counts).sort(function (a, b) { return counts[b] - counts[a]; }).forEach(function (nm) {
        html += '<span class="chip">' + counts[nm] + ' → ' + escapeHtml(nm) + '</span>';
    });
    if (defCount > 0) html += '<span class="chip def">' + defCount + ' → ' + (defName ? escapeHtml(defName) + ' (default)' : 'default type, not chosen yet') + '</span>';
    if (f.repeats) html += '<span class="bm-hint">Some records have several values for this field; the first mapped value decides, so counts are approximate.</span>';
    $s.html(html);
}

// ---- Presets ----
$('#presetSelect').on('change', function () {
    var p = PRESETS[this.value];
    $('#presetDelete').prop('disabled', !p);
    if (!p) return;
    $('#presetName').val(p.name);
    if (p.default_material_type_id) $('#materialTypeSelect').val(String(p.default_material_type_id));
    if (!analysis) return;
    if (!analysis.fields.find(function (f) { return f.field === p.source_field; })) {
        Swal.fire({ icon: 'info', title: 'Field not in this file',
            text: 'This preset maps ' + p.source_field + ', but the file has no ' + p.source_field + '. Pick another field, or every record gets the default type.' });
        $('#mapField').val(''); renderRows();
        return;
    }
    $('#mapField').val(p.source_field);
    renderRows(p.mappings);
});

$('#presetSave').on('click', function () {
    var name = $('#presetName').val().trim(), f = fieldInfo(), map = currentMap();
    if (!name) return Swal.fire({ icon: 'warning', title: 'Name the preset', text: 'For example "Koha – FNRI".' });
    if (!f || !Object.keys(map).length) return Swal.fire({ icon: 'warning', title: 'Nothing to save', text: 'Map at least one value to a Material Type first.' });
    var existing = Object.values(PRESETS).find(function (p) { return p.name.toLowerCase() === name.toLowerCase(); });
    var save = function () {
        $.ajax({ url: PRESET_URL, type: 'POST', dataType: 'json',
                 data: { _token: CSRF, name: name, source_field: f.field, mappings: JSON.stringify(map),
                         default_material_type_id: $('#materialTypeSelect').val() || '' } })
            .done(function (res) {
                var p = res.preset; PRESETS[p.id] = p;
                if (!$('#presetSelect option[value="' + p.id + '"]').length) $('#presetSelect').append($('<option>').val(p.id).text(p.name));
                $('#presetSelect').val(p.id); $('#presetDelete').prop('disabled', false);
                Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Preset “' + escapeHtml(p.name) + '” saved', showConfirmButton: false, timer: 2200 });
            })
            .fail(function (xhr) {
                if (xhr.status === 403 || xhr.status === 419) return;
                Swal.fire({ icon: 'error', title: 'Not saved', text: ajaxError(xhr, 'Please try again.') });
            });
    };
    if (existing) {
        Swal.fire({ icon: 'question', title: 'Replace preset?', text: 'A preset named “' + existing.name + '” exists. Overwrite it with this mapping?',
                    showCancelButton: true, confirmButtonText: 'Replace', reverseButtons: true })
            .then(function (r) { if (r.isConfirmed) save(); });
    } else save();
});

$('#presetDelete').on('click', function () {
    var id = $('#presetSelect').val(), p = PRESETS[id];
    if (!p) return;
    Swal.fire({ icon: 'warning', title: 'Delete preset “' + escapeHtml(p.name) + '”?', text: 'Batches already imported with it are not affected.',
                showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc3545', reverseButtons: true })
        .then(function (r) {
            if (!r.isConfirmed) return;
            $.ajax({ url: PRESET_URL + '/' + id, type: 'DELETE', data: { _token: CSRF }, dataType: 'json' })
                .done(function () {
                    delete PRESETS[id];
                    $('#presetSelect option[value="' + id + '"]').remove();
                    $('#presetSelect').val(''); $('#presetDelete').prop('disabled', true);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Preset deleted', showConfirmButton: false, timer: 2000 });
                });
        });
});

// ---- Import ----
function saveImport() {
    if (!$('#batchFile').val()) return Swal.fire({ icon: 'warning', title: 'Choose a MARC file first' });
    if (!$('#batchSubject').val().trim()) {
        return Swal.fire({ icon: 'warning', title: 'Enter the subject', text: 'Describe what this batch is about — it identifies the batch in the list.' })
            .then(function () { $('#batchSubject').trigger('focus'); });
    }
    if (!$('#materialTypeSelect').val()) {
        return Swal.fire({ icon: 'warning', title: 'Select the Default Material Type', text: 'It is used for records the mapping does not cover.' });
    }
    var f = fieldInfo(), map = currentMap(), mapped = !!(f && Object.keys(map).length);
    $('#materialMapField').val(mapped ? f.field : '');
    $('#materialMap').val(mapped ? JSON.stringify(map) : '');

    var plan = mapped ? $('#mapSummary').html()
                      : 'Every record → <strong>' + escapeHtml($('#materialTypeSelect option:selected').text()) + '</strong>';
    Swal.fire({
        title: 'Import ' + (analysis ? analysis.total + ' record(s)' : 'this file') + '?',
        html: '<div class="bm-summary text-start">' + plan + '</div>' +
              '<div class="mt-2 small text-muted">Large files can take a few minutes.</div>',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Import', cancelButtonText: 'Cancel', reverseButtons: true
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $('#loadingOverlay').show();
        $('#loadingSpinnerContainer').show();
        $('#createBatchFrm').submit();
    });
}
</script>
@endsection