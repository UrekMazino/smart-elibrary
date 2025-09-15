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
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/marc-templates') }}">MARC Templates</a></li>
            <li class="breadcrumb-item active" aria-current="page">Edit</li>
        </ol>
    </nav>
    <div class="row">
		<div class="col-lg-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
                    <div class="clearfix">
                        <div class="pull-left">
                            <h6 class="card-title">Edit Template</h6>
                        </div>
                        <div class="pull-right">
                            <button class="btn btn-primary" type="button" onclick="showAddTagModal()">+ Add Tag</button>
                        </div>      
                    </div>
                    <form method="post" action="{{ url('/admin/marc-templates/update') }}" id="editTemplateFrm" enctype="multipart/form-data">
                        @csrf
                        <div class="container" id="main-el">
                            <div class="row mb-5">
                                <label for="name" class="form-label">Name</label>
                                <input id="name" class="form-control width45" name="template_name" type="text" value="{{$template->name}}" required>
                                
                            </div>
                        </div>
                        <input type="hidden" name="template_id" value="{{$template->id}}">
                        <input id="fieldIds" class="form-control" name="field_ids" type="hidden" value="{{$template->fields}}">
                        
                        <button class="btn btn-primary" type="submit"  onclick="showSwal('custom-position')">Update</button>
                        <!-- <button class="btn btn-primary" onclick="showSwal('custom-position')">Click here!</button> -->
                    </form>
				</div>
			</div>
		</div>
	</div>
</div>
<!-- success -->
<!--View add field Modal -->
@include('admin.modals.add-tag-modal')
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script src="{{ asset('js/admin/marc-template.js') }}"></script>
<script src="{{ asset('js/admin/edit-marc-template.js') }}"></script>
@if(session('success'))            
<script>
    showSwal('custom-position');
</script>
@endif
<script> 
  let getMarcDetailsUrl = '{{ url("/admin/get-marc-tag-details") }}';
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


    $(function() {
        showSwal = function(type) {
            if (type === 'custom-position') {
                Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Your work has been saved',
                showConfirmButton: false,
                timer: 1500
                })
            } 
        }

    });


});
</script>
@endsection