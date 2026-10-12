{{-- One advanced-search row: [AND/OR/NOT] field, match, value. $i = row index, $row = values. --}}
@php use App\Services\CatalogSearchParams as SP; @endphp
<div class="sr-adv-row">
    @if($i === 0)
        <span class="sr-adv-where">Where</span>
        <input type="hidden" name="adv[{{ $i }}][join]" value="AND">
    @else
        <label class="visually-hidden" for="sr-adv-join-{{ $i }}">Combine with the rows above</label>
        <select id="sr-adv-join-{{ $i }}" name="adv[{{ $i }}][join]">
            @foreach(SP::JOINS as $join)<option value="{{ $join }}" @selected($row['join'] === $join)>{{ $join }}</option>@endforeach
        </select>
    @endif
    <label class="visually-hidden" for="sr-adv-field-{{ $i }}">Field</label>
    <select id="sr-adv-field-{{ $i }}" name="adv[{{ $i }}][field]">
        @foreach(SP::FIELDS as $key => $label)<option value="{{ $key }}" @selected($row['field'] === $key)>{{ $label }}</option>@endforeach
    </select>
    <label class="visually-hidden" for="sr-adv-op-{{ $i }}">Match</label>
    <select id="sr-adv-op-{{ $i }}" name="adv[{{ $i }}][op]">
        @foreach(SP::OPS as $key => $label)<option value="{{ $key }}" @selected($row['op'] === $key)>{{ $label }}</option>@endforeach
    </select>
    <label class="visually-hidden" for="sr-adv-value-{{ $i }}">Words</label>
    <input type="text" id="sr-adv-value-{{ $i }}" name="adv[{{ $i }}][value]" value="{{ $row['value'] }}" maxlength="120"
           placeholder="{{ $i ? 'e.g. food security' : 'e.g. nutrition survey' }}">
    <button type="button" class="sr-icon-btn" data-adv-remove aria-label="Remove this row">
        <svg width="12" height="12" viewBox="0 0 16 16" aria-hidden="true"><path stroke="currentColor" stroke-width="2" stroke-linecap="round" d="M3 3l10 10M13 3L3 13"/></svg>
    </button>
</div>
