{{-- Add-a-MARC-field picker. Shared by: create record, edit record, create batch.
     Each host page wires the #marc-table DataTable + the .add-field-link / .add-tag-link
     click handler; this file owns the shell + look. --}}
<div class="modal fade" id="viewAddFieldModal" tabindex="-1" aria-labelledby="viewAddFieldModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content aft-modal">
            <div class="modal-header">
                <div>
                    <h6 class="modal-title mb-0" id="viewAddFieldModalTitle">
                        <i data-feather="tag" class="icon-sm me-1"></i>Add a MARC Field
                    </h6>
                    <p class="aft-sub">Search by tag number or name, then click <strong>Add</strong>.</p>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table id="marc-table" class="table aft-table w-100">
                        <thead>
                            <tr>
                                <th style="width: 96px">Tag</th>
                                <th>Description</th>
                                <th style="width: 96px" class="text-end">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<style>
    #viewAddFieldModal .modal-header { align-items: flex-start; }
    #viewAddFieldModal .modal-title { font-size: 0.9375rem; font-weight: 700; color: var(--ui-text, #22283b); }
    #viewAddFieldModal .aft-sub { margin: 3px 0 0; font-size: 0.75rem; color: var(--ui-muted, #8a94a6); }

    #viewAddFieldModal .aft-table { font-size: 0.8125rem; margin-bottom: 0; }
    #viewAddFieldModal .aft-table thead th {
        font-size: 0.65625rem; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1);
        border-bottom: 2px solid var(--ui-border, #eef0f2); padding: 8px 10px; white-space: nowrap;
    }
    #viewAddFieldModal .aft-table tbody td { padding: 6px 10px; vertical-align: middle; border-color: var(--ui-border, #f0f2f4); }
    #viewAddFieldModal .aft-table tbody tr:hover { background: var(--ui-surface-2, #f4f9fb); }

    /* Tag code as a mono chip */
    #viewAddFieldModal .mtag-code {
        font-family: ui-monospace, SFMono-Regular, Menlo, monospace; font-weight: 700; font-size: 0.78125rem;
        color: var(--ui-success-ink, #1c6b57); background: var(--ui-surface-2, #e4eff1); border-radius: 5px; padding: 2px 8px; display: inline-block;
    }
    #viewAddFieldModal .mtag-desc { color: var(--ui-text, #33415c); text-decoration: none; }
    #viewAddFieldModal .mtag-desc:hover { color: #0d6efd; text-decoration: underline; }

    /* DataTable chrome: search, length, info, paging */
    #viewAddFieldModal .dataTables_filter { text-align: left; margin-bottom: 10px; }
    #viewAddFieldModal .dataTables_filter label { font-size: 0; }   /* hide the "Search:" text */
    #viewAddFieldModal .dataTables_filter input {
        border: 1px solid var(--ui-border, #dfe3e8); border-radius: 20px; padding: 6px 14px; font-size: 0.8125rem;
        margin-left: 0; min-width: 240px;
    }
    #viewAddFieldModal .dataTables_filter input:focus { border-color: #0d6efd; box-shadow: 0 0 0 3px rgba(13,110,253,.12); outline: none; }
    #viewAddFieldModal .dataTables_length { font-size: 0.75rem; color: var(--ui-muted, #8a94a6); }
    #viewAddFieldModal .dataTables_length select { font-size: 0.75rem; padding: 2px 6px; border-radius: 5px; }
    #viewAddFieldModal .dataTables_info { font-size: 0.71875rem; color: var(--ui-muted-2, #a3adc2); padding-top: 10px; }
    #viewAddFieldModal .dataTables_paginate { padding-top: 8px; }
    #viewAddFieldModal .dataTables_paginate .paginate_button { font-size: 0.75rem; padding: 3px 9px !important; }
    #viewAddFieldModal .dataTables_empty { color: var(--ui-muted-2, #a3adc2); font-size: 0.8125rem; }
</style>
