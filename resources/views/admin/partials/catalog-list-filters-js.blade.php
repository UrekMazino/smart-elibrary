{{-- Script for admin.partials.catalog-list-filters. Include it BEFORE the page's DataTable init and
     pass clFilters.params as the table's ajax `data` function. --}}
<script nonce="{{ cspNonce() }}">
window.clFilters = (function () {
    var PARAMS = ['status', 'type', 'author', 'year', 'resource'];   // status: batch page only
    var $wrap = $('#clFilters');
    function sel(p) { return $wrap.find('[data-param="' + p + '"]'); }

    // Filters live in the URL (?type=..&author=..), so refresh / Back / a shared link keep them.
    var qs = new URLSearchParams(location.search);
    PARAMS.forEach(function (p) {
        var v = qs.get(p), $s = sel(p);
        if (v !== null && $s.find('option').filter(function () { return this.value === v; }).length) $s.val(v);
    });

    // Author can be thousands of names: make it searchable. (jQuery 3.6 stops select2 from
    // focusing its search box on open, so focus it ourselves — otherwise typing does nothing.)
    if ($.fn.select2) {
        sel('author').select2({ width: '100%' }).on('select2:open', function () {
            var f = document.querySelector('.select2-container--open .select2-search__field');
            if (f) f.focus();
        });
    }

    function values() {
        var o = {};
        PARAMS.forEach(function (p) { var v = sel(p).val(); if (v) o[p] = v; });
        return o;
    }
    function sync() {
        var v = values();
        PARAMS.forEach(function (p) { sel(p).closest('.cl-f').toggleClass('is-active', !!v[p]); });
        $('#fClear').prop('hidden', !Object.keys(v).length);
        var url = new URL(location.href);
        PARAMS.forEach(function (p) { if (v[p]) url.searchParams.set(p, v[p]); else url.searchParams.delete(p); });
        history.replaceState(null, '', url);
    }
    function reload() { sync(); if ($.fn.dataTable.isDataTable('#tbl')) $('#tbl').DataTable().ajax.reload(); }

    $wrap.on('change', 'select', reload);
    $('#fClear').on('click', function () {
        PARAMS.forEach(function (p) { sel(p).val('').trigger('change.select2'); });   // update select2's display only
        reload();
    });

    // "34 records" / "Showing 6 of 34 records" after every load.
    $('#tbl').on('xhr.dt', function (e, settings, json) {
        if (!json) return;
        var n = json.iTotalDisplayRecords, t = json.iTotalRecords;
        $('#clSummary').html(n === t
            ? '<strong>' + t + '</strong> record' + (t === 1 ? '' : 's')
            : 'Showing <strong>' + n + '</strong> of ' + t + ' records');
    });

    sync();
    return { params: function (d) { $.extend(d, values()); } };
})();
</script>
