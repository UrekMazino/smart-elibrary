@extends("admin.layouts.master")

@section('css')
<link rel="stylesheet" href="{{ asset('admin/nobleui/template/assets/vendors/select2/select2.min.css') }}">
<style>
	.report-tiles { display:grid; grid-template-columns:repeat(auto-fit,minmax(190px,1fr)); gap:12px; }
	.report-tile { margin:0; cursor:pointer; }
	.report-tile input { position:absolute; opacity:0; width:0; height:0; }
	.report-tile .rt-inner { display:flex; flex-direction:column; gap:5px; height:100%; padding:14px 16px; border:1.5px solid #e6e9ef; border-radius:10px; background:#fff; transition:border-color .12s, box-shadow .12s, background .12s; }
	.report-tile:hover .rt-inner { border-color:#b9c2d0; }
	.report-tile input:checked + .rt-inner { border-color:#0d6efd; background:#f3f8ff; box-shadow:0 0 0 3px rgba(13,110,253,.12); }
	.report-tile .rt-inner svg, .report-tile .rt-inner i { width:22px; height:22px; color:#0d6efd; }
	.rt-name { font-weight:600; font-size:14px; color:#1f2733; }
	.rt-desc { font-size:12px; color:#7a8494; line-height:1.3; }
	.filter-div { display:none; }
	.report-preview-box { max-height:560px; overflow:auto; }
	#report-actions { display:none; }
	.report-hint { color:#8a94a6; }
</style>
@endsection

@section('content')
@php
	$reportTypes = [
		['v'=>1,'icon'=>'book',         'name'=>'Acquisition',          'desc'=>'Catalog records by type, source & keyword'],
		['v'=>2,'icon'=>'users',        'name'=>'Patron',               'desc'=>'Registered library patrons'],
		['v'=>3,'icon'=>'repeat',       'name'=>'Borrowed Materials',   'desc'=>'Physical copy loans (circulations)'],
		['v'=>4,'icon'=>'help-circle',  'name'=>'Inquiries',            'desc'=>'Patron inquiries submitted'],
		['v'=>5,'icon'=>'star',         'name'=>'CSF',                  'desc'=>'Customer satisfaction feedback'],
		['v'=>6,'icon'=>'download',     'name'=>'Publication Downloads','desc'=>'Materials downloaded by patrons'],
		['v'=>7,'icon'=>'upload',       'name'=>'Publication Uploads',  'desc'=>'Catalog records added in the period'],
	];
	$dateLabels = [2=>'Date Registered',3=>'Date Borrowed',4=>'Date of Inquiry',5=>'Date of CSF',6=>'Date Downloaded',7=>'Date Uploaded'];
@endphp
<div class="page-content">
	<nav class="page-breadcrumb">
		<ol class="breadcrumb"><li class="breadcrumb-item active" aria-current="page">Reports</li></ol>
	</nav>

	<form method="POST" id="frm" target="_blank" action="{{ url('/admin/print-report') }}">
		@csrf

		{{-- Step 1: choose a report --}}
		<div class="card mb-3">
			<div class="card-body">
				<h6 class="card-title mb-1">1. Choose a report</h6>
				<p class="report-hint mb-3" style="font-size:13px;">Pick the type of report you want to generate.</p>
				<div class="report-tiles">
					@foreach($reportTypes as $t)
						<label class="report-tile">
							<input type="radio" name="typeReport" value="{{ $t['v'] }}" data-datelabel="{{ $dateLabels[$t['v']] ?? '' }}">
							<span class="rt-inner">
								<i data-feather="{{ $t['icon'] }}"></i>
								<span class="rt-name">{{ $t['name'] }}</span>
								<span class="rt-desc">{{ $t['desc'] }}</span>
							</span>
						</label>
					@endforeach
				</div>
			</div>
		</div>

		{{-- Step 2: filters (contextual) --}}
		<div class="card mb-3" id="filters-card" style="display:none;">
			<div class="card-body">
				<h6 class="card-title mb-3">2. Set the filters</h6>

				{{-- Acquisition filters (type 1) --}}
				<div class="filter-div" id="filter-acquisition">
					<div class="row g-3">
						<div class="col-md-4">
							<label class="form-label">Keyword / Phrase</label>
							<input type="text" class="form-control" name="keyword" placeholder="Enter keyword/phrase (optional)">
						</div>
						<div class="col-md-4">
							<label class="form-label">Material Type</label>
							<select class="js-example-basic-multiple form-select" id="selectMaterial" name="selectMaterial[]" multiple="multiple" data-width="100%">
								<option value="0" selected>All</option>
								@foreach(getMaterials() AS $list)
									<option value="{{ $list->id }}">{{ $list->name }}</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Source</label>
							<select class="js-example-basic-multiple form-select" id="selectSource" name="selectSource[]" multiple="multiple" data-width="100%">
								<option value="0" selected>All</option>
								@foreach(getConsortia('all') AS $list)
									<option value="{{ $list->name }}">{{ $list->name }}</option>
								@endforeach
							</select>
						</div>
					</div>
				</div>

				{{-- Date filters (types 2-7) --}}
				<div class="filter-div" id="filter-date">
					<label class="form-label" id="date_title">Date</label>
					<div class="btn-group d-block mb-3" role="group" aria-label="Date filter mode">
						<input type="radio" class="btn-check" name="select_type" id="st1" value="1" checked>
						<label class="btn btn-outline-primary btn-sm" for="st1">Single date</label>
						<input type="radio" class="btn-check" name="select_type" id="st2" value="2">
						<label class="btn btn-outline-primary btn-sm" for="st2">Monthly</label>
						<input type="radio" class="btn-check" name="select_type" id="st3" value="3">
						<label class="btn btn-outline-primary btn-sm" for="st3">Yearly</label>
						<input type="radio" class="btn-check" name="select_type" id="st4" value="4">
						<label class="btn btn-outline-primary btn-sm" for="st4">Date range</label>
					</div>

					<div class="row filter_group" id="filter_date">
						<div class="col-md-4">
							<label class="form-label">Select Date</label>
							<input data-provide="datepicker" name="filter_date_dt" id="filter_date_dt" data-date-autoclose="true" class="form-control" value="{{ date('m/d/Y') }}">
						</div>
					</div>

					<div class="row filter_group g-3" id="filter_month" style="display:none;">
						<div class="col-md-4">
							<label class="form-label">Month</label>
							<select class="form-select" name="filter_month_mon" id="filter_month_mon">
								@foreach([1=>'January',2=>'February',3=>'March',4=>'April',5=>'May',6=>'June',7=>'July',8=>'August',9=>'September',10=>'October',11=>'November',12=>'December'] as $mn=>$mname)
									<option value="{{ $mn }}" {{ (int)date('n')==$mn ? 'selected':'' }}>{{ $mname }}</option>
								@endforeach
							</select>
						</div>
						<div class="col-md-4">
							<label class="form-label">Year</label>
							<select class="form-select" name="filter_month_year" id="filter_month_year">
								@for($i=(int)date('Y'); $i >= (int)date('Y')-5; $i--)<option value="{{ $i }}">{{ $i }}</option>@endfor
							</select>
						</div>
					</div>

					<div class="row filter_group" id="filter_year" style="display:none;">
						<div class="col-md-4">
							<label class="form-label">Year</label>
							<select class="form-select" name="filter_year_yr" id="filter_year_yr">
								@for($i=(int)date('Y'); $i >= (int)date('Y')-5; $i--)<option value="{{ $i }}">{{ $i }}</option>@endfor
							</select>
						</div>
					</div>

					<div class="row filter_group g-3" id="filter_duration" style="display:none;">
						<div class="input-daterange row g-3" data-provide="datepicker">
							<div class="col-md-4">
								<label class="form-label">From</label>
								<input type="text" class="form-control" name="start" value="{{ date('m/d/Y') }}">
							</div>
							<div class="col-md-4">
								<label class="form-label">To</label>
								<input type="text" class="form-control" name="end" value="{{ date('m/d/Y') }}">
							</div>
						</div>
					</div>
				</div>

				<div class="mt-4" id="report-actions">
					<button type="button" class="btn btn-primary" id="btnPreview"><i data-feather="eye" class="icon-sm me-1"></i>Preview</button>
					<button type="submit" class="btn btn-outline-secondary" id="btnPrint"><i data-feather="printer" class="icon-sm me-1"></i>Print / Export</button>
				</div>
			</div>
		</div>
	</form>

	{{-- Step 3: preview --}}
	<div class="card" id="preview-card" style="display:none;">
		<div class="card-body">
			<h6 class="card-title mb-3">3. Preview</h6>
			<div class="report-preview-box" id="report-preview">
				<p class="report-hint mb-0">Set your filters and click <strong>Preview</strong> to see the results here, then <strong>Print / Export</strong> for the printable report (with Excel export).</p>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js')
<script src="{{ asset('admin/nobleui/template/assets/vendors/select2/select2.min.js') }}"></script>
<script>
$(function () {
	'use strict';
	if (window.feather) feather.replace();
	$(".js-example-basic-multiple").select2();

	// Keep "All" and specific selections mutually exclusive in the multi-selects
	$('.js-example-basic-multiple').on('select2:select', function (e) {
		var id = this.id;
		if (e.params.data.id == 0) { $('#'+id).val([0]).trigger('change'); }
		else { $('#'+id+' option[value="0"]').prop('selected', false); $('#'+id).trigger('change.select2'); }
	});

	// Choosing a report type -> show the right filters
	$('input[name=typeReport]').on('change', function () {
		var v = this.value;
		$('#filters-card, #preview-card, #report-actions').show();
		$('.filter-div').hide();
		if (v === '1') {
			$('#filter-acquisition').show();
		} else {
			$('#filter-date').show();
			$('#date_title').text($(this).data('datelabel') || 'Date');
		}
		$('#report-preview').html('<p class="report-hint mb-0">Click <strong>Preview</strong> to generate the report.</p>');
	});

	// Date-mode pills -> show the matching input group
	$('input[name=select_type]').on('change', function () {
		$('.filter_group').hide();
		$({'1':'#filter_date','2':'#filter_month','3':'#filter_year','4':'#filter_duration'}[this.value]).show();
	});

	// Preview (AJAX, in-page)
	$('#btnPreview').on('click', function () {
		if (!$('input[name=typeReport]:checked').length) { alert('Please choose a report type first.'); return; }
		var $box = $('#report-preview');
		$box.html('<div class="text-muted p-2">Loading&hellip;</div>');
		$.ajax({
			url: '{{ url("/admin/report-preview") }}',
			method: 'POST',
			data: $('#frm').serialize(),
			success: function (html) { $box.html(html); },
			error: function () { $box.html('<div class="alert alert-danger mb-0">Could not generate the report. Please check the filters and try again.</div>'); }
		});
	});

	// Print requires a type
	$('#frm').on('submit', function (e) {
		if (!$('input[name=typeReport]:checked').length) { e.preventDefault(); alert('Please choose a report type first.'); }
	});
});
</script>
@endsection
