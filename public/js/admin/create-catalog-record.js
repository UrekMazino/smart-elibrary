/* =====================================================================
 * MARC field editor for the Create Record page.
 *
 * State management (the point of this file):
 *   - ONE in-memory array `fields` is the single source of truth.
 *   - The DOM is rendered FROM that state; edits update the state.
 *   - Each field/subfield carries a stable numeric `_key` in a data-* attribute.
 *     Elements are located by closest([data-key]) + event delegation — never by
 *     a global id — so there are NO duplicate ids and dynamically-added rows work
 *     without rebinding.
 *   - The hidden #fieldData input is written ONCE, on form submit (serialize()),
 *     not re-parsed/re-stringified on every keystroke.
 *
 * Three entry points all funnel through here:
 *   1. MARC template selected      -> loadFromSource(res.fields)   (fixed fields)
 *   2. Parse a Record (page reload)-> loadFromSource(data.fields)  (single-import.js)
 *   3. Add Tag                     -> addTag(res)                  (editable field)
 *
 * Shapes:
 *   source field  : {tag:{id,code,description,isRequired}, subfields:[{id,code,description,default}], indicators:[{id,indicator}]}
 *   add-tag detail: {id,code,description, subfields:[{id,code,description}], first_indictors:[{id,indicator}], second_indicators:[...]}
 *   field_data out: {marctag:{index,id,code,description}, indicator:[{id,indicator},{id,indicator}], subfield:[{'sub-index',id,code,description,value}]}
 * ===================================================================== */

