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
                    <div class="clearfix">
                        <div class="pull-left">
                            <h6 class="card-title">Edit Record</h6>
                        </div>
                        <div class="pull-right">
                            <a href="{{ route('edit-record', ['id' => $record->id]) }}" class="btn btn-secondary">Refresh</a>
                            <button class="btn btn-info" type="button" onclick="showImportModal('edit')">Parse a Record</button>
                            <button class="btn btn-primary" type="button" onclick="showAddTagModal()">+ Add Tag</button>
                        </div>      
                    </div>
                    <!-- <a href="{{ url('/admin/get-marc-template-details?template_id=15') }}">Test</a> -->
                    <form method="post" action="{{ route('update-record', ['id' => $record->id]) }}" id="updateRecordFrm" enctype="multipart/form-data">
                        @csrf
                        <div class="container" id="main-el">
                            @error('marcFile')
                            <div class="mb-3">
                                <span class="text-danger">{{ $message }}</span>
                            </div>
                            @enderror
                            <div class="mb-3">
                                <label for="marcTemplateSelect" class="form-label"><strong>MARC Template</strong></label>
                                <select class="form-select" name="marc_template" id="marcTemplateSelect">
                                    <option selected disabled>Select a Template</option>
                                    @foreach($marcTemplates as $index => $template)
                                        <option value="{{$template->id}}">{{ $template->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="materialTypeSelect" class="form-label"><strong>Material Type</strong></label>
                                <select class="form-select" name="material_type" id="materialTypeSelect">
                                    <option selected disabled>Select Material Type</option>
                                    @foreach($materials as $material)
                                        <option value="{{$material->id}}" class="{{ $material->code }}" {{ $material->id == $record->material_type_id ? "selected" : ""}}>{{ $material->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="accessionNumber" class="form-label"><strong>Accession Number</strong></label>
                                <input type="text" class="form-control" id="accessionNumber" name="accession_number" value="{{$record->accession_number}}" autocomplete="off" placeholder="Enter accession number..." required>
                            </div>
                            <div class="mb-5">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col col-lg-3">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" id="isAANR" name="is_aanr" {{ $record->is_aanr == 1 ? 'checked' : '' }}>
                                                <label class="form-check-label" for="isAANR">
                                                    <strong>AANR?</strong> (Check if yes)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3" id="template-div">

                            </div>
                            <input type="hidden" id="importFlag" value="{{ isset($fieldData) ? 1 : 0; }}">
                            <input type="hidden" id="fieldData" class="form-control" name="field_data" value="{{ isset($fieldData) ? $fieldData : '[]'; }}">
                        </div>
                        <button class="btn btn-info" type="submit">Update</button>
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
            { data: 'code'},
            { data: 'description',
                render: function(data, type, row) {
                    return '<a href="#" class="add-tag-link" data-row-id="' + row.id + '" data-first-array=\'' + row.first_indictors + '\' data-second-array=\'' + row.second_indicators + '\'>' + data + '</a>';

                }
            },
            { data: 'id',
                render: function(data, type, row) {
                    return '<a href="#" class="add-tag-link btn btn-info" data-row-id="' + row.id + '" data-first-array=\'' + row.first_indictors + '\' data-second-array=\'' + row.second_indicators + '\'>ADD</a>';
                } 
            },
        ]
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