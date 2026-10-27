{{-- Included inside a <script nonce="{{ cspNonce() }}"> next to OK_BADGE / NO_BADGE: the Cover column's tag for a generated
     placeholder cover. A placeholder counts as a cover; the tag tells staff it is not a real one. --}}
@php
    $placeholderTag = settingEnabled('enable_placeholder_covers')
        ? '<span class="badge rounded-pill bg-light text-secondary border ms-1" title="Generated placeholder cover. Upload a real cover, or turn the placeholder off, on the record page.">Placeholder</span>'
        : '<span class="badge rounded-pill bg-light text-secondary border ms-1" title="Placeholder covers are turned off for all records in Settings, so the OPAC shows no cover.">Placeholder &middot; hidden</span>';
@endphp
var PLACEHOLDER_TAG = @json($placeholderTag);