var MarcEditor = (function () {
    'use strict';

    var fields  = [];      // source of truth
    var keySeq  = 0;
    var subSeq  = 0;
    var $host;             // #template-div

    function nextKey()    { return ++keySeq; }
    function nextSubKey() { return ++subSeq; }
    function esc(s)       { return $('<div>').text(s == null ? '' : String(s)).html(); }

    function field(key)     { return fields.find(function (f) { return f._key == key; }); }
    function subOf(f, sk)   { return f ? f.subfield.find(function (s) { return s._key == sk; }) : null; }

    // ---- ingest ------------------------------------------------------------
    // "N - description" / "None" -> the value stored for a MARC indicator is the
    // leading token; keep the id so it round-trips.
    function normIndicators(indicators) {
        var out = [{ id: null, indicator: 'None' }, { id: null, indicator: 'None' }];
        (indicators || []).forEach(function (ind, i) {
            if (i > 1 || !ind) return;
            var token = String(ind.indicator || '').split('-')[0].trim() || 'None';
            out[i] = { id: ind.id != null ? ind.id : null, indicator: token };
        });
        return out;
    }

    function fromSource(sf) {
        return {
            _key: nextKey(),
            editable: false,                                  // fixed structure (template / parsed)
            marctag: { id: sf.tag.id, code: sf.tag.code, description: sf.tag.description },
            indicator: normIndicators(sf.indicators),
            subfield: (sf.subfields || []).map(function (s) {
                return {
                    _key: nextSubKey(),
                    id: s.id, code: s.code, description: s.description,
                    value: (s.default && s.default !== 'Enter value') ? s.default : ''
                };
            }),
            options: null
        };
    }

    // ---- public: load a whole record from source-shape fields --------------
    function loadFromSource(sourceFields) {
        fields = (sourceFields || []).map(fromSource);
        keySeq = fields.length;                              // keys already assigned above
        autoSelectMaterial();
        render();
    }

    // If a control field carries LDR$06 (type of record), preselect the Material Type
    // dropdown from it — preserves the behaviour the old parse renderer had.
    function autoSelectMaterial() {
        fields.forEach(function (f) {
            f.subfield.forEach(function (s) {
                if (s.code === 'LDR$06' && s.value) {
                    $('#materialTypeSelect option').each(function () {
                        if ($(this).hasClass(s.value)) $('#materialTypeSelect').val($(this).val());
                    });
                }
            });
        });
    }

    // ---- public: add one editable tag (Add Tag) ----------------------------
    function addTag(details) {
        fields.push({
            _key: nextKey(),
            editable: true,                                  // user can pick subfield codes, add/remove
            marctag: { id: details.id, code: details.code, description: details.description },
            indicator: [{ id: null, indicator: 'None' }, { id: null, indicator: 'None' }],
            subfield: [{ _key: nextSubKey(), id: null, code: null, description: null, value: '' }],
            options: {
                subfields: details.subfields || [],
                ind: [details.first_indictors || [], details.second_indicators || []]
            }
        });
        sortFields();
        render();
    }

    // LDR first, then alphabetical by tag code (mirrors the edit page).
    function sortFields() {
        fields.sort(function (a, b) {
            if (a.marctag.code === 'LDR') return -1;
            if (b.marctag.code === 'LDR') return 1;
            return String(a.marctag.code).toUpperCase().localeCompare(String(b.marctag.code).toUpperCase());
        });
    }

    // ---- mutations (called from delegated handlers) ------------------------
    function addSubfield(key) {
        var f = field(key);
        if (!f) return;
        f.subfield.push({ _key: nextSubKey(), id: null, code: null, description: null, value: '' });
        renderField(f);   // re-render just this field's card
    }
    function removeSubfield(key, sk) {
        var f = field(key);
        if (!f) return;
        f.subfield = f.subfield.filter(function (s) { return s._key != sk; });
        if (!f.subfield.length) f.subfield.push({ _key: nextSubKey(), id: null, code: null, description: null, value: '' });
        renderField(f);
    }
    function removeField(key) {
        fields = fields.filter(function (f) { return f._key != key; });
        $host.find('[data-key="' + key + '"]').remove();
    }

    // ---- render ------------------------------------------------------------
    function render() {
        $host.empty();
        fields.forEach(function (f) { $host.append(buildField(f)); });
        if (window.feather) feather.replace();
    }

    // Rebuild a single field's card in place (used after add/remove subfield).
    function renderField(f) {
        var $old = $host.find('[data-key="' + f._key + '"]');
        var $new = buildField(f);
        if ($old.length) $old.replaceWith($new); else $host.append($new);
        if (window.feather) feather.replace();
    }

    function buildField(f) {
        var $row    = $('<div>').addClass('row mb-3').attr('data-key', f._key);
        var $parent = $('<div>').addClass('col col-lg-12 subfield-parent');

        var $label = $('<label>').addClass('form-label in-b mr strong')
            .text(f.marctag.code + ' - ' + f.marctag.description);
        $parent.append($label);

        if (f.editable) {
            $parent.append(' ', $('<i>').addClass('sm-font danger-clr in-b mfe-remove-tag').attr({
                'data-bs-toggle': 'tooltip', 'title': 'Remove Tag', 'data-feather': 'minus-square'
            }));
        }

        // Indicators
        var $ind = $('<div>').addClass('block mb-2');
        $ind.append($('<label>').addClass('form-label in-b mr strong').text('Indicator'));
        $ind.append(buildIndicator(f, 0), buildIndicator(f, 1));
        if (f.editable) {
            $ind.append($('<i>').addClass('sm-font success-clr in-b mfe-add-sub').attr({
                'data-bs-toggle': 'tooltip', 'title': 'Add Subfield', 'data-feather': 'plus-square'
            }));
        }
        $parent.append($ind);

        // Subfields
        var $subs = $('<div>').addClass('additional-subfield');
        f.subfield.forEach(function (s, i) { $subs.append(buildSubfield(f, s, i === 0)); });
        $parent.append($subs);

        return $row.append($parent);
    }

    function buildIndicator(f, pos) {
        var $sel = $('<select>').addClass('form-select in-b width25 mr5 mfe-ind').attr('data-pos', pos);
        var cur = f.indicator[pos] || { id: null, indicator: 'None' };

        if (f.editable && f.options) {
            $sel.append($('<option>').val('').text('None'));
            (f.options.ind[pos] || []).forEach(function (ind) {
                if (ind && ind.id != null) {
                    var opt = $('<option>').val(ind.id).text(ind.indicator);
                    if (cur.id != null && cur.id == ind.id) opt.attr('selected', 'selected');
                    $sel.append(opt);
                }
            });
        } else {
            // Fixed: show the parsed/template indicator as the only option.
            $sel.append($('<option>').attr('selected', 'selected').val(cur.id == null ? '' : cur.id).text(cur.indicator));
        }
        return $sel;
    }

    function buildSubfield(f, s, isFirst) {
        var $r = $('<div>').addClass('subfield-row mb-1').attr('data-sub', s._key);

        if (f.editable) {
            var $rm = $('<i>').addClass('sm-font mr in-b ' + (isFirst ? 'dull-clr' : 'danger-clr mfe-remove-sub')).attr({
                'data-bs-toggle': 'tooltip', 'title': 'Remove Subfield', 'data-feather': 'minus-square'
            });
            $r.append($rm);

            var $sel = $('<select>').addClass('form-select in-b width25 mr5 mfe-subcode');
            $sel.append($('<option>').val('').attr('disabled', 'disabled').prop('selected', s.id == null).text('Select Subfield'));
            (f.options.subfields || []).forEach(function (sf) {
                var opt = $('<option>').val(sf.id).attr({ 'data-code': sf.code, 'data-desc': sf.description })
                    .text(sf.code + ' - ' + sf.description);
                if (s.id != null && s.id == sf.id) opt.attr('selected', 'selected');
                $sel.append(opt);
            });
            $r.append($sel);
        } else {
            // Fixed code: a static label, not a select.
            $r.append($('<span>').addClass('in-b mr5 badge bg-light text-dark')
                .css({ 'min-width': '70px', 'font-weight': 600 })
                .text(s.code || ''));
        }

        $r.append($('<input>').addClass('form-control in-b width25 mr5 mfe-value')
            .attr({ type: 'text', placeholder: 'Enter value' }).val(s.value || ''));
        return $r;
    }

    // ---- delegated events (bound once, survive re-render) ------------------
    function bind() {
        $host.on('input', '.mfe-value', function () {
            var f = field($(this).closest('[data-key]').data('key'));
            var s = subOf(f, $(this).closest('[data-sub]').data('sub'));
            if (s) s.value = $(this).val();
        });
        $host.on('change', '.mfe-subcode', function () {
            var opt = $(this).find('option:selected');
            var f = field($(this).closest('[data-key]').data('key'));
            var s = subOf(f, $(this).closest('[data-sub]').data('sub'));
            if (s) {
                s.id = parseInt($(this).val(), 10) || null;
                s.code = opt.data('code') || null;
                s.description = opt.data('desc') || null;
            }
        });
        $host.on('change', '.mfe-ind', function () {
            var f = field($(this).closest('[data-key]').data('key'));
            if (f) f.indicator[$(this).data('pos')] = {
                id: $(this).val() || null,
                indicator: $(this).find('option:selected').text()
            };
        });
        $host.on('click', '.mfe-remove-tag', function () {
            removeField($(this).closest('[data-key]').data('key'));
        });
        $host.on('click', '.mfe-add-sub', function () {
            addSubfield($(this).closest('[data-key]').data('key'));
        });
        $host.on('click', '.mfe-remove-sub', function () {
            removeSubfield($(this).closest('[data-key]').data('key'), $(this).closest('[data-sub]').data('sub'));
        });
    }

    // ---- serialize (on submit) --------------------------------------------
    // Emit the field_data shape storeRecord expects; strip internal _key and
    // recompute index / sub-index sequentially.
    function serialize() {
        return fields.map(function (f, i) {
            return {
                marctag: { index: i + 1, id: f.marctag.id, code: f.marctag.code, description: f.marctag.description },
                indicator: f.indicator.map(function (ind) { return { id: ind.id, indicator: ind.indicator }; }),
                subfield: f.subfield.map(function (s, j) {
                    return { 'sub-index': j + 1, id: s.id, code: s.code, description: s.description, value: s.value };
                })
            };
        });
    }
    function commit($input) { $input.val(JSON.stringify(serialize())); }

    function init(hostSelector) {
        $host = $(hostSelector);
        bind();
    }

    return {
        init: init,
        loadFromSource: loadFromSource,
        addTag: addTag,
        serialize: serialize,
        commit: commit,
        _fields: function () { return fields; }   // for tests/inspection
    };
})();


/* ---- Wiring -------------------------------------------------------------- */

$(document).ready(function () {
    MarcEditor.init('#template-div');

    // Serialize the in-memory state into #fieldData once, at submit time.
    $('#createRecordFrm').on('submit', function () {
        MarcEditor.commit($('#fieldData'));
    });
});

// MARC template selected -> load its fields.
$(document).on('change', '#marcTemplateSelect', function () {
    $.getJSON(getTemplateDetailsUrl, { template_id: $(this).val() })
        .done(function (res) { MarcEditor.loadFromSource(res.fields || []); })
        .fail(function () { console.log("Couldn't load the template."); });
});

function showAddFieldModal() {
    $('#viewAddFieldModal').modal('show');
}

// "ADD" in the Add Tag modal -> fetch the tag and append it as an editable field.
function addTag(id) {
    $('#viewAddFieldModal').modal('hide');
    $.getJSON(getMarcDetailsUrl, { marctag_id: id })
        .done(function (res) { MarcEditor.addTag(res); })
        .fail(function () { console.log("Couldn't load the tag details."); });
}
