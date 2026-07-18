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

        /* ---- Redesign (matches create-record) ---- */
        .cr-section-title {
            font-size: 11px; text-transform: uppercase; letter-spacing: .5px; color: #7987a1;
            font-weight: 700; margin: 0 0 8px; padding-bottom: 5px; border-bottom: 1px solid #eef0f2;
        }
        .cr-section-title.mt-4 { margin-top: 14px !important; }
        .cr-label { font-size: 12.5px; font-weight: 600; color: #33415c; margin-bottom: 4px; }
        .cr-req { color: #c92a4b; }
        .cr-hint { color: #a3adc2; font-size: 11.5px; margin-top: 4px; display: block; }

        /* Each MARC field the editor renders sits in its own compact card. */
        #template-div > .row {
            border: 1px solid #e4eff1; border-radius: 6px; background: #e4eff1;
            padding: 6px 10px; margin: 0 0 6px !important;
        }
        #template-div .subfield-parent > label.strong { font-size: 12px; color: #22283b; font-weight: 700; margin-bottom: 3px; }
        #template-div .block.mb-2 { margin-bottom: 3px !important; }
        #template-div .subfield-row { margin-bottom: 3px; }
        #template-div .form-control,
        #template-div .form-select { font-size: 12px; padding: 2px 8px; height: 28px; line-height: 1.4; }
        #template-div .width25 { width: 260px !important; max-width: 46%; }
        #template-div label.strong[for="fieldName"] { font-size: 11px; }
        #template-div .additional-subfield { margin-bottom: 0 !important; }
        #template-div .sm-font { width: 15px !important; height: 15px !important; }

        .cr-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .cr-footer {
            position: sticky; bottom: 0; background: #fff; border-top: 1px solid #eef0f2;
            padding: 14px 0 2px; margin-top: 6px;
        }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/marc-templates') }}">Cataloging</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
    <div class="row">
		<div class="col-lg-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="card-title mb-0">Edit Record</h6>
                        <div class="cr-actions">
                            <button class="btn btn-outline-primary btn-sm" type="button" onclick="showAddTagModal()">
                                <i data-feather="plus" class="icon-sm me-1"></i>Add Tag
                            </button>
                            <button class="btn btn-outline-info btn-sm" type="button" onclick="showImportModal('edit')">
                                <i data-feather="file-text" class="icon-sm me-1"></i>Parse a Record
                            </button>
                            <a href="{{ route('edit-record', ['id' => $record->id]) }}" class="btn btn-outline-secondary btn-sm">
                                <i data-feather="rotate-ccw" class="icon-sm me-1"></i>Refresh
                            </a>
                        </div>
                    </div>
                    <p class="text-muted mb-4" style="font-size: 12.5px;">
                        Edit this record&rsquo;s MARC fields. Use <strong>Add Tag</strong> to add another
                        field, or <strong>Parse a Record</strong> to replace the fields from a pasted MARC record.
                    </p>

                    <form method="post" action="{{ route('update-record', ['id' => $record->id]) }}" id="updateRecordFrm" enctype="multipart/form-data">
                        @csrf
                        <div id="main-el">
                            @error('marcFile')
                                <div class="alert alert-danger py-2" style="font-size: 13px;">{{ $message }}</div>
                            @enderror

                            <div class="cr-section-title">Record details</div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="marcTemplateSelect" class="cr-label">MARC Template</label>
                                    <select class="form-select" name="marc_template" id="marcTemplateSelect">
                                        <option selected disabled>Select a Template</option>
                                        @foreach($marcTemplates as $index => $template)
                                            <option value="{{$template->id}}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                    <span class="cr-hint">Loading a template replaces the fields below.</span>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="materialTypeSelect" class="cr-label">Material Type <span class="cr-req">*</span></label>
                                    <select class="form-select" name="material_type" id="materialTypeSelect">
                                        <option selected disabled>Select Material Type</option>
                                        @foreach($materials as $material)
                                            <option value="{{$material->id}}" class="{{ $material->code }}" {{ $material->id == $record->material_type_id ? "selected" : ""}}>{{ $material->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="row align-items-end">
                                <div class="col-md-6 mb-3">
                                    <label for="accessionNumber" class="cr-label">Accession Number <span class="cr-req">*</span></label>
                                    <input type="text" class="form-control" id="accessionNumber" name="accession_number" value="{{$record->accession_number}}" autocomplete="off" placeholder="Enter accession number…" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" id="isAANR" name="is_aanr" {{ $record->is_aanr == 1 ? 'checked' : '' }}>
                                        <label class="form-check-label" for="isAANR">
                                            <strong>AANR</strong> &mdash; part of the AANR collection
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="cr-section-title mt-4">MARC Fields</div>
                            <div id="template-div"></div>

                            <input type="hidden" id="importFlag" value="{{ isset($fieldData) ? 1 : 0; }}">
                            <input type="hidden" id="fieldData" class="form-control" name="field_data" value="{{ isset($fieldData) ? $fieldData : '[]'; }}">
                        </div>

                        <div class="cr-footer">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="icon-sm me-1"></i>Update Record
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
<script src="{{ asset('js/admin/edit-catalog-record.js') }}"></script>
<script> 
    let getMarcDetailsUrl = '{{ url("/admin/get-marc-tag-details") }}';
</script>
@if(session('success'))            
<script>
    showSwal('custom-position');
</script>
@endif
<script> 
  let getTemplateDetailsUrl = '{{ url("/admin/get-marc-template-details") }}';
</script>
<script>
    $(document).on('click', '.add-tag-link', function(event) {
        event.preventDefault();

        let rowId = $(this).data('row-id');
        let firstArray = $(this).data('first-array');
        let secondArray = $(this).data('second-array');
        
        // Call your addTag function with the retrieved data
        addTag(rowId, firstArray, secondArray);
    });
</script>
<script>
    
$(document).ready(function(){
    $('#marc-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: '{{ url("/admin/marc-tags-list/json") }}',
        columns: [
            { data: 'code',
                render: function (data) {
                    return '<span class="mtag-code">' + data + '</span>';
                }
            },
            { data: 'description',
                render: function (data, type, row) {
                    return '<a href="#" class="mtag-desc add-tag-link" data-row-id="' + row.id + '" data-first-array=\'' + row.first_indictors + '\' data-second-array=\'' + row.second_indicators + '\'>' + data + '</a>';
                }
            },
            { data: 'id', className: 'text-end',
                render: function (data, type, row) {
                    return '<button type="button" class="btn btn-sm btn-primary add-tag-link" data-row-id="' + row.id + '" data-first-array=\'' + row.first_indictors + '\' data-second-array=\'' + row.second_indicators + '\'>+ Add</button>';
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