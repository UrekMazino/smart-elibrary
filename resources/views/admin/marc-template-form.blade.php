@extends("admin.layouts.master")

{{-- Create + Edit MARC Template. Uses the SAME shared MarcEditor (marc-record-editor.js) and
     Add Tag picker as Create/Edit Record, in 'template' mode, so a template is built from
     exactly the fields, indicators and subfields a record will get when it loads it. --}}

@section('css')
    <link rel="stylesheet" href="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.css') }}">
    <style>
        .in-b { display: inline-block !important; }
        .block { display: block !important; }
        .width25 { width: 35% !important; }
        .mr { margin-right: 10px !important; }
        .mr5 { margin-right: 5px !important; }
        .sm-font { width: 18px !important; height: 18px !important; }
        .danger-clr { color: red; cursor: pointer; }
        .danger-clr:hover { color: darkred; }
        .dull-clr { color: gray; }
        .success-clr { color: green; cursor: pointer; }
        .success-clr:hover { color: lightgreen; }
        .strong { font-weight: bold; }
        .additional-subfield { display: block; }

        /* ---- Matches create-record / edit-record ---- */
        .cr-section-title {
            font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .5px; color: var(--ui-muted, #7987a1);
            font-weight: 700; margin: 0 0 8px; padding-bottom: 5px; border-bottom: 1px solid var(--ui-border, #eef0f2);
        }
        .cr-section-title.mt-4 { margin-top: 14px !important; }
        .cr-label { font-size: 0.78125rem; font-weight: 600; color: var(--ui-text, #33415c); margin-bottom: 4px; }
        .cr-req { color: var(--ui-danger-ink, #c92a4b); }
        .cr-hint { color: var(--ui-muted-2, #a3adc2); font-size: 0.71875rem; margin-top: 4px; display: block; }

        .cr-empty {
            display: none; text-align: center; padding: 34px 20px; border: 1px dashed var(--ui-border, #d7dded);
            border-radius: 10px; background: var(--ui-surface, #fafbfd); color: var(--ui-muted, #8a94a6);
        }
        .cr-empty .icn { font-size: 1.625rem; display: block; margin-bottom: 8px; }
        .cr-empty h6 { color: var(--ui-text-2, #55606b); font-size: 0.875rem; font-weight: 700; margin-bottom: 4px; }
        .cr-empty p { font-size: 0.78125rem; margin: 0; }

        .cr-actions { display: flex; gap: 8px; flex-wrap: wrap; }
        .cr-footer {
            position: sticky; bottom: 0; background: var(--ui-surface, #fff); border-top: 1px solid var(--ui-border, #eef0f2);
            padding: 14px 0 2px; margin-top: 6px; display: flex; gap: 8px;
        }
    </style>
    @include('admin.partials.marc-editor-styles')
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('utilities') }}">Utilities</a></li>
            <li class="breadcrumb-item"><a href="{{ route('marc-templates-index') }}">MARC Templates</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $template ? 'Edit' : 'Create' }}</li>
        </ol>
    </nav>
    <div class="row">
		<div class="col-lg-12 grid-margin stretch-card">
			<div class="card">
				<div class="card-body">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                        <h6 class="card-title mb-0">{{ $template ? 'Edit Template' : 'Create Template' }}</h6>
                        <div class="cr-actions">
                            <button class="btn btn-outline-primary btn-sm" type="button" data-call="showAddFieldModal">
                                <i data-feather="plus" class="icon-sm me-1"></i>Add Tag
                            </button>
                        </div>
                    </div>
                    <p class="text-muted mb-4" style="font-size: 0.78125rem;">
                        Add the MARC tags a record of this kind needs, with their indicators and subfields.
                        A subfield&rsquo;s value is an optional <strong>default</strong> that is pre-filled when the
                        template is loaded on <strong>Create Record</strong> or <strong>Edit Record</strong>. Tags marked
                        <strong>Required</strong> can&rsquo;t be removed from a record. LDR / 008 aren&rsquo;t part of a
                        template &mdash; the record pages generate them in the Fixed Fields panel.
                    </p>

                    <form method="post" action="{{ $template ? url('/admin/marc-templates/update') : url('/admin/marc-templates/store') }}" id="templateFrm">
                        @csrf
                        @if($template)
                            <input type="hidden" name="template_id" value="{{ $template->id }}">
                        @endif

                        <div class="cr-section-title">Template details</div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="cr-label">Name <span class="cr-req">*</span></label>
                                <input id="name" class="form-control @error('name') is-invalid @enderror" name="name" type="text"
                                       value="{{ old('name', $template->name ?? '') }}" placeholder="e.g. BOOKS_LC_FNRI" maxlength="255" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <span class="cr-hint">Shown in the MARC Template dropdown on Create / Edit Record.</span>
                            </div>
                        </div>

                        <div class="cr-section-title mt-4">MARC Fields</div>
                        @error('field_ids')
                            <div class="alert alert-danger py-2" style="font-size: 0.8125rem;">{{ $message }}</div>
                        @enderror
                        <div id="template-div"></div>
                        <div class="cr-empty">
                            <span class="icn">&#128218;</span>
                            <h6>No tags yet</h6>
                            <p>Use <strong>Add Tag</strong> to add the MARC fields this template should load.</p>
                        </div>

                        <input id="fieldIds" name="field_ids" type="hidden" value="[]">

                        <div class="cr-footer">
                            <button class="btn btn-primary" type="submit">
                                <i data-feather="save" class="icon-sm me-1"></i>{{ $template ? 'Update Template' : 'Create Template' }}
                            </button>
                            <a href="{{ route('marc-templates-index') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>
				</div>
			</div>
		</div>
	</div>
</div>
@include('admin.modals.add-field-modal')
@endsection

@section('js')
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net/jquery.dataTables.js') }}"></script>
<script src="{{ asset('admintemplate/nobleui/template/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.js') }}"></script>
{{-- Config before the scripts that consume it. --}}
<script nonce="{{ cspNonce() }}">
// This page's buttons (data-call; onclick= is blocked by the Content Security Policy).
AdminActions.allow(['showAddFieldModal']);

  let getMarcDetailsUrl = '{{ url("/admin/get-marc-tag-details") }}';
  let marcEditorMode    = 'template';
  // The template's tags in MarcEditor source shape (empty on create; re-posted fields after a validation error).
  let marcInitialFields = @json($initialFields);
</script>
<script src="{{ asset('js/admin/marc-record-editor.js') }}"></script>
<script nonce="{{ cspNonce() }}">
    // "Add" in the Add Tag modal -> append that MARC field to the template.
    $(document).on('click', '.add-field-link', function(event) {
        event.preventDefault();
        addTag($(this).data('row-id'));
    });

$(document).ready(function(){
    $('#marc-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: '{{ url("/admin/marc-tags-list/json") }}',
            // LDR/008 are generated on the record pages, never part of a template.
            data: function (d) { d.exclude_control = 1; }
        },
        columns: [
            { data: 'code',
                render: function (data) {
                    return '<span class="mtag-code">' + data + '</span>';
                }
            },
            { data: 'description',
                render: function (data, type, row) {
                    return '<a href="#" class="mtag-desc add-field-link" data-row-id="' + row.id + '">' + data + '</a>';
                }
            },
            { data: 'id', className: 'text-end',
                render: function (data, type, row) {
                    return '<button type="button" class="btn btn-sm btn-primary add-field-link" data-row-id="' + row.id + '">+ Add</button>';
                }
            },
        ],
        language: { search: '', searchPlaceholder: 'Search tag number or name…', lengthMenu: 'Show _MENU_' }
    });
});
</script>
@endsection
