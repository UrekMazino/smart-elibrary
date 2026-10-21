{{-- One row of the Material Types utility. data-row feeds the page's action handlers. --}}
<tr data-row="{{ json_encode(['id' => $m->id, 'name' => $m->name, 'tooltip' => $m->tooltip, 'records' => $m->records, 'marc' => (bool) $m->is_marc_type]) }}">
    <td>
        <span class="mt-name">{{ $m->name }}</span>
        @if($m->is_marc_type)<span class="mt-code ms-1" title="MARC LDR/06 code">{{ $m->code }}</span>@endif
    </td>
    <td class="mt-desc">{{ $m->tooltip ?: '—' }}</td>
    <td class="text-end mt-count">{{ number_format($m->records) }}</td>
    <td>
        <span class="mt-badge {{ $m->is_active ? 'on' : 'off' }}">{{ $m->is_active ? 'Shown' : 'Hidden' }}</span>
    </td>
    <td class="text-center">
        <div class="btn-group btn-group-sm">
            <button type="button" class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" style="color:#fff;">Action</button>
            <div class="dropdown-menu dropdown-menu-end">
                <a class="dropdown-item" href="#" data-act="edit">Edit</a>
                <a class="dropdown-item" href="#" data-act="toggle">{{ $m->is_active ? 'Hide from lists' : 'Show in lists' }}</a>
                @if($m->is_marc_type)
                    @if($m->records > 0)<a class="dropdown-item" href="#" data-act="merge">Move records to…</a>@endif
                @else
                    <a class="dropdown-item" href="#" data-act="merge">Merge into…</a>
                    <div class="dropdown-divider"></div>
                    @if($m->in_use)
                        <span class="dropdown-item disabled" title="In use by {{ $m->in_use }} record/batch/preset reference(s) — merge it instead">Delete (in use)</span>
                    @else
                        <a class="dropdown-item text-danger" href="#" data-act="delete">Delete</a>
                    @endif
                @endif
            </div>
        </div>
    </td>
</tr>
