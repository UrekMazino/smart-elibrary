@extends("admin.layouts.master")

@section('css')
    <style>
        .even-row {
            background-color: #f2f2f2;
        }

        .odd-row {
            background-color: #ffffff; 
        }

        .delete-icon {
            color: red;
        }

        .delete-icon:hover {
            color: pink;
        }
    </style>
    <link rel="stylesheet" href="{{ asset('assets/css/cropper.min.css') }}">
@endsection

@section('content')
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
        <nav class="page-breadcrumb mb-0">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item"><a href="{{ url('/admin/record-list') }}">Record list</a></li>
                <li class="breadcrumb-item active" aria-current="page">View details</li>
                <li class="breadcrumb-item active" aria-current="page">{{ $title }}</li>
            </ol>
        </nav>
        <button type="button" class="btn btn-sm btn-outline-secondary" onclick="goBack()">
            <i data-feather="arrow-left" class="icon-sm"></i> Back
        </button>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <span class="card-title">Details for:</span><h3> {!! $title !!} </h3>
                </div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-baseline mb-4 mb-md-3">
                        <h6 class="card-title mb-0">Record Information</h6>
                        <div>

                            @can("edit.catalog")<button type="button" class="btn btn-sm btn-outline-info" onclick="actionFrm({{$record->id}},'edit')">Edit</button>@endcan
                            @can("publish-hold.catalog")<button class="btn btn-sm {{ $record->is_published ? 'btn-outline-warning' : 'btn-outline-primary' }} " onclick="togglePublishOnhold({{$record->is_published}}, {{$record->id}})">{{ $record->is_published ? "Set on Hold" : "Publish now"  }}</button>@endcan
                        </div>
                        
                    </div>
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item">
                          <a class="nav-link {{ isset($uploadStatus) || isset($copyUpdate)  ? '' : 'active' }}" id="full-record-tab" data-bs-toggle="tab" href="#full-record" role="tab" aria-controls="full-record" aria-selected="true">Full Record</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="marc-record-tab" data-bs-toggle="tab" href="#marc-record" role="tab" aria-controls="marc-record" aria-selected="false">MARC Record</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="citation-tab" data-bs-toggle="tab" href="#citation" role="tab" aria-controls="citation" aria-selected="false">Citation</a>
                        </li>

                        <li class="nav-item">
                            <a class="nav-link" id="record-hist-tab" data-bs-toggle="tab" href="#record-hist" role="tab" aria-controls="record-hist" aria-selected="false">Record History</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ isset($uploadStatus) ? 'active' : '' }}" id="resources-tab" data-bs-toggle="tab" href="#resources" role="tab" aria-controls="resources" aria-selected="false">Resources</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link {{ isset($copyUpdate) ? 'active' : '' }}" id="physical-copy-tab" data-bs-toggle="tab" href="#physical-copy" role="tab" aria-controls="physical-copy" aria-selected="false">Physical Copy</a>
                        </li>
                    </ul>
                    <div class="tab-content border border-top-0 p-3" id="myTabContent">

                    <!-- Full Record Tab -->
                    <div class="tab-pane fade {{ isset($uploadStatus) || isset($copyUpdate) ? '' : 'show active' }}" id="full-record" role="tabpanel" aria-labelledby="full-record-tab"></div>

                    <!-- MARC Record Tab -->
                    <div class="tab-pane fade" id="marc-record" role="tabpanel" aria-labelledby="marc-record-tab"></div>

                    <!-- Citation Tab -->
                    <div class="tab-pane fade" id="citation" role="tabpanel" aria-labelledby="citation-tab">
                        @forelse(($citations ?? []) as $style => $text)
                            <div class="row mb-1 {{ $loop->even ? 'even-row' : 'odd-row' }}">
                                <div class="p-2 col-lg-2 col-md-2 col-sm-12 text-left"><strong>{{ $style }}</strong></div>
                                <div class="p-2 col-lg-9 col-md-9 col-sm-10 text-left" id="cite-{{ $style }}">{!! $text !!}</div>
                                <div class="p-2 col-lg-1 col-md-1 col-sm-2 text-end">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Copy" onclick="copyCitation('cite-{{ $style }}')"><i data-feather="copy" class="icon-sm"></i></button>
                                </div>
                            </div>
                        @empty
                            <div class="p-2 text-muted">No citation data available for this record.</div>
                        @endforelse
                    </div>

                    <!-- Record History Tab -->
                    <div class="tab-pane fade" id="record-hist" role="tabpanel" aria-labelledby="record-hist-tab"></div>

                    <!-- Record Resources Tab -->
                    <div class="tab-pane fade {{ isset($uploadStatus) ? 'show active' : '' }}" id="resources" role="tabpanel" aria-labelledby="resources-tab">
                        @isset($uploadError)
                            <div class="alert alert-danger">{{ $uploadError }}</div>
                        @endisset
                        <div class="row">
                            <div class="col-md-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-muted mb-3">Full text (PDF, max 100 MB): </h6>
                                                <div class="mb-3">
                                                        <form action="{{ route('upload-fulltext') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                                            <input type="hidden" value="{{ $title }}" name="title">
                                                            <input class="form-control w-100" type="file" id="fulltextUpload" name="fullText" accept="application/pdf,.pdf">                                                           
                                                            
                                                            <div class="mb-4 mb-md-0 mt-3">
                                                                <button class="btn btn-outline-info d-none" id="fulltext-btn">Upload Fulltext</button>
                                                            </div>
                                                        </form>
                                                    <div class="mb-4 mb-md-0 mt-3">
                                                        @if(!is_null($fullText))
                                                            <form action="{{ route('delete-fulltext') }}" method="POST" >
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                                                <input type="hidden" value="{{ $title }}" name="title">
                                                                <button type="submit" class="btn btn-outline-danger" id="delete-ft-btn">Remove Fulltext</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="text-center">
                                                    <img class="w-20 fulltext-view mt-2 mx-auto rounded" id="full-text-img-view" @if(is_null($fullText)) style="opacity: 0.2;" @endif src="{{ is_null($fullText) ? asset('assets/images/empty-pdf.jfif') : asset('assets/images/pdf-placeholder.jfif') }}" alt="">
                                                    @if(is_null($fullText))
                                                        <div class="text-center font-weight-light" id="full-text-no-upload">
                                                            <p style="font-weight:lighter">No Upload</p>
                                                        </div>
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <h6 class="text-muted mb-3">Cover Photo (JPG/PNG/WEBP, max 10 MB): </h6>
                                                <div class="mb-3">
                                                        <form action="{{ route('upload-coverphoto') }}" method="POST" enctype="multipart/form-data">
                                                            @csrf
                                                            <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                                            <input type="hidden" value="{{ $title }}" name="title">
                                                            <input class="form-control w-100" type="file" id="coverUpload" name="coverImage" accept="image/jpeg,image/png,image/webp">                                                           
                                                            
                                                            <div class="mb-4 mb-md-0 mt-3">
                                                                <button class="btn btn-outline-info d-none" id="cover-photo-btn">Save Cover Photo</button>
                                                                    
                                                            </div>
                                                        </form>
                                                    <div class="mb-4 mb-md-0 mt-3">
                                                        @if(!is_null($coverPage))
                                                            <form action="{{ route('delete-coverphoto') }}" method="POST" >
                                                                @csrf
                                                                @method('DELETE')
                                                                <input type="hidden" value="{{$record->id}}" name="catalog_id">
                                                                <input type="hidden" value="{{ $title }}" name="title">
                                                                <button type="submit" class="btn btn-outline-danger" id="delete-dp-btn">Remove Cover Photo</button>
                                                            </form>
                                                        @endif
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                        
                                                <div class="text-center">
                                                    <img class="w-50 img-view mt-2 mx-auto rounded" id="cover-photo-img-view" @if(is_null($coverPage)) style="opacity: 0.2;" @endif src="{{ is_null($coverPage) ? asset('assets/images/placeholder.jpg') : asset($coverPage->FrontPageLocation) }}" alt="">
                                                    @if(is_null($coverPage))
                                                        <div class="text-center font-weight-light mt-2" id="cover-photo-no-upload">
                                                            <p style="font-weight:lighter">No Upload</p>
                                                        </div>
                                                    @endif
                                                </div>
                                                
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Physical Copy Tab -->
                    <div class="tab-pane fade {{ isset($copyUpdate) ? 'show active' : '' }}" id="physical-copy" role="tabpanel" aria-labelledby="physical-copy-tab">
                        <div class="row">
                            <div class="col-md-12 stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-2 text-center mb-2">
                                                <h5 class="text-muted" id="physical-count">Physical Count: {{ isset($totalCopies) ? $totalCopies : 'N/A' }}</h5>
                                            </div>
                                            <div class="col-md-1"></div>
                                            <div class="col-md-1 text-center mb-2">
                                                <button type="button" onclick="searchCopy()" class="btn btn-sm btn-info" id="copy-search-btn">Search</button>
                                            </div>
                                            <div class="col-md-5 text-center mb-2">
                                                <input type="text" class="form-control" id="copy-search-input">
                                            </div>
                                            <div class="col-md-1"></div>
                                            <div class="col-md-2 float-right text-center mb-2">
                                                
                                                <a href="#" type="button" onclick="showAddCopyModal()" class="btn btn-sm btn-outline-primary">+ Copy/ies</a>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 grid-margin stretch-card">
                                <div class="card">
                                    <div class="card-body">
                                        
                                        <div class="table-responsive">
                                            <table class="table" id="copyTbl" style="width: 100%;">
                                                <thead>
                                                    <tr>
                                                        <th>#</th>
                                                        <th>CIRCULATION NO.</th>
                                                        <th>STATUS</th>
                                                        <th>SOURCE</th>
                                                        <th>LOCATION</th>
                                                        <th>ACTION</th>
                                                    </tr>
                                                </thead>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                    
                        </div>
                        <!-- add-copy-modal -->
                        @include('admin.modals.add-copy-modal')
                        <!-- edit-copy-modal -->
                        @include('admin.modals.edit-copy-modal')

                    </div>
                    
                    @php 
                        $materialData =  getMaterialData($record->material_type_id);
                        $materialName = $materialData->name;
                        $createdBy = getPatronInfo($record->created_by, 'name');
                        $updatedBy = getPatronInfo($record->updated_by, 'name');
                    @endphp 
                    <input type="hidden" id="catalog-id" value="{{ $record->id }}">
                    <input type="hidden" id="fields_data" value="{{ $record->field_data }}">
                    <input type="hidden" id="record" value="{{ $record }}">
                    <input type="hidden" id="material_name" value="{{ $materialName }}">
                    <input type="hidden" id="created_by" value="{{ $createdBy }}">
                    <input type="hidden" id="updated_by" value="{{ $updatedBy }}">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<form method="post" action="{{ url('/admin/publish-to-opac') }}" id="publishFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="publish_catalog_id">
