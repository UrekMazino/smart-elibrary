@extends("admin.layouts.master")

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
      /* Compact, readable people table — same treatment as the catalog lists. */
      #tbl { font-size: 13px; width: 100% !important; }
      #tbl thead th {
        font-size: 11px; text-transform: uppercase; letter-spacing: .4px;
        color: #7987a1; white-space: nowrap; padding: 9px 10px;
        border-bottom: 2px solid #eef0f2; vertical-align: middle;
      }
      #tbl tbody td { padding: 8px 10px; vertical-align: middle !important; white-space: normal; }
      #tbl tbody tr:hover { background: #f7f9fc; }

      /* Name cell: initials avatar + name + library code */
      .u-cell { display: flex; align-items: center; gap: 9px; }
      .u-avatar {
        flex: 0 0 30px; width: 30px; height: 30px; border-radius: 50%;
        background: #e4e9f2; color: #4a5a78; font-size: 11.5px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; letter-spacing: .3px;
      }
      .u-avatar.is-banned  { background: #ffe0e6; color: #c92a4b; }
      .u-avatar.is-blocked { background: #fff0d6; color: #a06e14; }
      .u-name { font-weight: 600; color: #22283b; }
      .u-sub  { color: #a3adc2; font-size: 11.5px; }

      /* `#tbl` prefix beats the template's `.badge { color:#fff }` (same specificity otherwise). */
      #tbl .pill-banned  { background: #ffe0e6; color: #c92a4b; font-weight: 600; font-size: 10.5px; }
      #tbl .pill-blocked { background: #fff0d6; color: #a06e14; font-weight: 600; font-size: 10.5px; }

      .cell-muted { color: #7987a1; }
      .addr {
        color: #7987a1; display: inline-block; max-width: 240px;
        overflow: hidden; text-overflow: ellipsis; white-space: nowrap; vertical-align: middle;
      }
      .date-main { color: #33415c; } .date-time { color: #a3adc2; font-size: 11.5px; }
      @media (max-width: 1200px) { .addr { max-width: 150px; } }

      /* Status filter chips */
      .chip {
        border: 1px solid #e4e9f2; background: #fff; color: #4a5a78; border-radius: 20px;
        padding: 4px 12px; font-size: 12px; font-weight: 600; line-height: 1.4;
      }
      .chip:hover { background: #f4f7fc; }
      .chip.active { background: #33509c; border-color: #33509c; color: #fff; }
      .chip .chip-n { opacity: .65; font-weight: 500; margin-left: 3px; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="card-title mb-0">PATRONS</h6>
                        <a href="{{ url('/admin/register') }}" class="btn btn-primary btn-sm">
                            <i data-feather="user-plus" class="icon-sm me-1"></i>Register a Patron
                        </a>
                    </div>
                    <p class="text-muted mb-3" style="font-size: 12.5px;">
                        Registered library users. A <strong>banned</strong> account cannot log in or
                        read materials; a <strong>blocked</strong> account can still browse but cannot
                        use chat or inquiries.
                    </p>

                    <div class="d-flex flex-wrap align-items-center gap-2 mb-3">
                        <button type="button" class="chip active" data-status="all">All<span class="chip-n" id="n-all"></span></button>
                        <button type="button" class="chip" data-status="active">Active<span class="chip-n" id="n-active"></span></button>
                        <button type="button" class="chip" data-status="blocked">Blocked<span class="chip-n" id="n-blocked"></span></button>
                        <button type="button" class="chip" data-status="banned">Banned<span class="chip-n" id="n-banned"></span></button>
                    </div>

                    <div class="table-responsive">
                        <table id="tbl" class="table table-hover table-sm align-middle mb-0">
                          <thead>
                            <tr>
                              <th>Name</th>
                              <th style="width: 150px">Contact No.</th>
                              <th style="width: 220px">Email</th>
                              <th style="width: 240px">Address</th>
                              <th style="width: 130px">Registered</th>
                              <th style="width: 92px" class="text-center">Action</th>
                            </tr>
                          </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="post" action="{{ url('/admin/patron-action') }}" id="frm" enctype="multipart/form-data">
  @csrf
  <input type="hidden" name="patron_id" id="patron_id">
  <input type="hidden" name="action" value="">
</form>
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
<script>

var statusFilter = 'all';   // which chip is selected; sent with every table request

function escapeHtml(text) {
  return (text == null ? '' : String(text))
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#039;').replace(/`/g, '&#96;');
}

// "Juan Dela Cruz" -> "JC"
function initials(name) {
  var parts = String(name || '?').trim().split(/\s+/);
  return ((parts[0] || '?')[0] + (parts.length > 1 ? parts[parts.length - 1][0] : '')).toUpperCase();
}

// Small status pills shown next to a patron's name.
function statusBadges(row) {
    var out = '';
    if (row.is_banned == 1)  out += ' <span class="badge pill-banned rounded-pill">Banned</span>';
    if (row.is_blocked == 1) out += ' <span class="badge pill-blocked rounded-pill">Blocked</span>';
    return out;
}

$(document).ready(function(){

$('#tbl').DataTable({
    processing: true,
    serverSide: true,
    autoWidth: false,
    pageLength: 25,
    order: [[0, 'asc']],            // default: Name A -> Z
    language: { search: '', searchPlaceholder: 'Search name, email, contact, library code…' },
    ajax: {
        url: '{{ url("/admin/patron/json") }}',
        data: function (d) { d.status_filter = statusFilter; }    // chips filter server-side
    },
    columns: [
        { data: 'name',
          render: function (d, type, row) {
            if (type !== 'display') return d;
            var tone = row.is_banned == 1 ? ' is-banned' : (row.is_blocked == 1 ? ' is-blocked' : '');
            return '<div class="u-cell">' +
                     '<span class="u-avatar' + tone + '">' + escapeHtml(initials(d)) + '</span>' +
                     '<span><span class="u-name">' + escapeHtml(d) + '</span>' + statusBadges(row) +
                       (row.library_code ? '<br><span class="u-sub">' + escapeHtml(row.library_code) + '</span>' : '') +
                     '</span>' +
                   '</div>';
          } },
        { data: 'contactnum', className: 'cell-muted text-nowrap',
          render: function (d, type) {
            if (type !== 'display') return d;
            return d ? escapeHtml(d) : '<span class="text-muted small">—</span>';
          } },
        { data: 'email', className: 'cell-muted',
          render: function (d, type) { return type === 'display' ? escapeHtml(d) : d; } },
        { data: 'location',
          render: function (d, type) {
            if (type !== 'display') return d;
            if (!d) return '<span class="text-muted small">—</span>';
            return '<span class="addr" title="' + escapeHtml(d) + '">' + escapeHtml(d) + '</span>';
          } },
        { data: 'created_at', className: 'text-nowrap',
          render: function (d, type) {
            if (type !== 'display') return d;
            var parts = String(d || '').split(' ');
            return '<span class="date-main">' + escapeHtml(parts[0] || '') + '</span>' +
                   (parts[1] ? '<br><span class="date-time">' + escapeHtml(parts[1].substring(0,5)) + '</span>' : '');
          } },
        { data: null, orderable: false, className: 'text-center',
          render: function (d, type, row) {
              var banLabel = row.is_banned == 1 ? 'Unban account' : 'Ban account';
              var blockLabel = row.is_blocked == 1 ? 'Unblock account' : 'Block account';
              return '<div class="btn-group btn-group-sm" role="group"><button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Action</button><div class="dropdown-menu dropdown-menu-end">' +
                  '<a class="dropdown-item" href="#" onclick="generateLibraryCode('+row.id+')">Generate Library Code</a>' +
                  '<a class="dropdown-item" href="#" onclick="sendFrm(\'frm\',\'reset\','+row.id+')">Reset Password</a>' +
                  (@json(auth()->user()->can('manage.patron.status')) ? (
                    '<div class="dropdown-divider"></div>' +
                    '<a class="dropdown-item text-warning" href="#" onclick="toggleUserFlag('+row.id+',\'blocked\')">'+blockLabel+'</a>' +
                    '<a class="dropdown-item text-danger" href="#" onclick="toggleUserFlag('+row.id+',\'banned\')">'+banLabel+'</a>'
                  ) : '') +
                  '<div class="dropdown-divider"></div>' +
                  '<a class="dropdown-item text-danger" href="#" onclick="sendFrm(\'frm\',\'delete\','+row.id+')">Delete</a>' +
                  '</div></div>';
          }

        },
    ]
});

// Keep the chip counts fresh on every draw (ban/block changes them). `xhr.dt` is the
// documented hook for extra fields in the response — it leaves DataTables' own parsing
// of aaData / iTotalRecords alone.
$('#tbl').on('xhr.dt', function (e, settings, json) {
    if (!json || !json.counts) return;
    $('#n-all').text(' ' + json.counts.all);
    $('#n-active').text(' ' + json.counts.active);
    $('#n-blocked').text(' ' + json.counts.blocked);
    $('#n-banned').text(' ' + json.counts.banned);
});

$('.chip').on('click', function () {
    $('.chip').removeClass('active');
    $(this).addClass('active');
    statusFilter = $(this).data('status');
    $('#tbl').DataTable().ajax.reload();   // back to page 1: the row set changed
});

if (window.feather) feather.replace();

});

// Ban/Block toggle: banned = no login + no Read; blocked = no chat/inquiry.
function toggleUserFlag(id, flag) {
    var isBan = flag === 'banned';
    Swal.fire({
      title: 'Are you sure?',
      html: isBan
              ? 'A <strong>banned</strong> account cannot log in and cannot use the Read feature.'
              : 'A <strong>blocked</strong> account cannot use chat / inquiry.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then(function (result) {
      if (!result.value) return;
      $.ajax({
        type: 'POST',
        url: '{{ url("/admin/user/toggle-flag") }}',
        data: { _token: '{{ csrf_token() }}', user_id: id, flag: flag },
        dataType: 'json',
        success: function (res) {
          $('#tbl').DataTable().ajax.reload(null, false);
          Swal.fire('Done', (flag.charAt(0).toUpperCase()+flag.slice(1)) + (res.value ? ' applied.' : ' removed.'), 'success');
        },
        error: function () { Swal.fire('Error', 'Could not update the account. Please try again.', 'error'); }
      });
    });
}

function sendFrm(frm,type,id = null)
{
    if(type == 'delete')
    {
      msg = "";
      $('#action').val('delete');
    }
    else
    {
      msg = "Default password is : `pcaarrdelibrary`";
      $('#action').val('reset');
    }

    Swal.fire({
      title: 'Are you sure?',
      text: msg,
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {
        $("#patron_id").val(id);
        $('#'+frm).submit();
      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'Your data is safe :)',
          'error'
        )
      }
    })
}

function generateLibraryCode(id)
{
  Swal.fire({
      title: 'Are you sure?',
      text: 'This will generate a new library code for this user.',
      icon: 'warning',
      showCancelButton: true,
      confirmButtonClass: 'me-2',
      confirmButtonText: 'Yes',
      cancelButtonText: 'No',
      reverseButtons: true
    }).then((result) => {
      if (result.value) {

        // Make the AJAX call to the URL
        $.ajax({
          type: 'GET',
          url: '{{ url("/admin/update-librarycode") }}',
          data: {
              "id": id
          },
          dataType: 'json',
          success: function(res) {
            console.log(res);
          },
          error: function(e) {
              // Handle the error case
              console.log(e);
          }
        });

      } else if (
        // Read more about handling dismissals
        result.dismiss === Swal.DismissReason.cancel
      ) {
        Swal.fire(
          'Cancelled',
          'No library code generated.',
          'error'
        )
      }
    })
}
</script>
@endsection
