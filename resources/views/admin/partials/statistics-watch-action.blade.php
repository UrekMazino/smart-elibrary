{{-- The statistics watch's action for an account: "Take out" (the dialog), or "Put back" for one out of the rankings.
     Banned and removed accounts are out while they stay so: nothing to do here. --}}
@if ($p->status === 'out')
    <form method="POST" action="{{ route('admin.statistics-watch.put-back') }}" class="d-inline">
        @csrf
        @method('DELETE')
        <input type="hidden" name="user" value="{{ $p->id }}">
        <button type="submit" class="btn btn-outline-secondary btn-xs">Put back</button>
    </form>
@elseif ($p->status === null)
    <button type="button" class="btn btn-outline-danger btn-xs" data-take-out="[{{ $p->id }}]" data-name="{{ $p->name }}">Take out</button>
@else
    <span class="text-muted">—</span>
@endif
