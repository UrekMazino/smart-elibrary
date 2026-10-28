@extends("admin.layouts.master")

@section('css')
    <style>
        .even-row {
            background-color: var(--ui-surface-2, #f2f2f2); /* Light gray */
        }

        .odd-row {
            background-color: var(--ui-surface, #ffffff); /* White */
        }
    </style>
@endsection

@section('content')
<div class="page-content">
    @include('admin.partials.breadcrumb', ['page' => 'batch-import', 'below' => ['Batch #' . $batch->batch_id]])
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
                                <label for="batchSubject" class="form-label"><strong>Subject</strong></label>
                                <input type="text" class="form-control" id="batchSubject" value="{{ $batch->subject ?: '—' }}" disabled>
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
                        <a href="{{ url()->previous() }}" class="btn btn-primary" type="submit">Back</a>
                        <a href="{{ url('/admin/batch-records/'. $batch->batch_id) }}" class="btn btn-primary">View Records</a>
                        <a href="{{ route('batch-report', ['id' => $batch->batch_id]) }}" class="btn btn-outline-primary">Import report</a>
       
				</div>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
// script here
</script>
@endsection