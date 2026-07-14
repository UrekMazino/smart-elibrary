@extends("admin.layouts.master")

@section('css')
  <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
  <link rel="stylesheet" type="text/css" href="{{ asset('css/admin/dashboard.css') }}" />
  <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/DataTables/DataTables-1.12.1/css/dataTables.bootstrap5.min.css') }}">
  <style>
    /* ---- Dashboard redesign: minimalist, elegant ---- */
    .dash-head h4 { font-weight: 700; letter-spacing: .2px; color: #22283b; }
    .dash-head p  { color: #8a94a6; }
    .dash-section-label {
      font-size: 11px; font-weight: 700; letter-spacing: 1px; text-transform: uppercase;
      color: #9aa4b8; margin: 4px 0 12px;
    }
    .page-content .card { border: 1px solid #eef0f4; border-radius: 12px; box-shadow: 0 1px 2px rgba(20,30,60,.04); }

    /* Icon chips (soft tinted backgrounds) */
    .ic { width: 42px; height: 42px; border-radius: 11px; display: flex; align-items: center; justify-content: center; flex: 0 0 auto; }
    .ic i { width: 20px; height: 20px; }
    .ic-indigo { background: #eef0ff; color: #6571ff; }
    .ic-green  { background: #e6f7ee; color: #05a34a; }
    .ic-amber  { background: #fff5e0; color: #e0a106; }
    .ic-blue   { background: #e7f4ff; color: #1a86d0; }
    .ic-slate  { background: #eef1f6; color: #5a6a85; }
    .ic-violet { background: #f1ecff; color: #7c5cff; }

    /* KPI cards */
    .kpi-label { font-size: 12px; font-weight: 600; color: #7987a1; text-transform: uppercase; letter-spacing: .4px; }
    .kpi-num   { font-size: 28px; font-weight: 700; color: #22283b; line-height: 1; }
    .kpi-spark { width: 120px; max-width: 46%; }

    /* Operations cards (clickable) */
    a.op-card { display: block; text-decoration: none; transition: box-shadow .15s ease, transform .15s ease; }
    a.op-card:hover { box-shadow: 0 8px 22px rgba(20,30,60,.09); transform: translateY(-2px); }
    .op-num   { font-size: 24px; font-weight: 700; color: #22283b; line-height: 1; }
    .op-label { font-size: 12.5px; color: #7987a1; margin-top: 4px; }

    /* Collection completeness bars */
    .prog-row + .prog-row { margin-top: 14px; }
    .prog-top { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 6px; }
    .prog-top .t { font-size: 13px; color: #5a6a85; font-weight: 600; }
    .prog-top .v { font-size: 13px; color: #22283b; font-weight: 700; }
    .prog-top .v small { color: #9aa4b8; font-weight: 500; }
    .prog { height: 8px; border-radius: 6px; background: #eef1f6; overflow: hidden; }
    .prog > span { display: block; height: 100%; border-radius: 6px; }

    .csf-card .card-title { font-size: 13px; font-weight: 600; color: #5a6a85; }
  </style>
@endsection

@section('content')
<div class="page-content">

    {{-- Header + global date filter (drives the KPI numbers and the two breakdown tables) --}}
    <div class="dash-head d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
      <div>
        <h4 class="mb-0">Dashboard</h4>
        <p class="mb-0 small">Library overview and operations at a glance</p>
      </div>
      <div class="d-flex align-items-center flex-wrap text-nowrap gap-2">
        <div class="input-group date datepicker wd-200" id="dashboardDate">
          <span class="input-group-text input-group-addon bg-transparent border-primary"><i data-feather="calendar" class="text-primary"></i></span>
          <input type="text" name="dates" id="date-range" class="form-control border-primary bg-transparent">
        </div>
        <div class="btn-group" role="group" aria-label="Date range filter">
          <button type="button" class="btn btn-outline-primary dynamic-count" id="today">Today</button>
          <button type="button" class="btn btn-outline-primary dynamic-count" id="month">Month</button>
          <button type="button" class="btn btn-outline-primary dynamic-count" id="year">Year</button>
          <button type="button" class="btn btn-primary dynamic-count" id="total">Total</button>
        </div>
      </div>
    </div>

    {{-- ============ KPI cards ============ --}}
    <div class="row">
      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div class="ic ic-indigo"><i data-feather="book"></i></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="ddTitles" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="ddTitles">
                  <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> Reports</a>
                </div>
              </div>
            </div>
            <h6 class="kpi-label mt-3 mb-2">Titles</h6>
            <div class="d-flex align-items-end justify-content-between">
              <span class="kpi-num titles-count-view">{{ number_format($totalTitles) }}</span>
              <div class="kpi-spark">
                <div id="title-no-record"></div>
                <div id="titlesChart"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div class="ic ic-green"><i data-feather="check-square"></i></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="ddPub" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="ddPub">
                  <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> Reports</a>
                </div>
              </div>
            </div>
            <h6 class="kpi-label mt-3 mb-2">Publications</h6>
            <div class="d-flex align-items-end justify-content-between">
              <span class="kpi-num publication-count-view">{{ number_format($totalPublications) }}</span>
              <div class="kpi-spark">
                <div id="publication-no-record"></div>
                <div id="publicationsChart"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div class="ic ic-blue"><i data-feather="users"></i></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="ddPatrons" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="ddPatrons">
                  <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> Reports</a>
                </div>
              </div>
            </div>
            <h6 class="kpi-label mt-3 mb-2">Patrons</h6>
            <div class="d-flex align-items-end justify-content-between">
              <span class="kpi-num patron-count-view">{{ number_format($totalUsers) }}</span>
              <div class="kpi-spark">
                <div id="patron-no-record"></div>
                <div id="patronsChart"></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between align-items-start">
              <div class="ic ic-amber"><i data-feather="download"></i></div>
              <div class="dropdown">
                <button class="btn p-0" type="button" id="ddDl" data-bs-toggle="dropdown" aria-expanded="false">
                  <i class="icon-lg text-muted pb-3px" data-feather="more-horizontal"></i>
                </button>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="ddDl">
                  <a class="dropdown-item d-flex align-items-center" href="{{ url('/admin/report') }}"><i data-feather="book" class="icon-sm me-2"></i> Reports</a>
                </div>
              </div>
            </div>
            <h6 class="kpi-label mt-3 mb-2">Downloads</h6>
            <div class="d-flex align-items-end justify-content-between">
              <span class="kpi-num download-count-view">{{ number_format($totalDownloads) }}</span>
              <div class="kpi-spark">
                <div id="download-no-record"></div>
                <div id="downloadsChart"></div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    {{-- ============ Operations: needs-attention counts (link straight to the workflow) ============ --}}
    <div class="dash-section-label">Operations</div>
    <div class="row">
      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <a class="card op-card" href="{{ url('/admin/reservations') }}">
          <div class="card-body d-flex align-items-center">
            <div class="ic ic-amber"><i data-feather="clock"></i></div>
            <div class="ms-3">
              <div class="op-num">{{ number_format($pendingReservations) }}</div>
              <div class="op-label">Pending Reservations</div>
            </div>
            <i data-feather="chevron-right" class="ms-auto text-muted"></i>
          </div>
        </a>
      </div>
      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <a class="card op-card" href="{{ url('/admin/borrowed') }}">
          <div class="card-body d-flex align-items-center">
            <div class="ic ic-blue"><i data-feather="book-open"></i></div>
            <div class="ms-3">
              <div class="op-num">{{ number_format($activeLoans) }}</div>
              <div class="op-label">Active Loans</div>
            </div>
            <i data-feather="chevron-right" class="ms-auto text-muted"></i>
          </div>
        </a>
      </div>
      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <a class="card op-card" href="{{ url('/admin/on-hold-list') }}">
          <div class="card-body d-flex align-items-center">
            <div class="ic ic-slate"><i data-feather="pause-circle"></i></div>
            <div class="ms-3">
              <div class="op-num">{{ number_format($onHoldMaterials) }}</div>
              <div class="op-label">On-Hold Materials</div>
            </div>
            <i data-feather="chevron-right" class="ms-auto text-muted"></i>
          </div>
        </a>
      </div>
      <div class="col-sm-6 col-xl-3 grid-margin stretch-card">
        <a class="card op-card" href="{{ url('/admin/inquiry') }}">
          <div class="card-body d-flex align-items-center">
            <div class="ic ic-violet"><i data-feather="message-square"></i></div>
            <div class="ms-3">
              <div class="op-num">{{ number_format($totalInquiries) }}</div>
              <div class="op-label">Inquiries</div>
            </div>
            <i data-feather="chevron-right" class="ms-auto text-muted"></i>
          </div>
        </a>
      </div>
    </div>

    {{-- ============ Collection completeness + breakdown tables ============ --}}
    <div class="dash-section-label">Collection &amp; Patrons</div>
    <div class="row">
      @php
        $pctPub = $totalTitles ? round($totalPublications / $totalTitles * 100) : 0;
        $pctFt  = $totalTitles ? round($withFulltext / $totalTitles * 100) : 0;
        $pctCov = $totalTitles ? round($withCover / $totalTitles * 100) : 0;
      @endphp
      <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
            <h6 class="card-title">Collection Completeness</h6>
            <div class="prog-row">
              <div class="prog-top"><span class="t">Published</span><span class="v">{{ $pctPub }}% <small>({{ number_format($totalPublications) }}/{{ number_format($totalTitles) }})</small></span></div>
              <div class="prog"><span style="width: {{ $pctPub }}%; background: #05a34a;"></span></div>
            </div>
            <div class="prog-row">
              <div class="prog-top"><span class="t">With Full-text</span><span class="v">{{ $pctFt }}% <small>({{ number_format($withFulltext) }}/{{ number_format($totalTitles) }})</small></span></div>
              <div class="prog"><span style="width: {{ $pctFt }}%; background: #6571ff;"></span></div>
            </div>
            <div class="prog-row">
              <div class="prog-top"><span class="t">With Cover</span><span class="v">{{ $pctCov }}% <small>({{ number_format($withCover) }}/{{ number_format($totalTitles) }})</small></span></div>
              <div class="prog"><span style="width: {{ $pctCov }}%; background: #1a86d0;"></span></div>
            </div>
            <p class="text-muted small mb-0 mt-3">{{ number_format($onHoldMaterials) }} title(s) currently on hold (unpublished).</p>
          </div>
        </div>
      </div>

      <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
              <h6 class="card-title">Titles by Category</h6>
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

      <div class="col-md-4 grid-margin stretch-card">
        <div class="card">
          <div class="card-body">
              <h6 class="card-title">Patrons by Category</h6>
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
    </div>

    {{-- ============ AANR Collection by Consortia — commented out per request (not needed for now) ============ --}}
    {{--
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
    --}}

    {{-- ============ Client Satisfaction Feedback — hidden per request (not needed for now) ============ --}}
    {{--
    <div class="dash-section-label">Client Satisfaction Feedback</div>
    <div class="row">
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Responsiveness</h6>
            <div id="responsivenessChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Reliability</h6>
            <div id="reliabilityChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Access &amp; Facilities</h6>
            <div id="accessAndFacilitiesChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Communication</h6>
            <div id="communicationChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Integrity</h6>
            <div id="integrityChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Assurance</h6>
            <div id="assuranceChart"></div>
          </div>
        </div>
      </div>
      <div class="col-xl-3 col-md-4 col-sm-6 grid-margin stretch-card">
        <div class="card csf-card">
          <div class="card-body">
            <h6 class="card-title">Outcome</h6>
            <div id="outcomeChart"></div>
          </div>
        </div>
      </div>
    </div>
    --}}

    {{-- ============ Website traffic ============ --}}
    <div class="dash-section-label">Website Traffic</div>
    <div class="row">
      <div class="col-12 grid-margin stretch-card">
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
        if (window.feather) { feather.replace(); }
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
    let getViewsTrafficUrl = '{{ url("/admin/views-traffic/json") }}';
  </script>

@endsection