</form>
<form method="post" action="{{ url('/admin/hold-view-from-opac') }}" id="holdFrm">
	@csrf
	<input type="hidden" name="catalog_id" id="hold_catalog_id">
</form>s

<form method="post" action="" id="delete_copy_frm">
	@csrf
    @method('DELETE')
    <input type="hidden" value="{{ $record->id }}" name="catalog_id">
    <input type="hidden" value="{{ $title }}" name="title" id="hidden-title">

</form>
@endsection
@section('js')
<!-- <script src="{{ asset('js/admin/citation-js/index.js') }}"></script> -->
<!-- Include @citation-js/core library -->
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/buttons/js/buttons.bootstrap5.min.js') }}"></script>

<script>
// Return to the list the admin came from (record-list or on-hold-list);
// fall back to the record list if there's no history to go back to.
function goBack() {
    if (document.referrer && window.history.length > 1) {
        window.history.back();
    } else {
        window.location.href = "{{ url('/admin/record-list') }}";
    }
}

function copyCitation(id) {
    var el = document.getElementById(id);
    if (!el || !navigator.clipboard) return;
    navigator.clipboard.writeText(el.innerText.trim()).then(function () {
        if (window.Swal) Swal.fire({ toast: true, position: 'top-end', icon: 'success', title: 'Citation copied', showConfirmButton: false, timer: 1200 });
    });
}
</script>
<script type="text/javascript" src="{{ asset('assets/js/cropper.js') }}"></script>
<script type="text/javascript" src="{{ asset('assets/js/cropper.min.js') }}"></script>
<script> let pdfDefaultThumb = "{{ asset('assets/images/pdf-placeholder.jfif') }}";</script>
<script> let copyTblUrl = "{{ url('/admin/copy-list-json') }}";</script>
<script src="{{ asset('js/admin/catalog-view-record.js') }}?v={{ filemtime(public_path('js/admin/catalog-view-record.js')) }}"></script>
<script>
    $(document).ready(function(){
        let searchValue = "";
        let catalogId = $('#catalog-id').val();
        $.ajax({
            type: 'GET',
            url: copyTblUrl,
            data: {
                "catalog_id": catalogId,
                "search_value": searchValue
            },
            dataType: 'json',
            success: function(res) {
                // Check if the response data is in the expected format
            
                if (res && res.aaData) {
                    let copyListArr = $.map(res.aaData, function(value, key) {
                        return [value];
                    });
                    
                    // Prepare the data in the expected format for DataTables
                    let data = copyListArr.map(record => ({
                        "id": record.id,
                        "circulation_number": record.circulation_number,
                        "status_name": record.status_name,
                        "source": record.source,
                        "location": record.location,
                    }));

                    // DataTable initialization
                    $('#copyTbl').DataTable({
                        searching: false,
                        data: data,
                        columns: [
                            { data: 'id', orderable: false },
                            { data: 'circulation_number', orderable: false },
                            { data: 'status_name', orderable: false },
                            { data: 'source', orderable: false },
                            { data: 'location', orderable: false },
                            { 
                                "data": function (row, type, val, meta) {
                                    return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="showEditCopyModal('+row.id+')">Edit</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'delete\')">Delete</a></div></div>';
                                }, orderable: false
                            },
                        ]
                    });
                } else {
                    console.log("Data format error or empty data.");
                }
            },
            error: function(xhr, status, error) {
                // More specific error handling to identify the issue
                console.error("Error:", status, error);
            }
        });

    });

    function searchCopy() {
        let searchValue = $("#copy-search-input").val();
        let catalogId = $('#catalog-id').val();
        $.ajax({
            type: 'GET',
            url: copyTblUrl,
            data: {
                "catalog_id": catalogId,
                "search_value": searchValue
            },
            dataType: 'json',
            success: function(res) {
                // Check if the response data is in the expected format
            
                if (res && res.aaData) {
                    let copyListArr = $.map(res.aaData, function(value, key) {
                        return [value];
                    });
                    
                    // Prepare the data in the expected format for DataTables
                    let data = copyListArr.map(record => ({
                        "id": record.id,
                        "circulation_number": record.circulation_number,
                        "status_name": record.status_name,
                        "source": record.source,
                        "location": record.location,
                    }));

                    let table = $('#copyTbl').DataTable();
                    table.destroy();
                    // DataTable initialization

                    $('#copyTbl').DataTable({
                        searching: false,
                        data: data,
                        columns: [
                            { data: 'id', orderable: false },
                            { data: 'circulation_number', orderable: false },
                            { data: 'status_name', orderable: false },
                            { data: 'source', orderable: false },
                            { data: 'location', orderable: false },
                            { 
                                "data": function (row, type, val, meta) {
                                    return '<div class="btn-group" role="group"><button id="btnGroupDrop1" type="button" class="btn btn-info dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu" aria-labelledby="btnGroupDrop1"><a class="dropdown-item" href="#" onclick="showEditCopyModal('+row.id+')">Edit</a><a class="dropdown-item" href="#" onclick="actionFrm('+row.id+',\'delete\')">Delete</a></div></div>';
                                }, orderable: false
                            },
                        ]
                    });
                } else {
                    console.log("Data format error or empty data.");
                }
            },
            error: function(xhr, status, error) {
                // More specific error handling to identify the issue
                console.error("Error:", status, error);
            }
        });
    }

    function actionFrm(id,action)
    {
        Swal.fire({
            title: 'Are you sure?',
            text: "",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
            reverseButtons: true
            }).then((result) => {
            if (result.value) {
                $("#modalSaving").modal("toggle");

                if(action == 'edit') {
                    let act = "{{ route('edit-record', ['id' => ':id']) }}".replace(':id', id);
                    window.location.href = act;
                }

                if(action == 'delete') {
                    let act = "{{ route('delete-copy', ['id' => ':id']) }}".replace(':id', id);

                    $("#delete_copy_frm").prop('action',act);
                    $("#delete_copy_frm").submit();
                }


                

            } else if (
                // Read more about handling dismissals
                result.dismiss === Swal.DismissReason.cancel
            ) {
                Swal.fire(
                'Cancelled',
                'Your data is safe!',
                'error'
                )
            }
        })

    }

    function togglePublishOnhold(action, id)
    {
        let title = "";
        let message = "";
        let formId = ""
        if(action == 1) {
            title = "Hold this record?";
            message = "This record will be hidden from OPAC view.";
            formId = "#holdFrm";
            catalogId = "#hold_catalog_id"
        } else {
            title = "Publish this record? ";
            message = "This record will be available to OPAC view.";
            formId = "#publishFrm";
            catalogId ="#publish_catalog_id";
        }
        Swal.fire({
            title: title,
            text: message,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonClass: 'me-2',
            confirmButtonText: 'Yes',
            cancelButtonText: 'No',
        }).then((result) => {
            if (result.value) {
                $(catalogId).val(id);
                $.ajax({
                    url: $(formId).attr('action'),
                    method: $(formId).attr('method'),
                    data: $(formId).serialize(),
                    success: function (response) {
                        Swal.fire(
                            'Success',
                            'The record has been updated successfully!',
                            'success'
                        );
                        setTimeout(() => {
                            location.reload();
                        }, 1000); // 2000 milliseconds = 2 seconds
                    },
                    error: function () {
                        Swal.fire(
                            'Error',
                            'An error occurred while updating the record.',
                            'error'
                        );
                    }
                });
            } else if (
                result.dismiss === Swal.DismissReason.cancel
            ) {
                Swal.fire(
                    'Cancelled',
                    'Data was not deleted!',
                    'error'
                );
            }
        });
    }
</script>
@endsection