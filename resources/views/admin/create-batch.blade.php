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
            <li class="breadcrumb-item"><a href="{{ url('/admin/marc-templates') }}">Batch</a></li>
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
                            @error('batch_file')
                            <div class="mb-3">
                                <span class="text-danger">{{ $message }}</span>
                            </div>
                            @enderror
							<div class="mb-3">
                                <label class="form-label" for="batchFile">File upload</label>
                                <input class="form-control" type="file" id="batchFile" name="batch_file">
							</div>
                            <div class="mb-3">
                                <label for="materialTypeSelect" class="form-label"><strong>Material Type</strong></label>
                                <select class="form-select" name="material_type_id" id="materialTypeSelect">
                                    <option selected disabled>Select Material Type</option>
                                    @foreach($materials as $material)
                                        <option value="{{$material->id}}" class="{{ $material->code }}">{{ $material->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="batchComment" class="form-label">Comment</label>
                                <textarea class="form-control" id="batchComment" rows="5" name="comment"></textarea>
                            </div>
                            <div class="mb-5">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col col-lg-3">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" id="isAANR" name="is_aanr">
                                                <label class="form-check-label" for="isAANR">
                                                    <strong>AANR Publication/s?</strong> (Check if yes)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button class="btn btn-info" type="button" onclick="saveImport()">Save</button>
                        <!-- <button class="btn btn-primary" onclick="showSwal('custom-position')">Click here!</button> -->
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
@if(session('success'))            
<script>
    showSwal('custom-position');
</script>
@endif
<script>



function saveImport()
{
    Swal.fire({
        title: 'Import data? <br><h6>This may take a few minutes.</h6>',
        text: "The duration of this process may vary, depending on the quantity of records within the MARC file.",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonClass: 'me-2',
        confirmButtonText: 'Yes',
        cancelButtonText: 'No',
        reverseButtons: true
    }).then((result) => {
        if (result.value) {
        // $("#modalSaving").modal("toggle");

        $('#loadingOverlay').show();
        $('#loadingSpinnerContainer').show();
        $("#createBatchFrm").submit();
        // $('#progressBarContainer').show();
        // $(document).ready(function() {
        //     let current = 0;
        //     let totalRecords = 0;
        //     let percentage = 0;

        //     updateProgressBar();
        //     function updateProgressBar() {
        //         $.ajax({
        //             url: '/get-import-progress', // Your Laravel route to get progress
        //             method: 'GET',
        //             success: function (data) {
        //                 current = data.progress;
        //                 totalRecords = data.total_records;
        //                 percentage = (current/totalRecords)*100;
        //                 console.log(current);
        //                 $('.progress-bar').css('width', percentage + '%');
        //                 $('.progress-bar').attr('aria-valuenow', percentage);
        //                 $('.progress-bar').html(current + ' / ' + totalRecords);
        //             },
        //             error: function(e) {
        //                 // Handle the error case
        //                 console.log(e);
        //             }
        //         });
        //     }


        //     setInterval(updateProgressBar, 1000); 
        // });

        } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
        ) {
        Swal.fire(
            'Cancelled',
            'Your data was not saved!',
            'error'
        )
        }
    })
}
</script>
@endsection