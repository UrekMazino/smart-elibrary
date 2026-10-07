{{-- Filter toolbar for the Record List and On-Hold list. Options come from the list's own records
     (CatalogingController::catalogListFilterOptions) with counts; the filtering itself runs on the
     server (applyCatalogListFilters), so paging, sorting and search keep working.
     Script: admin.partials.catalog-list-filters-js. --}}
<style>
    .cl-filters { display: flex; flex-wrap: wrap; gap: 10px 12px; align-items: flex-end; padding: 12px 14px; margin-bottom: 12px;
                  background: #f8fafc; border: 1px solid #eef0f4; border-radius: 10px; }
    .cl-f { display: flex; flex-direction: column; flex: 1 1 150px; min-width: 140px; max-width: 220px; }
    .cl-f-wide { flex: 2 1 240px; max-width: 360px; }
    .cl-f label { font-size: 10.5px; text-transform: uppercase; letter-spacing: .5px; color: #7987a1; font-weight: 700; margin-bottom: 4px; }
    .cl-f .form-select { font-size: 13px; }
    /* A filter that's in use is outlined, so it's obvious why the list is shorter. */
    .cl-f.is-active .form-select,
    .cl-f.is-active .select2-container .select2-selection { border-color: #6571ff; box-shadow: 0 0 0 2px rgba(101, 113, 255, .14); }
    /* Author (select2) matches the native selects next to it: 34px, same border, text centred. */
    .cl-f .select2-container .select2-selection--single { height: 34px; border-color: #e9ecef; border-radius: 4px; }
    .cl-f .select2-container .select2-selection--single .select2-selection__rendered { line-height: 32px; padding: 0 28px 0 12.8px; font-size: 13px; color: #212529; }
    .cl-f .select2-container .select2-selection--single .select2-selection__arrow { height: 32px; right: 8px; width: 16px;
        background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23343a40' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M2 5l6 6 6-6'/%3e%3c/svg%3e") no-repeat center / 16px 12px; }
    .cl-f .select2-container .select2-selection--single .select2-selection__arrow b { display: none; }   /* Bootstrap's chevron instead */
    .cl-tail { display: flex; align-items: center; gap: 12px; margin-left: auto; min-height: 31px; }
    .cl-summary { font-size: 12.5px; color: #55606b; white-space: nowrap; }
    #fClear { font-size: 12.5px; padding: 0; text-decoration: none; }
    @media (max-width: 575.98px) {
        .cl-f, .cl-f-wide { max-width: none; flex-basis: 100%; }
        .cl-tail { margin-left: 0; width: 100%; justify-content: space-between; }
    }
</style>

<div class="cl-filters" id="clFilters" role="search" aria-label="Filter records">
    @if(!empty($filters['statuses']))
    {{-- Only on lists that mix both (a batch's records). --}}
    <div class="cl-f">
        <label for="fStatus">Status</label>
        <select id="fStatus" class="form-select form-select-sm" data-param="status">
            <option value="">All statuses</option>
            <option value="published">Published ({{ $filters['statuses']['published'] }})</option>
            <option value="onhold">On hold ({{ $filters['statuses']['onhold'] }})</option>
        </select>
    </div>
    @endif
    <div class="cl-f">
        <label for="fType">Type</label>
        <select id="fType" class="form-select form-select-sm" data-param="type">
            <option value="">All types</option>
            @foreach($filters['types'] as $t)
                <option value="{{ $t['id'] }}">{{ $t['name'] }} ({{ $t['count'] }})</option>
            @endforeach
            @if($filters['noType'])<option value="__none__">— No type — ({{ $filters['noType'] }})</option>@endif
        </select>
    </div>
    <div class="cl-f cl-f-wide">
        <label for="fAuthor">Author</label>
        <select id="fAuthor" class="form-select form-select-sm" data-param="author">
            <option value="">All authors</option>
            @if($filters['noAuthor'])<option value="__none__">— No author — ({{ $filters['noAuthor'] }})</option>@endif
            @foreach($filters['authors'] as $a)
                <option value="{{ $a['name'] }}">{{ $a['name'] }} ({{ $a['count'] }})</option>
            @endforeach
        </select>
    </div>
    <div class="cl-f">
        <label for="fYear">Year</label>
        <select id="fYear" class="form-select form-select-sm" data-param="year">
            <option value="">All years</option>
            @foreach($filters['years'] as $y)
                <option value="{{ $y['year'] }}">{{ $y['year'] }} ({{ $y['count'] }})</option>
            @endforeach
            @if($filters['noYear'])<option value="__none__">— No year — ({{ $filters['noYear'] }})</option>@endif
        </select>
    </div>
    <div class="cl-f">
        <label for="fResource">Resources</label>
        <select id="fResource" class="form-select form-select-sm" data-param="resource">
            <option value="">All records</option>
            <option value="fulltext-only">With full text</option>
            <option value="coverphoto-only">With cover photo</option>
            <option value="both">With full text &amp; cover</option>
            <option value="without-fulltext">Without full text</option>
            <option value="without-coverphoto">Without cover photo</option>
            <option value="without-both">Without full text &amp; cover</option>
            <option value="without-summary">Without summary</option>
        </select>
    </div>
    <div class="cl-tail">
        <span class="cl-summary" id="clSummary" aria-live="polite"></span>
        <button type="button" class="btn btn-link btn-sm" id="fClear" hidden>Clear filters</button>
    </div>
</div>
