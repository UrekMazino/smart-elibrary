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

        .additional-subfield {
            display: block;
        }

        .btn-info {
            color: #fff !important;
        }

        /* ---- Redesign ---- */
        .cr-section-title {
            font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .5px; color: var(--ui-muted, #7987a1);
            font-weight: 700; margin: 0 0 14px; padding-bottom: 7px; border-bottom: 1px solid var(--ui-border, #eef0f2);
        }
        .cr-label { font-size: 0.78125rem; font-weight: 600; color: var(--ui-text, #33415c); margin-bottom: 4px; }
        .cr-req { color: var(--ui-danger-ink, #c92a4b); }
        .cr-hint { color: var(--ui-muted-2, #a3adc2); font-size: 0.71875rem; margin-top: 4px; display: block; }

        /* Empty state: shown only when no fields have been added yet (CSS :empty). */
        .cr-empty {
            display: none; text-align: center; padding: 34px 20px; border: 1px dashed var(--ui-border, #d7dded);
            border-radius: 10px; background: var(--ui-surface, #fafbfd); color: var(--ui-muted, #8a94a6);
        }
        .cr-empty .icn { font-size: 1.625rem; display: block; margin-bottom: 8px; }
        .cr-empty h6 { color: var(--ui-text-2, #55606b); font-size: 0.875rem; font-weight: 700; margin-bottom: 4px; }
        .cr-empty p { font-size: 0.78125rem; margin: 0; }

        /* Fixed Fields (LDR / 008) guided panel — compact grid */
        .ff-group { border: 1px solid var(--ui-border, #eef0f2); border-radius: 6px; background: var(--ui-surface, #fbfcfd); padding: 8px 10px; margin-bottom: 6px; }
        .ff-group-title { font-size: 0.75rem; font-weight: 700; color: var(--ui-text, #22283b); margin-bottom: 7px; }
        .ff-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 6px 10px; }
        .ff-cell { display: flex; flex-direction: column; }
        .ff-label { font-size: 0.65625rem; font-weight: 600; color: var(--ui-text-2, #55606b); margin-bottom: 2px; }
        .ff-group .form-control, .ff-group .form-select { font-size: 0.75rem; padding: 2px 8px; height: 28px; }
        .ff-auto {
            font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-size: 0.75rem; color: var(--ui-text, #33415c);
            background: var(--ui-surface-2, #eef1f6); border-radius: 5px; padding: 4px 8px; letter-spacing: .5px;
        }
        .ff-hint { font-size: 0.625rem; color: var(--ui-muted-2, #a3adc2); margin-top: 2px; }

        /* Tighter section rhythm so more tags are visible at once. */
        .cr-section-title { margin: 0 0 8px; padding-bottom: 5px; }
        .cr-section-title.mt-4 { margin-top: 14px !important; }

        .cr-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .cr-footer {
            position: sticky; bottom: 0; background: var(--ui-surface, #fff); border-top: 1px solid var(--ui-border, #eef0f2);
            padding: 14px 0 2px; margin-top: 6px;
        }
    </style>
    @include('admin.partials.marc-editor-styles')
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/marc-templates') }}">Cataloging</a></li>
            <li class="breadcrumb-item active" aria-current="page">Create</li>
        </ol>
    </nav>
    <div class="row">
		<div class="col-lg-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="card-title mb-0">Create Record</h6>
                        <div class="cr-actions">
                            <button class="btn btn-outline-primary btn-sm" type="button" onclick="showAddFieldModal()">
                                <i data-feather="plus" class="icon-sm me-1"></i>Add Tag
                            </button>
                            <button class="btn btn-outline-info btn-sm" type="button" onclick="showImportModal('create')">
                                <i data-feather="file-text" class="icon-sm me-1"></i>Parse a Record
                            </button>
                            <a href="{{ url('/admin/create-record') }}" class="btn btn-outline-secondary btn-sm">
                                <i data-feather="rotate-ccw" class="icon-sm me-1"></i>Reset Form
                            </a>
                        </div>
                    </div>
                    <p class="text-muted mb-4" style="font-size: 0.78125rem;">
                        Pick a MARC template to load its standard fields, then fill in the values.
                        Use <strong>Add Tag</strong> for any extra MARC field, or <strong>Parse a
                        Record</strong> to import from a pasted MARC record.
                    </p>

                    <form method="post" action="{{ url('/admin/create-record/store') }}" id="createRecordFrm" enctype="multipart/form-data">
                        @csrf
                        <div id="main-el">
                            @error('marcFile')
                                <div class="alert alert-danger py-2" style="font-size: 0.8125rem;">{{ $message }}</div>
                            @enderror

                            <div class="cr-section-title">Record details</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="marcTemplateSelect" class="cr-label">MARC Template <span class="cr-req">*</span></label>
                                    <select class="form-select" name="marc_template" id="marcTemplateSelect">
                                        <option selected disabled>Select a Template</option>
                                        @foreach($marcTemplates as $index => $template)
                                            <option value="{{$template->id}}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="cr-hint">Loads the standard MARC fields for this material.</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="materialTypeSelect" class="cr-label">Material Type <span class="cr-req">*</span></label>
                                    <select class="form-select" name="material_type" id="materialTypeSelect" required>
                                        <option value="" selected disabled>Select Material Type</option>
                                        @foreach($materials as $material)
                                            <option value="{{$material->id}}" class="{{ $material->code }}">{{ $material->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-md-6 mb-3">
                                    <label for="accessionNumber" class="cr-label">Accession Number <span class="cr-req">*</span></label>
                                    <input type="text" class="form-control" id="accessionNumber" name="accession_number" autocomplete="off" placeholder="Enter accession number…" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isAANR" name="is_aanr">
                                        <label class="form-check-label" for="isAANR">
                                            <strong>AANR</strong> &mdash; part of the AANR collection
                                        </label>
                                    </div>
                                </div>
                            </div>

                            {{-- Guided LDR/008 fixed-field editor. Hidden until a leader/008 exists. --}}
                            <div id="fixed-fields-card" style="display:none;">
                                <div class="cr-section-title mt-4">Fixed Fields (LDR / 008)</div>
                                <p class="text-muted mb-2" style="font-size: 0.75rem;">
                                    Structural positions are set automatically (marked <em>auto</em>). Verify the
                                    coded positions below &mdash; type of record and bibliographic level follow the
                                    material type; encoding level, dates, country and language are yours to confirm.
                                </p>
                                <div id="fixed-fields-body"></div>
                            </div>

                            <div class="cr-section-title mt-4">MARC Fields</div>
                            <div id="template-div"></div>
                            <div class="cr-empty">
                                <span class="icn">&#128218;</span>
                                <h6>No fields yet</h6>
                                <p>Select a MARC template above to load its fields, or use <strong>Add Tag</strong>.</p>
                            </div>

                            <input type="hidden" id="importFlag" value="{{ isset($fieldData) ? 1 : 0; }}">
                            <input id="fieldData" class="form-control" name="field_data" type="hidden" value="{{ isset($fieldData) ? $fieldData : '[]'; }}">
                        </div>

                        <div class="cr-footer">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="icon-sm me-1"></i>Create Record
                            </button>
                        </div>
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
{{-- Config before the scripts that consume it. --}}
<script>
  let getTemplateDetailsUrl = '{{ url("/admin/get-marc-template-details") }}';
  let getMarcDetailsUrl     = '{{ url("/admin/get-marc-tag-details") }}';
  let marcEditorMode        = 'create';
  // Core bibliographic tags pre-loaded on a fresh form (source shape). Empty on a
  // parse/repopulate ($fieldData set), where the parsed record takes over instead.
  let marcInitialFields     = @json(isset($fieldData) ? [] : ($defaultFields ?? []));
</script>
<script src="{{ asset('js/admin/marc-record-editor.js') }}"></script>
<script src="{{ asset('js/admin/import/single-import.js') }}"></script>
@if(session('success'))
<script>
    showSwal('custom-position');
</script>
@endif
<script>
    // "ADD" in the Add Tag modal -> append that MARC field to the record.
    $(document).on('click', '.add-field-link', function(event) {
        event.preventDefault();
        addTag($(this).data('row-id'));
    });
</script>
<script>

$(document).ready(function(){
    $('#marc-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ url("/admin/marc-tags-list/json") }}',
            // Hide LDR/008 — they're generated + edited in the Fixed Fields panel, not added by hand.
            data: function (d) { d.exclude_control = 1; }
        },
        columns: [
            { data: 'code',
                render: function (data) {
                    return '<span class="mtag-code">' + data + '</span>';
                }
            },
            { data: 'description',
                render: function (data, type, row) {
                    return '<a href="#" class="mtag-desc add-field-link" data-row-id="' + row.id + '">' + data + '</a>';
                }
            },
            { data: 'id', className: 'text-end',
                render: function (data, type, row) {
                    return '<button type="button" class="btn btn-sm btn-primary add-field-link" data-row-id="' + row.id + '">+ Add</button>';
                }
            },
        ],
        language: { search: '', searchPlaceholder: 'Search tag number or name…', lengthMenu: 'Show _MENU_' }
    });

    // $(function() {
    //     showSwal = function(type) {
    //         if (type === 'custom-position') {
    //             Swal.fire({
    //             position: 'top-end',
    //             icon: 'success',
    //             title: 'Your work has been saved',
    //             showConfirmButton: false,
    //             timer: 1500
    //             })
    //         }
    //     }

    // });

});
</script>
@endsection
