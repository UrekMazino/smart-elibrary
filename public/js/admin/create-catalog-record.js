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
    function subByCode(f, c){ return f ? f.subfield.find(function (s) { return s.code === c; }) : null; }
    function fieldByCode(c) { return fields.find(function (f) { return f.marctag.code === c; }); }

    /* ---- MARC 21 fixed fields (LDR / 008) ------------------------------------
     * Some byte positions are system-generated, others must be set by the cataloger.
     * We AUTO-fill the structural/system positions and expose the manual ones through a
     * guided panel. Enumerated positions use short MARC value lists (dropdowns); the big
     * coded lists (country/language) are free-text with a hint. Each position is stored as
     * its own subfield (LDR$06, 008$15-17, ...) — the shape marctag_subfields_loc and
     * storeRecord already use (storeRecord reads LDR$06 for the record type).
     */
    var LDR06 = { a: 'Language material', c: 'Notated music', d: 'Manuscript notated music',
        e: 'Cartographic material', f: 'Manuscript cartographic', g: 'Projected medium',
        i: 'Nonmusical sound recording', j: 'Musical sound recording',
        k: 'Two-dimensional nonprojectable graphic', m: 'Computer file', o: 'Kit',
        p: 'Mixed materials', r: 'Three-dimensional artifact', t: 'Manuscript language material' };
    var LDR07 = { a: 'Monographic component part', b: 'Serial component part', c: 'Collection',
        d: 'Subunit', i: 'Integrating resource', m: 'Monograph / Item', s: 'Serial' };
    var LDR17 = { ' ': 'Full level', '1': 'Full, not examined', '2': 'Less-than-full',
        '3': 'Abbreviated', '4': 'Core', '5': 'Preliminary', '7': 'Minimal',
        '8': 'Prepublication', 'u': 'Unknown', 'z': 'Not applicable' };
    var LDR18 = { i: 'ISBD', a: 'AACR 2', c: 'ISBD (punctuation omitted)',
        n: 'Non-ISBD (punctuation omitted)', ' ': 'Non-ISBD', u: 'Unknown' };
    var DATE_TYPE = { s: 'Single known date', m: 'Multiple dates', r: 'Reprint / reissue',
        t: 'Publication + copyright', c: 'Continuing, currently published',
        d: 'Continuing, ceased', e: 'Detailed date', i: 'Inclusive dates',
        k: 'Range of collection years', n: 'Dates unknown', q: 'Questionable date',
        u: 'Continuing, status unknown' };
    var CAT_SRC = { ' ': 'National bibliographic agency', c: 'Cooperative program',
        d: 'Other', u: 'Unknown' };

    // panel layout: {code, label, auto?, type:'select'|'text', options?, size?, hint?}
    var LDR_POS = [
        { code: 'LDR$05', label: 'Record status', auto: true },
        { code: 'LDR$06', label: 'Type of record', type: 'select', options: LDR06 },
        { code: 'LDR$07', label: 'Bibliographic level', type: 'select', options: LDR07 },
        { code: 'LDR$17', label: 'Encoding level', type: 'select', options: LDR17 },
        { code: 'LDR$18', label: 'Descriptive cataloging form', type: 'select', options: LDR18 }
    ];
    var F008_POS = [
        { code: '008$00-05', label: 'Date entered', auto: true },
        { code: '008$06', label: 'Type of date', type: 'select', options: DATE_TYPE },
        { code: '008$07-10', label: 'Date 1', type: 'text', size: 4, hint: 'e.g. 2021' },
        { code: '008$11-14', label: 'Date 2', type: 'text', size: 4, hint: 'blank if single date' },
        { code: '008$15-17', label: 'Country', type: 'text', size: 3, hint: 'MARC code, e.g. ph' },
        { code: '008$35-37', label: 'Language', type: 'text', size: 3, hint: 'MARC code, e.g. eng' },
        { code: '008$39', label: 'Cataloging source', type: 'select', options: CAT_SRC }
    ];

    // A material's Type-of-record + bibliographic level. Defaults suit the PCAARRD corpus
    // (mostly textual monographs); serials get bib level 's'.
    function marcTypeFor(materialName) {
        var n = String(materialName || '').toLowerCase();
        if (n.indexOf('serial') >= 0 || n.indexOf('continuing') >= 0) return { type: 'a', bib: 's' };
        return { type: 'a', bib: 'm' };
    }

    function yymmdd() {
        var d = new Date();
        return String(d.getFullYear()).slice(2) + ('0' + (d.getMonth() + 1)).slice(-2) + ('0' + d.getDate()).slice(-2);
    }

    function sub(code, description, value) {
        return { _key: nextSubKey(), id: null, code: code, description: description, value: value };
    }

    function makeLeader(materialName) {
        var t = marcTypeFor(materialName);
        return {
            _key: nextKey(), fixed: true,
            marctag: { id: 2501, code: 'LDR', description: 'Leader' },
            indicator: [{ id: null, indicator: 'None' }, { id: null, indicator: 'None' }],
            subfield: [
                sub('LDR$05', 'Record status', 'n'),               // new record (auto)
                sub('LDR$06', 'Type of record', t.type),
                sub('LDR$07', 'Bibliographic level', t.bib),
                sub('LDR$09', 'Character coding scheme', 'a'),      // Unicode (auto)
                sub('LDR$17', 'Encoding level', ' '),
                sub('LDR$18', 'Descriptive cataloging form', 'i')
            ]
        };
    }

    function make008(pubYear) {
        var y = String(pubYear || '').replace(/[^0-9]/g, '').slice(0, 4);
        return {
            _key: nextKey(), fixed: true,
            marctag: { id: 6, code: '008', description: 'Fixed-Length Data Elements' },
            indicator: [{ id: null, indicator: 'None' }, { id: null, indicator: 'None' }],
            subfield: [
                sub('008$00-05', 'Date entered on file', yymmdd()),  // auto
                sub('008$06', 'Type of date/Publication status', 's'),
                sub('008$07-10', 'Date 1', y || '    '),
                sub('008$11-14', 'Date 2', '    '),
                sub('008$15-17', 'Place of publication', 'ph '),
                sub('008$35-37', 'Language', 'eng'),
                sub('008$38', 'Modified record', ' '),              // auto
                sub('008$39', 'Cataloging source', 'd')
            ]
        };
    }

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
        var code = sf.tag.code;
        // Pre-loaded default tags come with editable:true + allSubfields (they get add/
        // remove subfield + delete tag). Template/parsed fields stay fixed-structure.
        var editable = !!sf.editable;
        return {
            _key: nextKey(),
            editable: editable,
            required: !!(sf.tag && sf.tag.isRequired),        // required tags (245) can't be deleted
            fixed: (code === 'LDR' || code === '008'),        // -> guided fixed-field panel, not a generic card
            marctag: { id: sf.tag.id, code: code, description: sf.tag.description },
            indicator: normIndicators(sf.indicators),
            subfield: (sf.subfields || []).map(function (s) {
                return {
                    _key: nextSubKey(),
                    id: s.id, code: s.code, description: s.description,
                    value: (s.default && s.default !== 'Enter value') ? s.default : ''
                };
            }),
            options: editable ? { subfields: sf.allSubfields || [], ind: [[], []] } : null
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
        var f = field(key);
        if (f && f.required) return;   // required tags can't be removed (defensive; icon is hidden too)
        fields = fields.filter(function (f) { return f._key != key; });
        $host.find('[data-key="' + key + '"]').remove();
    }

    // ---- fixed fields (LDR / 008) -----------------------------------------
    // Ensure a leader + 008 exist. Called on template load / material select; creates them
    // once, and keeps the material-driven positions (type, bib level) in sync with the
    // Material Type dropdown without clobbering the cataloger's other edits.
    function ensureFixed(materialName, pubYear) {
        var t = marcTypeFor(materialName);

        var ldr = fieldByCode('LDR');
        if (!ldr) { ldr = makeLeader(materialName); fields.push(ldr); }
        else if (materialName) {
            var s6 = subByCode(ldr, 'LDR$06'); if (s6) s6.value = t.type;
            var s7 = subByCode(ldr, 'LDR$07'); if (s7) s7.value = t.bib;
        }

        var f008 = fieldByCode('008');
        if (!f008) { f008 = make008(pubYear); fields.push(f008); }

        sortFields();
        render();
    }

    // ---- render ------------------------------------------------------------
    function render() {
        $host.empty();
        // Fixed fields (LDR/008) render in their own guided panel, not as generic cards.
        fields.forEach(function (f) { if (!f.fixed) $host.append(buildField(f)); });
        renderFixed();
        if (window.feather) feather.replace();
    }

    function renderFixed() {
        var $body = $('#fixed-fields-body');
        if (!$body.length) return;
        $body.empty();

        var ldr = fieldByCode('LDR');
        var f008 = fieldByCode('008');
        $('#fixed-fields-card').toggle(!!(ldr || f008));

        if (ldr)  $body.append(buildFixedGroup('LDR (Leader)', ldr, LDR_POS));
        if (f008) $body.append(buildFixedGroup('008 (Fixed-Length Data Elements)', f008, F008_POS));
    }

    function buildFixedGroup(title, f, positions) {
        var $wrap = $('<div>').addClass('ff-group').attr('data-key', f._key);
        $wrap.append($('<div>').addClass('ff-group-title').text(title));
        var $grid = $('<div>').addClass('ff-grid');

        positions.forEach(function (p) {
            var s = subByCode(f, p.code);
            var val = s ? s.value : '';
            var $cell = $('<div>').addClass('ff-cell');
            $cell.append($('<label>').addClass('ff-label').text(p.label));

            if (p.auto) {
                $cell.append($('<div>').addClass('ff-auto').text((val && val.trim()) ? val : '—'));
                $cell.append($('<span>').addClass('ff-hint').text('auto'));
            } else if (p.type === 'select') {
                var $sel = $('<select>').addClass('form-select form-select-sm mfe-fixed').attr('data-code', p.code);
                Object.keys(p.options).forEach(function (k) {
                    var opt = $('<option>').val(k).text((k === ' ' ? '(blank)' : k) + ' — ' + p.options[k]);
                    if (val === k) opt.attr('selected', 'selected');
                    $sel.append(opt);
                });
                $cell.append($sel);
            } else {
                $cell.append($('<input>').addClass('form-control form-control-sm mfe-fixed')
                    .attr({ type: 'text', maxlength: p.size || 8, 'data-code': p.code }).val(val));
                if (p.hint) $cell.append($('<span>').addClass('ff-hint').text(p.hint));
            }
            $grid.append($cell);
        });

        return $wrap.append($grid);
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

        // Delete-tag control — but not for a required tag (e.g. 245 Title Statement).
        if (f.editable && !f.required) {
            $parent.append(' ', $('<i>').addClass('sm-font danger-clr in-b mfe-remove-tag').attr({
                'data-bs-toggle': 'tooltip', 'title': 'Remove Tag', 'data-feather': 'minus-square'
            }));
        }
        if (f.required) {
            $parent.append(' ', $('<span>').addClass('badge bg-light text-muted')
                .css({ 'font-size': '9.5px', 'font-weight': 600 }).text('required'));
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

        // Fixed-field panel inputs (LDR/008) — outside #host, update by field key + code.
        $(document).on('change input', '.mfe-fixed', function () {
            var f = field($(this).closest('[data-key]').data('key'));
            if (!f) return;
            var s = subByCode(f, $(this).data('code'));
            if (s) s.value = $(this).val();
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
        ensureFixed: ensureFixed,
        removeField: removeField,
        serialize: serialize,
        commit: commit,
        _fields: function () { return fields; }   // for tests/inspection
    };
})();


/* ---- Wiring -------------------------------------------------------------- */

$(document).ready(function () {
    MarcEditor.init('#template-div');

    // Fresh form (not a parse, not a validation repopulate): pre-load the core
    // bibliographic tags and generate the LDR + 008 so the cataloguer starts with a
    // ready-to-fill record. single-import.js owns the importFlag == 1 (parse) case.
    if ($('#importFlag').val() != 1 && typeof defaultMarcFields !== 'undefined' && defaultMarcFields.length) {
        MarcEditor.loadFromSource(defaultMarcFields);
        MarcEditor.ensureFixed(selectedMaterialName());   // LDR/06 = 'a' (books) by default
    }

    // Serialize the in-memory state into #fieldData once, at submit time.
    $('#createRecordFrm').on('submit', function () {
        MarcEditor.commit($('#fieldData'));
    });
});

// MARC template selected -> load its fields, then ensure a leader + 008 exist.
$(document).on('change', '#marcTemplateSelect', function () {
    $.getJSON(getTemplateDetailsUrl, { template_id: $(this).val() })
        .done(function (res) {
            MarcEditor.loadFromSource(res.fields || []);
            MarcEditor.ensureFixed(selectedMaterialName());
        })
        .fail(function () { console.log("Couldn't load the template."); });
});

// Material Type selected -> generate / sync the fixed fields (type of record, bib level).
$(document).on('change', '#materialTypeSelect', function () {
    MarcEditor.ensureFixed(selectedMaterialName());
});

function selectedMaterialName() {
    return $('#materialTypeSelect option:selected').text().trim();
}

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
