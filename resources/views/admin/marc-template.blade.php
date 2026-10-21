@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
      .ac-head { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
      .ac-head h4 { font-weight:700; color:#22283b; margin:0; }
      .ac-head p  { color:#8a94a6; margin:2px 0 0; font-size:13px; }
      .page-content .card { border:1px solid #eef0f4; border-radius:12px; box-shadow:0 1px 2px rgba(20,30,60,.04); }
      #tbl { font-size:13px; width:100% !important; }
      #tbl thead th { font-size:11px; text-transform:uppercase; letter-spacing:.4px; color:#7987a1; white-space:nowrap; padding:9px 10px; border-bottom:2px solid #eef0f2; }
      #tbl tbody td { padding:8px 10px; vertical-align:middle; }
      #tbl tbody tr:hover { background:#f7f9fc; }
      .mt-id { color:#a3adc2; font-size:12px; }
      .mt-name { font-weight:600; color:#22283b; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <div class="ac-head">
      <div>
        <h4>MARC Templates</h4>
        <p>Reusable field templates used when cataloging records.</p>
      </div>
      @can('create.config.template')
        <a href="{{ url('/admin/marc-templates/create') }}" class="btn btn-primary btn-sm"><i data-feather="plus" class="icon-sm me-1"></i>New template</a>
      @endcan
    </div>

    <div class="card">
      <div class="card-body">
        <div class="table-responsive">
          <table id="tbl" class="table table-hover table-sm align-middle mb-0">
            <thead>
              <tr>
                <th style="width:70px">ID</th>
                <th>Name</th>
                <th style="width:92px" class="text-center">Action</th>
              </tr>
            </thead>
          </table>
        </div>
      </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>
$(document).ready(function () {
    if (window.feather) feather.replace();

    @if(session('success'))
    toast('success', @json(session('success')));
    @endif

    var table = $('#tbl').DataTable({
        processing: true,
        serverSide: true,
        autoWidth: false,
        ajax: '{{ url("/admin/marc-templates/json") }}',
        columns: [
            { data: 'id', className: 'mt-id text-nowrap', render: function (d, type) { return type === 'display' ? '#' + d : d; } },
            { data: 'name', render: function (d, type) { return type === 'display' ? '<span class="mt-name">' + (d == null ? '' : $('<div>').text(d).html()) + '</span>' : d; } },
            { data: null, orderable: false, className: 'text-center', render: function (d, type, row) {
                return '<div class="btn-group btn-group-sm" role="group">' +
                    '<button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">Action</button>' +
                    '<div class="dropdown-menu dropdown-menu-end">' +
                    @can("edit.config.template")   '<a class="dropdown-item" href="#" data-edit-url="{{ url("/admin/marc-templates/edit?template_id=") }}' + row.id + '">Edit</a>' + @endcan
                    @can("delete.config.template") '<button type="button" class="dropdown-item text-danger delete-btn" data-template-id="' + row.id + '">Delete</button>' + @endcan
                    '</div></div>';
            } },
        ]
    });

    @can("edit.config.template")
    $('#tbl').on('click', '.dropdown-item[data-edit-url]', function () {
        window.location.href = $(this).data('edit-url');
    });
    @endcan

    @can("delete.config.template")
    $('#tbl').on('click', '.delete-btn', function () {
        var templateId = $(this).data('template-id');
        Swal.fire({
            title: 'Delete this template?', icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, delete', cancelButtonText: 'No', reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            $.ajax({
                url: 'marc-templates/delete/' + templateId,
                type: 'DELETE',
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') },
                dataType: 'json',
                success: function (response) {
                    toast(response && response.success ? 'success' : 'error', response && response.success ? 'Template deleted.' : 'Something went wrong.');
                    table.ajax.reload(null, false);
                },
                error: function () { toast('error', 'Something went wrong.'); }
            });
        });
    });
    @endcan
});

function toast(icon, title) {
    Swal.mixin({ toast: true, position: 'top-end', showConfirmButton: false, timer: 2500, timerProgressBar: true }).fire({ icon: icon, title: title });
}
</script>
@endsection
