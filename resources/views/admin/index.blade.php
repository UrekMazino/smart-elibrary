@extends("admin.layouts.master")

@section('css')
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <link rel="stylesheet" type="text/css" href="{{ asset('css/admin/dashboard.css') }}" />
  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.min.css') }}">
@endsection

@section('content')
<div class="page-content">

    <div class="d-flex justify-content-between align-items-center flex-wrap grid-margin">
      {{-- <div>
          
      </div> --}}
      <div class="d-flex align-items-center flex-wrap text-nowrap">
        <div class="input-group date datepicker wd-200 me-2 mb-2 mb-md-0" id="dashboardDate">
          <span class="input-group-text input-group-addon bg-transparent border-primary"><i data-feather="calendar" class=" text-primary"></i></span>
          <input type="text" name="dates" id="date-range" class="form-control border-primary bg-transparent">
        </div>
        <div class="btn-group mb-3 mb-md-0" role="group" aria-label="Basic example">
          <button type="button" class="btn btn-outline-primary dynamic-count" id="today">Today</button>
          <button type="button" class="btn btn-outline-primary dynamic-count" id="month">Month</button>
          <button type="button" class="btn btn-outline-primary dynamic-count" id="year">Year</button>
          <button type="button" class="btn btn-primary dynamic-count" id="total">Total</button>
        </div>
    
      </div>
    </div>
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>
    <div class="row">
      <div class="col-12 col-xl-12 stretch-card">
        <div class="row flex-grow-1">
          <div class="col-sm-6 col-md-6 col-xl-3 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">Titles</h6>
                  <div class="dropdown mb-2">
                    <button class="btn p-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> <span class="">Reports</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-6">
                    <h1 class="mb-2 titles-count-view">{{ number_format($totalTitles) }}</h1>
                  </div>
                  <div class="col-6 col-md-12 col-xl-6">
                    <div id="title-no-record"></div>
                    <div id="titlesChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-sm-6 col-md-6 col-xl-3 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">Publications</h6>
                  <div class="dropdown mb-2">
                    <button class="btn p-0" type="button" id="dropdownMenuButton2" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton2">
                      <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> <span class="">Reports</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-6">
                    <h1 class="mb-2 publication-count-view">{{ number_format($totalPublications) }}</h1>
                  </div>
                  <div class="col-6 col-md-12 col-xl-6">
                    <div id="publication-no-record"></div>
                    <div id="publicationsChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-md-6 col-xl-3 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">Patrons</h6>
                  <div class="dropdown mb-2">
                    <button class="btn p-0" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                      <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> <span class="">Reports</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-6">
                    <h1 class="mb-2 patron-count-view">{{ number_format($totalUsers) }}</h1>
                  </div>
                  <div class="col-6 col-md-12 col-xl-6">
                    <div id="patron-no-record"></div>
                    <div id="patronsChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-md-6 col-xl-3 grid-margin stretch-card">
            <div class="card">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-baseline">
                  <h6 class="card-title mb-0">Downloads</h6>
                  <div class="dropdown mb-2">
                    <button class="btn p-0" type="button" id="dropdownMenuButton1" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                      <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                    </button>
                    <div class="dropdown-menu" aria-labelledby="dropdownMenuButton1">
                      <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> <span class="">Reports</span></a>
                    </div>
                  </div>
                </div>
                <div class="row">
                  <div class="col-6 col-md-12 col-xl-6">
                    <h1 class="mb-2 download-count-view">{{ number_format($totalDownloads) }}</h1>
                  </div>
                  <div class="col-6 col-md-12 col-xl-6">
                    <div id="download-no-record"></div>
                    <div id="downloadsChart" class="mt-md-3 mt-xl-0"></div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div> <!-- row -->


    <div class="row">
      <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
              <h6 class="card-title">Titles Categories</h6>
              <div class="table-responsive">
                  <table id="titleCatTbl" class="table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>TITLES</th>
                        <th>TOTAL</th>
                        <th>DOWNLOADS</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($holdingsByMaterialType as $index => $material)
                        <tr>
                          <th>{{ $index+1 }}</th>
                          <td>{{ isset($materialTypesArr[$material->material_type_id]) ? $materialTypesArr[$material->material_type_id] : "No Designation" }}</td>
                          <td class="titleTotal" id="title-{{ $material->material_type_id }}-id">{{ number_format($material->num_holdings,0,".",","); }}</td>
                          <td class="downloadTitleTotal" id="download-title-{{ $material->material_type_id }}-id">{{ number_format($material->num_downloads,0,".",","); }}</td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
              </div>
          </div>
        </div>
      </div> 
      <div class="col-md-6 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
              <h6 class="card-title">PATRONS Categories</h6>
              <div class="table-responsive">
                  <table id="patronCatTbl" class="table">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>PATRONS</th>
                        <th>TOTAL</th>
                        <th>DOWNLOADS</th>
                      </tr>
                    </thead>
                    <tbody>
                      @foreach ($usersByPatronType as $index => $patron)
                        <tr>
                          <th>{{ $index+1 }}</th>
                          <td>{{ isset($patronTypesArr[$patron->patrontypes_id]) ? $patronTypesArr[$patron->patrontypes_id] : "No Designation" }}</td>
                          <td class="patronTotal" id="patron-{{ $patron->patrontypes_id }}-id">{{ number_format($patron->num_users,0,".",","); }}</td>
                          <td class="downloadPatronTotal" id="download-patron-{{ $patron->patrontypes_id }}-id">{{ number_format($patron->num_downloads,0,".",","); }}</td>
                        </tr>
                      @endforeach
            
                    </tbody>
                  </table>
              </div>
          </div>
        </div>
      </div>
    </div> {{-- end row --}}
    <div class="row">
      <div class="col-xl-12 grid-margin stretch-card">
          <div class="card">
              <div class="card-body">
                  <h6 class="card-title">AANR Collection by Consortia</h6>
                      <div id="aanrCollectionApexBar"></div>
              </div>
          </div>
      </div>
    </div>
    <div class="row">
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Responsiveness</h6>
            <div id="responsivenessChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Reliability</h6>
            <div id="reliabilityChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Access & Facilities</h6>
            <div id="accessAndFacilitiesChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Communication</h6>
            <div id="communicationChart"></div>
          </div>
        </div>
      </div>
    </div>
    <div class="row justify-content-md-center">
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Integrity</h6>
            <div id="integrityChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Assurance</h6>
            <div id="assuranceChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Outcome</h6>
            <div id="outcomeChart"></div>
          </div>
        </div>
      </div>
    </div>
  <div class="row">
      <div class="col-xl-6 grid-margin stretch-card">
          <div class="card">
              <div class="card-body">
                  <h6 class="card-title">Number of Views</h6>
                      <div id="numberOfViewsChart"></div>
              </div>
          </div>
      </div>
  </div>
</div>
@endsection

@section('js')


  <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
  <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
	<script>$('input[name="dates"]').daterangepicker();</script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/jquery.dataTables.min.js') }}"></script>
  <script src="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/js/dataTables.bootstrap5.min.js') }}"></script>
  <script src="{{ asset('js/admin/dashboard.js') }}"></script>
  <script>
    $(document).ready(function() {
        $('#titleCatTbl').DataTable({
            "scrollY": "250px",
            "paging": false,
            "ordering": true,
            "info": true,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10 // set number of rows per page
        });
    });
  </script>
  <script>
    $(document).ready(function() {
        $('#patronCatTbl').DataTable({
            "scrollY": "250px",
            "paging": false,
            "ordering": true,
            "info": true,
            "searching": false,
            "lengthChange": false,
            "pageLength": 10 // set number of rows per page
        });
    });
  </script>
  <script> 
    let countDynamicDates = '{{ url("/admin/count-dynamicdates/json") }}';
    let getRatingsUrl = '{{ url("/csf/get-ratings/json") }}';
    let getPerDateChartUrl = '{{ url("/admin/per-date-chart/json") }}';
  </script>

@endsection