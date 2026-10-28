{{-- Save bookmark on a homepage cover: a ribbon hanging from the top edge of the book, near the right (shown on
     hover). Filled teal when the patron has saved the record (a bookmark, not a star: stars are ratings, gold is a #1 medal). Expects: $id (catalog_id), $title. Styles and the click
     handler live in opac/index.blade.php. --}}
@php $__on = isSavedRecord($id); @endphp
<button type="button" class="save-star{{ $__on ? ' is-saved' : '' }}" data-home-save="{{ $id }}" aria-pressed="{{ $__on ? 'true' : 'false' }}"
	aria-label="Save {{ $title }}" title="{{ $__on ? 'Saved (click to remove)' : 'Save to your list' }}">
	<svg viewBox="0 0 24 36" aria-hidden="true">
		<path class="bm-fill" d="M3 0h18v32l-9-6.5L3 32z"/>
		<path class="bm-edge" d="M3 0v32l9-6.5 9 6.5V0"/>{{-- white edge down the sides and round the notch; the top goes into the book --}}
	</svg>
</button>
