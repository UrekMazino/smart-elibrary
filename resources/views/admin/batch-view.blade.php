@extends("admin.layouts.master")

@section('css')
    <style>
        .even-row {
            background-color: #f2f2f2; /* Light gray */
        }

        .odd-row {
            background-color: #ffffff; /* White */
        }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ url('/admin/batch-list') }}">Batch list</a></li>
            <li class="breadcrumb-item active" aria-current="page">View Batch Details</li>
			<li class="breadcrumb-item active" aria-current="page">Batch ID: {{ $batch->batch_id }}</li>
        </ol>
    </nav>
    <div class="row">
		<div class="col-lg-12 grid-margin stretch-card">
			<div class="card">
                <div class="card-header">
                    <span class="card-title">No. of records:</span><h3> {{ $batch->no_of_records }} </h3>
                </div>
				<div class="card-body">
                    <div class="clearfix">
                        <div class="pull-left">
                            <!-- <h6 class="card-title">Batch Import MARC Records</h6> -->
                        </div>
                        <div class="pull-right">
                            <!-- Right Value here -->
                        </div>      
                    </div>
                        <div class="container" id="main-el">
                            <div class="mb-3">
                                <label for="materialTypeSelect" class="form-label"><strong>Material Type</strong></label>
                                <select class="form-select" name="material_type_id" id="materialTypeSelect">
                                    <option selected disabled value="{{$batch->material_type_id }}">{{ $batch->name }}</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label for="batchComment" class="form-label">Comment</label>
                                <textarea class="form-control" id="batchComment" rows="5" name="comment" disabled>{{ $batch->comment }}</textarea>
                            </div>
                            <div class="mb-5">
                                <div class="container-fluid">
                                    <div class="row">
                                        <div class="col col-lg-3">
                                            <div class="form-check mb-2">
                                                <input type="checkbox" class="form-check-input" id="isAANR" name="is_aanr" {{$batch->is_aanr == 1 ? 'checked' :''}} disabled>
                                                <label class="form-check-label" for="isAANR">
                                                    <strong>AANR?</strong> (Check if yes)
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <a href="{{ url()->previous() }}" class="btn btn-info" type="submit">Back</a>
                        <a href="{{ url('/admin/batch-records/'. $batch->batch_id) }}" class="btn btn-primary">View Records</a>
       
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js')
<script>
// script here
</script>
@endsection