{{-- Save star on a homepage cover (upper-right corner, shown on hover). Filled yellow when the
     patron has saved the record. Expects: $id (catalog_id), $title. Styles and the click
     handler live in opac/index.blade.php. --}}
@php $__on = isSavedRecord($id); @endphp
<button type="button" class="save-star{{ $__on ? ' is-saved' : '' }}" data-home-save="{{ $id }}" aria-pressed="{{ $__on ? 'true' : 'false' }}"
	aria-label="Save {{ $title }}" title="{{ $__on ? 'Saved (click to remove)' : 'Save to your list' }}">
	<svg viewBox="0 0 16 16" aria-hidden="true"><path d="M8 1.8l1.9 3.9 4.3.6-3.1 3 .7 4.3L8 11.6l-3.8 2 .7-4.3-3.1-3 4.3-.6z"/></svg>
</button>
