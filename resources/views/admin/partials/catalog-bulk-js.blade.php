{{-- Row selection + bulk actions for a server-side catalog DataTable (#tbl).
     Include after catalog-list-filters-js. In the page:
       1. add `<th class="bs-cell">@include('admin.partials.catalog-bulk-checkall')</th>` as the FIRST header cell,
       2. put `bulkSelect.column` FIRST in the DataTable `columns` (and shift `order` by one),
       3. call `bulkSelect.init($('#tbl').DataTable())` after creating the table.
     $list: 'published' | 'onhold' | 'batch'; $batchId when $list === 'batch'. --}}
<style>
  #tbl th.bs-cell, #tbl td.bs-cell { width: 34px; text-align: center; padding-left: 6px !important; padding-right: 6px !important; cursor: default; }
  #tbl .bs-cell .form-check-input { margin: 0; cursor: pointer; width: 1.05em; height: 1.05em; }
  #tbl tbody tr.bs-selected, #tbl tbody tr.bs-selected:hover { background: var(--ui-navy-soft, #eaf1ff); }
  .bulk-banner { background: var(--ui-navy-soft, #f2f6ff); border: 1px solid var(--ui-border, #d6e2ff); border-radius: 6px; padding: 7px 12px; margin-bottom: 10px; font-size: 0.8125rem; color: var(--ui-text, #33415c); }
  .bulk-banner .btn-link { padding: 0 2px; font-size: 0.8125rem; vertical-align: baseline; }
  #bulkBtn .badge { font-weight: 600; }
</style>
<script nonce="{{ cspNonce() }}">
window.bulkSelect = (function () {
    var CFG = {
        list:      @json($list),
        batchId:   @json($batchId ?? null),
        url:       @json(route('records-bulk')),
        exportUrl: @json(route('records-bulk-export-ticket')),
        csrf:      @json(csrf_token()),
    };
    var VERB = { publish: 'Publish', hold: 'Move to On-Hold', delete: 'Delete', reindex: 'Re-index for AI' };
    var DONE = { publish: 'published to the OPAC', hold: 'moved to On-Hold', delete: 'deleted', reindex: 'queued for AI re-indexing' };

    // mode 'ids': exactly the ticked ids. mode 'all': every record matching the current
    // filters + search (`total` of them), except the ones unticked since (`excluded`).
    var state = { mode: 'ids', ids: new Set(), excluded: new Set(), total: 0, pageIds: [], lastIdx: null, sig: null };
    var table = null, $banner = $('<div class="bulk-banner" id="bulkBanner" hidden></div>');

    function fmt(n) { return Number(n).toLocaleString(); }
    function isSel(id) { return state.mode === 'all' ? !state.excluded.has(id) : state.ids.has(id); }
    function count() { return state.mode === 'all' ? Math.max(0, state.total - state.excluded.size) : state.ids.size; }

    function setSel(id, on) {
        if (state.mode === 'all') { on ? state.excluded.delete(id) : state.excluded.add(id); }
        else { on ? state.ids.add(id) : state.ids.delete(id); }
    }
    function clear() {
        state.mode = 'ids'; state.ids.clear(); state.excluded.clear(); state.lastIdx = null;
        render();
    }

    // The filters + search the "all" selection was made under. If they change, the selection
    // would silently mean something else, so it is cleared.
    function signature() {
        var f = {}; window.clFilters && clFilters.params(f);
        return JSON.stringify([f, table ? table.search() : '']);
    }

    function render() {
        var n = count(), pageAll = state.pageIds.length > 0, pageSome = false;
        state.pageIds.forEach(function (id) { if (isSel(id)) pageSome = true; else pageAll = false; });

        $('#tbl tbody tr').each(function () {
            var row = table && table.row(this).data();
            if (!row) return;
            var on = isSel(row.id);
            $(this).toggleClass('bs-selected', on).find('input.bs-row').prop('checked', on);
        });
        $('#bsAll').prop('checked', pageAll).prop('indeterminate', !pageAll && pageSome);

        $('#bulkBtn').prop('disabled', n === 0);
        $('#bulkCount').text(fmt(n));
        $('#bulkHeader').text(n ? fmt(n) + ' record' + (n === 1 ? '' : 's') + ' selected' : 'Nothing selected');

        // Gmail-style banner: offer "all matching" once the whole page is ticked.
        var html = '';
        if (state.mode === 'all') {
            html = 'All <strong>' + fmt(n) + '</strong> matching record' + (n === 1 ? ' is' : 's are') + ' selected' +
                   (state.excluded.size ? ' (' + fmt(state.excluded.size) + ' unticked)' : '') +
                   '. <button type="button" class="btn btn-link" data-bs-act="clear">Clear selection</button>';
        } else if (pageAll && state.total > state.pageIds.length) {
            html = 'All <strong>' + fmt(state.pageIds.length) + '</strong> records on this page are selected. ' +
                   '<button type="button" class="btn btn-link" data-bs-act="all">Select all ' + fmt(state.total) + ' matching records</button>';
        } else if (n > 0) {
            html = '<strong>' + fmt(n) + '</strong> selected' + (n > state.pageIds.filter(isSel).length ? ' across pages' : '') +
                   '. <button type="button" class="btn btn-link" data-bs-act="clear">Clear selection</button>';
        }
        $banner.html(html).prop('hidden', html === '');
    }

    function payload(extra) {
        var p = $.extend({ _token: CFG.csrf, list: CFG.list, scope: state.mode }, extra || {});
        if (CFG.batchId) p.batch_id = CFG.batchId;
        if (state.mode === 'all') {
            if (window.clFilters) clFilters.params(p);   // status / type / author / year / resource
            p.search = table.search();
            p.excluded = Array.from(state.excluded);
        } else {
            p.ids = Array.from(state.ids);
        }
        return p;
    }

    function confirmAction(action, n) {
        var noun = fmt(n) + ' record' + (n === 1 ? '' : 's');
        var text = {
            publish: noun + ' will be published and become visible in the OPAC and to AI mode.',
            hold:    noun + ' will be moved to On-Hold and hidden from the OPAC and AI mode.',
            reindex: noun + ' will be queued for AI (RAG) re-indexing.',
            delete:  noun + ' will be deleted. They disappear from every list, the OPAC and AI mode.',
        }[action];
        var opts = { title: VERB[action] + ' ' + noun + '?', text: text, icon: action === 'delete' ? 'warning' : 'question',
                     showCancelButton: true, confirmButtonText: VERB[action], cancelButtonText: 'Cancel', reverseButtons: true };
        if (action === 'delete') {
            opts.confirmButtonColor = '#dc3545';
            if (n > 20) {   // a big delete needs a deliberate confirmation
                opts.input = 'text';
                opts.inputPlaceholder = 'Type DELETE to confirm';
                opts.preConfirm = function (v) {
                    if ((v || '').trim().toUpperCase() !== 'DELETE') { Swal.showValidationMessage('Type DELETE to confirm'); return false; }
                    return true;
                };
            }
        }
        return Swal.fire(opts);
    }

    function run(action) {
        var n = count();
        if (!n) return;
        confirmAction(action, n).then(function (r) {
            if (!r.isConfirmed) return;
            window.appBusy && appBusy.show();
            $.post(CFG.url, payload({ action: action }))
                .done(function (res) {
                    var skipped = (res.selected || 0) - (res.changed || 0);
                    Swal.fire({ toast: true, position: 'top-end', icon: 'success', showConfirmButton: false, timer: 3500,
                                title: fmt(res.changed || 0) + ' record' + (res.changed === 1 ? '' : 's') + ' ' + DONE[action],
                                text: skipped > 0 ? fmt(skipped) + ' already ' + (action === 'publish' ? 'published' : 'on hold') : '' });
                    clear();
                    table.ajax.reload(null, false);
                })
                .fail(function (xhr) {
                    var msg = xhr.status === 403 ? 'You do not have permission to do that.'
                            : xhr.status === 419 ? 'Your session expired. Reload the page and try again.'
                            : 'The action could not be completed. Please try again.';
                    Swal.fire('Not done', msg, 'error');
                })
                .always(function () { window.appBusy && appBusy.hide(); });
        });
    }

    // Export: POST the selection for a short-lived ticket, then download it with a plain GET.
    // (Download managers / browsers may re-request a download URL with HEAD or GET, which a
    // POST-only download can't survive.)
    function exportAs(format) {
        $.post(CFG.exportUrl, payload())
            .done(function (res) {
                Swal.fire({ toast: true, position: 'top-end', icon: 'info', showConfirmButton: false, timer: 3000,
                            title: 'Downloading ' + fmt(res.count) + ' record(s) as ' + (format === 'xml' ? 'MARCXML' : 'MARC21') + '…' });
                window.location.href = res.urls[format];   // attachment: the page stays put
            })
            .fail(function (xhr) {
                Swal.fire('Export failed', xhr.status === 403 ? 'You do not have permission to do that.'
                    : xhr.status === 419 ? 'Your session expired. Reload the page and try again.'
                    : 'The export could not be prepared. Please try again.', 'error');
            });
    }

    function init(dt) {
        table = dt;
        $('#tbl').closest('.table-responsive').before($banner);

        // A new filter / search: the old selection no longer means the same rows.
        $('#tbl').on('preXhr.dt', function () {
            var sig = signature();
            if (state.sig !== null && sig !== state.sig && count() > 0) clear();
            state.sig = sig;
        });
        $('#tbl').on('xhr.dt', function (e, s, json) { if (json) state.total = json.iTotalDisplayRecords || 0; });
        $('#tbl').on('draw.dt', function () {
            state.pageIds = table.rows({ page: 'current' }).data().toArray().map(function (r) { return r.id; });
            state.lastIdx = null;
            render();
        });

        // Row checkbox (shift-click ticks/unticks the range since the last click on this page).
        $('#tbl').on('click', 'td.bs-cell', function (e) {
            var $cb = $(this).find('input.bs-row');
            if (!$cb.length) return;
            if (e.target !== $cb[0]) $cb.prop('checked', !$cb.prop('checked'));
            var id = +$cb.data('id'), on = $cb.prop('checked'), idx = state.pageIds.indexOf(id);
            if (e.shiftKey && state.lastIdx !== null && idx > -1) {
                var a = Math.min(idx, state.lastIdx), b = Math.max(idx, state.lastIdx);
                for (var i = a; i <= b; i++) setSel(state.pageIds[i], on);
            } else {
                setSel(id, on);
            }
            state.lastIdx = idx;
            render();
        });

        // Header checkbox: the current page.
        $('#tbl').on('click', '#bsAll', function () {
            var on = $(this).prop('checked');
            state.pageIds.forEach(function (id) { setSel(id, on); });
            if (state.mode === 'all' && state.excluded.size >= state.total) clear(); else render();
        });

        $banner.on('click', '[data-bs-act]', function () {
            if ($(this).data('bs-act') === 'all') { state.mode = 'all'; state.ids.clear(); state.excluded.clear(); render(); }
            else clear();
        });

        $('#bulkActions').on('click', '[data-bulk]', function (e) {
            e.preventDefault();
            var a = $(this).data('bulk');
            if (a === 'export') exportAs($(this).data('format')); else run(a);
        });
    }

    return {
        init: init,
        clear: clear,
        // First column of the DataTable: the row checkbox.
        column: { data: null, orderable: false, searchable: false, className: 'bs-cell',
                  render: function (d, type, row) {
                      return type === 'display'
                          ? '<input type="checkbox" class="form-check-input bs-row" data-id="' + (+row.id) + '" aria-label="Select record ' + (+row.id) + '">'
                          : '';
                  } },
    };
})();
</script>
