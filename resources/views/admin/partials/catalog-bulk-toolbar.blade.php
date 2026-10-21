{{-- Actions menu for the selected rows of a catalog table (Record List / On-Hold / batch records).
     $list: 'published' | 'onhold' | 'batch'. Pair with admin.partials.catalog-bulk-js. --}}
<div class="dropdown bulk-actions" id="bulkActions">
    <button class="btn btn-primary btn-sm dropdown-toggle" type="button" id="bulkBtn"
            data-bs-toggle="dropdown" aria-expanded="false" disabled
            title="Tick records in the table to act on them">
        Actions <span class="badge bg-white text-primary ms-1" id="bulkCount">0</span>
    </button>
    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bulkBtn">
        <li><h6 class="dropdown-header" id="bulkHeader">Nothing selected</h6></li>
        @can('publish-hold.catalog')
            @if ($list !== 'published')
                <li><a class="dropdown-item" href="#" data-bulk="publish">Publish to OPAC</a></li>
            @endif
            @if ($list !== 'onhold')
                <li><a class="dropdown-item" href="#" data-bulk="hold">Move to On-Hold</a></li>
            @endif
            <li><hr class="dropdown-divider"></li>
        @endcan
        <li><a class="dropdown-item" href="#" data-bulk="export" data-format="mrc">Export MARC21 (.mrc)</a></li>
        <li><a class="dropdown-item" href="#" data-bulk="export" data-format="xml">Export MARCXML (.xml)</a></li>
        <li><a class="dropdown-item" href="#" data-bulk="reindex">Re-index for AI</a></li>
        @can('delete.catalog')
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item text-danger" href="#" data-bulk="delete">Delete</a></li>
        @endcan
    </ul>
</div>
