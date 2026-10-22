/* =====================================================================
 * Shared MARC field editor for the Create Record, Edit Record AND the MARC Template
 * create/edit pages.
 *
 * The module (MarcEditor) is page-agnostic; the wiring at the bottom is
 * config-driven via two page globals:
 *   marcEditorMode    'create' | 'edit' | 'template'
 *                       create/edit -> LDR$05 record status (n new / c revised)
 *                       template    -> no LDR/008 panel, per-tag "Required" toggle, values
 *                                      are template defaults, serialized by serializeTemplate()
 *   marcInitialFields source-shape array  -> fields to pre-load (create: core bib tags;
 *                                            edit: the record being edited; template: its tags)
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
 *   source field  : {tag:{id,code,description,isRequired}, subfields:[{id,code,description,default}], indicators:[{id,indicator}],
 *                    editable?, allSubfields?:[{id,code,description}], allIndicators?:[[{id,indicator}],[...]]}
 *   add-tag detail: {id,code,description, subfields:[{id,code,description}], first_indictors:[{id,indicator}], second_indicators:[...]}
 *   field_data out: {marctag:{index,id,code,description}, indicator:[{id,indicator},{id,indicator}], subfield:[{'sub-index',id,code,description,value,align?}]}
 *                   align:'justify' is a display flag set by the editor's Justify button (OPAC +
 *                   view-record honour it; MARC export ignores it). Omitted when not justified.
 * ===================================================================== */

var MarcEditor = (function () {
    'use strict';

    var fields  = [];      // source of truth
    var keySeq  = 0;
    var subSeq  = 0;
    var $host;             // #template-div
    var _mode   = 'create';// 'create' | 'edit' | 'template' — see the header

    function setMode(m)   { _mode = (m === 'edit' || m === 'template') ? m : 'create'; }
    function isTemplate() { return _mode === 'template'; }
    function blankSub()   { return { _key: nextSubKey(), id: null, code: null, description: null, value: '' }; }
    // "245 - Title Statement"; just the code when the name is the code itself (a tag outside MARC 21).
    function codeAndName(code, name) { return (name == null || name === '' || String(name) === String(code)) ? String(code) : code + ' - ' + name; }
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

    // Canonical position lists, so building and *completing* a fixed field share one
    // source of truth. Record status ('n' new / 'c' corrected) is filled in per-mode.
    function leaderPositions(status, mat) {
        var t = marcTypeFor(mat);
        return [
            ['LDR$05', 'Record status', status],
            ['LDR$06', 'Type of record', t.type],
            ['LDR$07', 'Bibliographic level', t.bib],
            ['LDR$09', 'Character coding scheme', 'a'],   // Unicode (auto)
            ['LDR$17', 'Encoding level', ' '],
            ['LDR$18', 'Descriptive cataloging form', 'i']
        ];
    }
    function f008Positions(pubYear) {
        var y = String(pubYear || '').replace(/[^0-9]/g, '').slice(0, 4);
        return [
            ['008$00-05', 'Date entered on file', yymmdd()],   // auto
            ['008$06', 'Type of date/Publication status', 's'],
            ['008$07-10', 'Date 1', y || '    '],
            ['008$11-14', 'Date 2', '    '],
            ['008$15-17', 'Place of publication', 'ph '],
            ['008$35-37', 'Language', 'eng'],
            ['008$38', 'Modified record', ' '],                // auto
            ['008$39', 'Cataloging source', 'd']
        ];
    }

    function makeLeader(materialName, status) {
        return {
            _key: nextKey(), fixed: true,
            marctag: { id: 2501, code: 'LDR', description: 'Leader' },
            indicator: [{ id: null, indicator: 'None' }, { id: null, indicator: 'None' }],
            subfield: leaderPositions(status, materialName).map(function (p) { return sub(p[0], p[1], p[2]); })
        };
    }

    function make008(pubYear) {
        return {
            _key: nextKey(), fixed: true,
            marctag: { id: 6, code: '008', description: 'Fixed-Length Data Elements' },
            indicator: [{ id: null, indicator: 'None' }, { id: null, indicator: 'None' }],
            subfield: f008Positions(pubYear).map(function (p) { return sub(p[0], p[1], p[2]); })
        };
    }

    // Add any position the field is missing (a partial LDR/008 from an old/imported record),
    // WITHOUT overwriting values the record already has.
    function completeField(f, positions) {
        positions.forEach(function (p) {
            if (!subByCode(f, p[0])) f.subfield.push(sub(p[0], p[1], p[2]));
        });
    }

    // ---- ingest ------------------------------------------------------------
    // A MARC 21 indicator is exactly ONE character: a digit (0-9), a lowercase letter, or a
    // blank. Reduce any incoming form to that single, export-valid character:
    //   - reference label "<char> - <description>"  -> the leading <char>
    //   - LoC's blank conventions '#' and '\'        -> ' ' (an actual space, 0x20)
    //   - legacy placeholders 'None' / 'N' / empty   -> ' ' (blank)
    // The catalog's indicators are only '#' or 0-9 (no letters), so mapping a bare 'N'/'None'
    // to blank can't collide with a real indicator. This is the single source of truth used on
    // load, on change, and at serialize, so field_data never holds a description or placeholder.
    function marcIndicatorChar(raw) {
        var t = String(raw == null ? '' : raw).trim();
        if (t === '') return ' ';
        if (/[\s-]/.test(t)) {                                   // "<char> - <description>"
            var ch = t.split(/[\s-]+/)[0].charAt(0);
            return (ch === '#' || ch === '\\') ? ' ' : ch;
        }
        if (t.length === 1) {                                    // already a bare single char
            if (t === '#' || t === '\\') return ' ';
            return /[0-9a-z]/.test(t) ? t : ' ';                 // valid MARC char, else blank
        }
        return ' ';                                              // bare word ('None', ...) -> blank
    }
    // Human-facing label for a stored indicator char (blank shows as '#', LoC's convention).
    function indicatorLabel(ch) { return ch === ' ' ? '#' : ch; }

    // Normalise a field's two indicators to {id, indicator:<MARC char>}; blank by default.
    function normIndicators(indicators) {
        var out = [{ id: null, indicator: ' ' }, { id: null, indicator: ' ' }];
        (indicators || []).forEach(function (ind, i) {
            if (i > 1 || !ind) return;
            out[i] = { id: ind.id != null ? ind.id : null, indicator: marcIndicatorChar(ind.indicator) };
        });
        return out;
    }

    function fromSource(sf) {
        var code = sf.tag.code;
        // Pre-loaded default tags come with editable:true + allSubfields (they get add/
        // remove subfield + delete tag). Template/parsed fields stay fixed-structure.
        var editable = !!sf.editable;
        var subs = (sf.subfields || []).map(function (s) {
            return {
                _key: nextSubKey(),
                id: s.id, code: s.code, description: s.description,
                value: (s.default && s.default !== 'Enter value') ? s.default : '',
                align: s.align === 'justify' ? 'justify' : null
            };
        });
        if (editable && !subs.length) subs.push(blankSub());   // always one row to pick a code into
        return {
            _key: nextKey(),
            editable: editable,
            // Records: required tags (245, or ones a template marks required) can't be deleted.
            // Templates: 'required' is just the flag being edited, so 245 isn't forced there.
            required: !!(sf.tag && sf.tag.isRequired) || (!isTemplate() && code === '245'),
            fixed: (code === 'LDR' || code === '008'),        // -> guided fixed-field panel, not a generic card
            marctag: { id: sf.tag.id, code: code, description: sf.tag.description },
            indicator: normIndicators(sf.indicators),
            subfield: subs,
            options: editable ? { subfields: sf.allSubfields || [], ind: sf.allIndicators || [[], []] } : null
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
    // BUT never override a real selection already made: the Edit page server-preselects the
    // record's own material (a numeric id), which is authoritative. Some materials share the
    // single-char MARC type codes (a/c/t/...) as their class, so without this guard opening
    // Edit would silently switch the dropdown from the record's material to the type-code one.
    function autoSelectMaterial() {
        var cur = $('#materialTypeSelect').val();
        if (cur && /^\d+$/.test(cur)) return;   // a real material id is already selected -> keep it
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

    // ---- public: load a MARC template ---------------------------------------
    // Replaces the data fields with the template's, but KEEPS the current LDR/008: templates
    // never carry them, and the cataloger may already have set dates/country/language there.
    function loadTemplate(sourceFields) {
        var kept = fields.filter(function (f) { return f.fixed; });
        fields = kept.concat((sourceFields || []).map(fromSource));
        sortFields();
        render();
    }

    // True when any data field already holds a value (used to warn before replacing).
    function hasValues() {
        return fields.some(function (f) {
            return !f.fixed && f.subfield.some(function (s) { return String(s.value || '').trim() !== ''; });
        });
    }

    // ---- public: add one editable tag (Add Tag) ----------------------------
    function addTag(details) {
        fields.push({
            _key: nextKey(),
            editable: true,                                  // user can pick subfield codes, add/remove
            marctag: { id: details.id, code: details.code, description: details.description },
            indicator: [{ id: null, indicator: ' ' }, { id: null, indicator: ' ' }],   // blank by default
            subfield: [blankSub()],
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
        f.subfield.push(blankSub());
        renderField(f);   // re-render just this field's card
    }
    function removeSubfield(key, sk) {
        var f = field(key);
        if (!f) return;
        f.subfield = f.subfield.filter(function (s) { return s._key != sk; });
        if (!f.subfield.length) f.subfield.push(blankSub());
        renderField(f);
    }
    function removeField(key) {
        var f = field(key);
        if (f && f.required && !isTemplate()) return;   // required tags can't be removed from a record (icon is hidden too)
        fields = fields.filter(function (f) { return f._key != key; });
        $host.find('[data-key="' + key + '"]').remove();
    }

    // ---- fixed fields (LDR / 008) -----------------------------------------
    // Ensure a leader + 008 exist and are complete. Creates them if missing; if a record
    // has a partial LDR/008 (e.g. an import with only LDR$06), fills in the rest WITHOUT
    // clobbering existing values. Keeps the material-driven positions (type, bib level) in
    // sync with the Material Type dropdown.
    //
    // Record status (LDR$05): 'n' new on create, 'c' corrected/revised on edit — an
    // existing record being edited is, by definition, a revision. Only bumped from
    // new/blank -> corrected, so a deliberate status ('d' deleted, etc.) is preserved.
    function ensureFixed(materialName, pubYear) {
        var t = marcTypeFor(materialName);
        var newStatus = _mode === 'edit' ? 'c' : 'n';

        var ldr = fieldByCode('LDR');
        if (!ldr) {
            fields.push(makeLeader(materialName, newStatus));
        } else {
            completeField(ldr, leaderPositions(newStatus, materialName));
            if (materialName) {   // material drives type + bib level
                subByCode(ldr, 'LDR$06').value = t.type;
                subByCode(ldr, 'LDR$07').value = t.bib;
            }
            var st = subByCode(ldr, 'LDR$05');
            if (_mode === 'edit' && st && (st.value === 'n' || !String(st.value).trim())) st.value = 'c';
        }

        var f008 = fieldByCode('008');
        if (!f008) fields.push(make008(pubYear));
        else completeField(f008, f008Positions(pubYear));

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

    // Card layout: each line is split in two (.mfe-line) — the LEFT half holds the tag's
    // controls (indicators, subfield code), the RIGHT half holds the value.
    function buildField(f) {
        var $row    = $('<div>').addClass('row mb-3').attr('data-key', f._key);
        var $parent = $('<div>').addClass('col col-lg-12 subfield-parent');

        // Heading: "245 - Title Statement", remove icon, required badge / toggle. A tag outside
        // MARC 21 (942...) has its number as its name: just "942".
        var $head = $('<div>').addClass('mfe-head');
        $head.append($('<label>').addClass('form-label strong').text(codeAndName(f.marctag.code, f.marctag.description)));

        // Delete-tag control — but not for a required tag on a record (e.g. 245 Title Statement).
        if (f.editable && (!f.required || isTemplate())) {
            $head.append($('<i>').addClass('sm-font danger-clr mfe-remove-tag').attr({
                'data-bs-toggle': 'tooltip', 'title': 'Remove Tag', 'data-feather': 'minus-square'
            }));
        }
        if (isTemplate()) {
            // Template: toggle whether records made from it must keep this tag.
            var id = 'mfe-req-' + f._key;
            $head.append($('<span>').addClass('form-check form-check-inline ms-2').append(
                $('<input>').addClass('form-check-input mfe-required').attr({ type: 'checkbox', id: id }).prop('checked', !!f.required),
                $('<label>').addClass('form-check-label').attr('for', id).css('font-size', '11.5px').text('Required')
            ));
        } else if (f.required) {
            $head.append($('<span>').addClass('badge bg-light text-muted')
                .css({ 'font-size': '9.5px', 'font-weight': 600 }).text('required'));
        }
        $parent.append($head);

        // Indicators (left) | value column caption (right).
        var $ind = $('<div>').addClass('mfe-left mfe-ind-row');
        $ind.append($('<label>').text('Indicator'));
        $ind.append(buildIndicator(f, 0), buildIndicator(f, 1));
        if (f.editable) {
            $ind.append($('<i>').addClass('sm-font success-clr mfe-add-sub').attr({
                'data-bs-toggle': 'tooltip', 'title': 'Add Subfield', 'data-feather': 'plus-square'
            }));
        }
        $parent.append($('<div>').addClass('mfe-line').append(
            $ind, $('<div>').addClass('mfe-right mfe-col-label').text(isTemplate() ? 'Default value' : 'Value')
        ));

        // Subfields
        var $subs = $('<div>').addClass('additional-subfield');
        f.subfield.forEach(function (s, i) { $subs.append(buildSubfield(f, s, i === 0)); });
        $parent.append($subs);

        return $row.append($parent);
    }

    function buildIndicator(f, pos) {
        var $sel = $('<select>').addClass('form-select mfe-ind').attr('data-pos', pos);
        var cur = f.indicator[pos] || { id: null, indicator: ' ' };

        if (f.editable && f.options) {
            $sel.append($('<option>').val('').text('# (blank)'));   // blank -> stored as a space
            var found = false;
            (f.options.ind[pos] || []).forEach(function (ind) {
                if (ind && ind.id != null) {
                    var opt = $('<option>').val(ind.id).text(ind.indicator);
                    if (cur.id != null && cur.id == ind.id) { opt.attr('selected', 'selected'); found = true; }
                    $sel.append(opt);
                }
            });
            // A stored non-blank indicator that isn't in the tag's list (or a field loaded without
            // a list) is still shown, so the dropdown never misrepresents the field's value.
            if (!found && cur.indicator && cur.indicator !== ' ') {
                $sel.append($('<option>').attr('selected', 'selected').val(cur.id || '').text(indicatorLabel(cur.indicator)));
            }
        } else {
            // Fixed: show the parsed/template indicator as the only option (blank shows as '#').
            $sel.append($('<option>').attr('selected', 'selected').val(cur.id == null ? '' : cur.id).text(indicatorLabel(cur.indicator)));
        }
        return $sel;
    }

    function buildSubfield(f, s, isFirst) {
        var $r    = $('<div>').addClass('subfield-row mfe-line').attr('data-sub', s._key);
        var $left = $('<div>').addClass('mfe-left');

        if (f.editable) {
            $left.append($('<i>').addClass('sm-font ' + (isFirst ? 'dull-clr' : 'danger-clr mfe-remove-sub')).attr({
                'data-bs-toggle': 'tooltip', 'title': 'Remove Subfield', 'data-feather': 'minus-square'
            }));

            var $sel = $('<select>').addClass('form-select mfe-subcode');
            var $pick = $('<option>').val('').attr('disabled', 'disabled').text('Select Subfield');
            $sel.append($pick);
            var picked = false;
            (f.options.subfields || []).forEach(function (sf) {
                var opt = $('<option>').val(sf.id == null ? '' : sf.id).attr({ 'data-code': sf.code, 'data-desc': sf.description })
                    .text(codeAndName(sf.code, sf.description));
                // A local tag's subfields have no id (no MARC 21 row): they match by code.
                if (s.id != null ? s.id == sf.id : (sf.id == null && s.code != null && s.code === sf.code)) {
                    opt.attr('selected', 'selected');
                    picked = true;
                }
                $sel.append(opt);
            });
            $pick.prop('selected', !picked);
            $left.append($sel);
        } else {
            // Fixed code: a static label, not a select.
            $left.append($('<span>').addClass('badge bg-light text-dark mfe-code-badge').text(s.code || ''));
        }

        // RIGHT half: the value. It isn't typed into directly — clicking it (or Edit) opens the
        // plain-text editor in its place. Plain text keeps values markup-free for the OPAC,
        // search and MARC export; the editor's formatting tools only insert text (line breaks,
        // "• " bullets, "1. " numbers, leading spaces), which the OPAC shows as written.
        var $right = $('<div>').addClass('mfe-right');
        $right.append($('<div>').addClass('mfe-value').attr({
            tabindex: 0, role: 'button', title: 'Click to edit',
            'data-placeholder': isTemplate() ? '(no default value)' : '(empty)'
        }).toggleClass('mfe-justify', s.align === 'justify').text(s.value || ''));
        $right.append($('<div>').addClass('mfe-editor').append(buildToolbar(),
            $('<textarea>').addClass('form-control mfe-editor-ta').attr({ rows: 3, placeholder: 'Type the value…', spellcheck: 'true' })
        ));
        // Editor toggle button (becomes Save while the editor is open).
        $right.append($('<button>').attr({ type: 'button', title: 'Edit value' })
            .addClass('btn btn-sm btn-outline-secondary mfe-edit-btn')
            .html('<i data-feather="edit-2" class="icon-sm"></i>'));

        return $r.append($left, $right);
    }

    function buildToolbar() {
        var $bar = $('<div>').addClass('mfe-toolbar');
        [['bullets', 'mdi-format-list-bulleted', 'Bulleted list'],
         ['numbers', 'mdi-format-list-numbered', 'Numbered list'],
         null,
         ['outdent', 'mdi-format-indent-decrease', 'Decrease indent (Shift+Tab)'],
         ['indent', 'mdi-format-indent-increase', 'Increase indent (Tab)'],
         null,
         ['clear', 'mdi-format-clear', 'Clear formatting from the selected lines'],
         null,
         ['justify', 'mdi-format-align-justify', 'Justify (align both edges of this value)']
        ].forEach(function (t) {
            if (!t) { $bar.append($('<span>').addClass('mfe-tool-sep')); return; }
            $bar.append($('<button>').attr({ type: 'button', title: t[2], 'aria-label': t[2], 'data-tool': t[0] })
                .addClass('mfe-tool').html('<i class="mdi ' + t[1] + '"></i>'));
        });
        return $bar.append($('<span>').addClass('mfe-tool-hint').text('Ctrl+Enter save · Esc cancel'));
    }

    // ---- plain-text formatting ----------------------------------------------
    // Lists and indentation are plain characters in the value: "• " bullets, "1. " numbers and
    // 4-space indents per line. Each tool works on every line the selection touches.
    var INDENT  = '    ';
    var LIST_RE = /^([ \t]*)(?:(•)[ \t]+|(\d+)[.)][ \t]+)?(.*)$/;

    function parseLine(line) {
        var m = LIST_RE.exec(line);
        return { indent: m[1], bullet: !!m[2], num: m[3] ? parseInt(m[3], 10) : null, text: m[4] };
    }

    // True when every non-blank line already has the marker (so the tool toggles it off).
    function allMarked(lines, test) {
        var filled = lines.filter(function (l) { return l.trim(); });
        return filled.length > 0 && filled.every(function (l) { return test(parseLine(l)); });
    }

    var LINE_TOOLS = {
        bullets: function (lines) {
            var all = allMarked(lines, function (p) { return p.bullet; });
            return lines.map(function (l) {
                if (!l.trim() && lines.length > 1) return l;
                var p = parseLine(l);
                return p.indent + (all ? '' : '• ') + p.text;
            });
        },
        numbers: function (lines) {
            var all = allMarked(lines, function (p) { return p.num != null; }), n = 0;
            return lines.map(function (l) {
                if (!l.trim() && lines.length > 1) return l;
                var p = parseLine(l);
                return p.indent + (all ? '' : (++n) + '. ') + p.text;
            });
        },
        indent: function (lines) {
            return lines.map(function (l) { return (l.trim() || lines.length === 1) ? INDENT + l : l; });
        },
        outdent: function (lines) {
            return lines.map(function (l) { return l.replace(/^(\t| {1,4})/, ''); });
        },
        clear: function (lines) {
            return lines.map(function (l) { return parseLine(l).text; });
        }
    };

    // Replace [start,end) with text through the browser's editing command, so Ctrl+Z still undoes it.
    function replaceRange(ta, start, end, text) {
        ta.focus();
        ta.setSelectionRange(start, end);
        if (!(text && document.execCommand && document.execCommand('insertText', false, text))) {
            ta.setRangeText(text, start, end, 'end');
        }
    }

    function applyLineTool(ta, tool) {
        var v = ta.value, s = ta.selectionStart, e = ta.selectionEnd;
        if (e > s && v.charAt(e - 1) === '\n') e--;          // a selection ending at a line start excludes that line
        var ls = v.lastIndexOf('\n', s - 1) + 1;
        var le = v.indexOf('\n', e); if (le < 0) le = v.length;
        var out = LINE_TOOLS[tool](v.slice(ls, le).split('\n')).join('\n');
        replaceRange(ta, ls, le, out);
        if (out.indexOf('\n') >= 0) ta.setSelectionRange(ls, ls + out.length);   // keep the block selected
        else ta.setSelectionRange(ls + out.length, ls + out.length);
        autoGrow(ta);
    }

    // Enter on a list line continues the list. Enter on an empty item steps it out one indent
    // level, or ends the list when it's already at the margin.
    function continueList(ta, e) {
        var v = ta.value, s = ta.selectionStart;
        if (s !== ta.selectionEnd) return;
        var ls = v.lastIndexOf('\n', s - 1) + 1;
        var le = v.indexOf('\n', s); if (le < 0) le = v.length;
        var p = parseLine(v.slice(ls, le));
        if (!p.bullet && p.num == null) return;
        e.preventDefault();
        if (!p.text.trim()) {
            var marker = p.bullet ? '• ' : p.num + '. ';
            replaceRange(ta, ls, le, p.indent ? p.indent.replace(/^(\t| {1,4})/, '') + marker : '');
        }
        else { replaceRange(ta, s, s, '\n' + p.indent + (p.bullet ? '• ' : (p.num + 1) + '. ')); }
        autoGrow(ta);
    }

    function autoGrow(ta) {
        ta.style.height = 'auto';
        ta.style.height = Math.min(ta.scrollHeight + 2, 400) + 'px';
    }

    // ---- value editor (open / save / close) --------------------------------
    function openEditor($row) {
        if ($row.hasClass('mfe-editing')) return;
        var f = field($row.closest('[data-key]').data('key'));
        var s = subOf(f, $row.data('sub'));
        var $ta = $row.find('.mfe-editor-ta').val(s ? s.value || '' : '');
        setEditorJustify($row, !!(s && s.align === 'justify'));
        $row.addClass('mfe-editing');
        $row.find('.mfe-edit-btn').html('<i data-feather="check" class="icon-sm"></i>').attr('title', 'Save value (Ctrl+Enter)')
            .removeClass('btn-outline-secondary').addClass('btn-success');
        if (window.feather) feather.replace();
        var ta = $ta[0];
        ta.focus();
        ta.setSelectionRange(ta.value.length, ta.value.length);
        autoGrow(ta);
    }

    function saveEditor($row) {
        // Normalise line endings; drop leading blank lines and trailing whitespace.
        var v = $row.find('.mfe-editor-ta').val().replace(/\r\n?/g, '\n').replace(/^(?:[ \t]*\n)+/, '').replace(/\s+$/, '');
        var f = field($row.closest('[data-key]').data('key'));
        var s = subOf(f, $row.data('sub'));
        var justify = $row.find('.mfe-editor').hasClass('mfe-justify-on');
        if (s) { s.value = v; s.align = justify ? 'justify' : null; }
        $row.find('.mfe-value').text(v).toggleClass('mfe-justify', justify);
        closeEditor($row);
    }

    function closeEditor($row) {
        $row.removeClass('mfe-editing');
        $row.find('.mfe-edit-btn').html('<i data-feather="edit-2" class="icon-sm"></i>').attr('title', 'Edit value')
            .removeClass('btn-success').addClass('btn-outline-secondary');
        if (window.feather) feather.replace();
    }

    // Justify is a per-value display flag (not text): the editor holds it until Save / Esc.
    function setEditorJustify($row, on) {
        $row.find('.mfe-editor').toggleClass('mfe-justify-on', on);
        $row.find('.mfe-tool[data-tool=justify]').toggleClass('active', on).attr('aria-pressed', on ? 'true' : 'false');
    }

    // Save any editor still open (e.g. the form is submitted mid-edit) so no typing is lost.
    function flushEditors() {
        if ($host) $host.find('.mfe-editing').each(function () { saveEditor($(this)); });
    }

    // ---- delegated events (bound once, survive re-render) ------------------
    function bind() {
        $host.on('change', '.mfe-subcode', function () {
            var opt = $(this).find('option:selected');
            var f = field($(this).closest('[data-key]').data('key'));
            var s = subOf(f, $(this).closest('[data-sub]').data('sub'));
            if (s) {
                s.id = parseInt($(this).val(), 10) || null;
                s.code = opt.attr('data-code') || null;          // attr, not data(): no number conversion
                s.description = opt.attr('data-desc') || null;
            }
        });
        $host.on('change', '.mfe-ind', function () {
            var f = field($(this).closest('[data-key]').data('key'));
            // Store the single MARC indicator character (blank = space), never the label text,
            // so the value is consistent whether or not the cataloger touched the dropdown.
            if (f) f.indicator[$(this).data('pos')] = {
                id: $(this).val() || null,
                indicator: marcIndicatorChar($(this).find('option:selected').text())
            };
        });
        $host.on('change', '.mfe-required', function () {
            var f = field($(this).closest('[data-key]').data('key'));
            if (f) f.required = this.checked;
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

        // Value editor: clicking the value (or Edit) opens the plain-text editor in its place and
        // Edit turns into Save; Save writes the value back to state + the value view.
        $host.on('click', '.mfe-edit-btn', function () {
            var $row = $(this).closest('[data-sub]');
            $row.hasClass('mfe-editing') ? saveEditor($row) : openEditor($row);
        });
        $host.on('click', '.mfe-value', function () { openEditor($(this).closest('[data-sub]')); });
        $host.on('keydown', '.mfe-value', function (e) {
            if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); openEditor($(this).closest('[data-sub]')); }
        });

        // Formatting tools. mousedown is cancelled so the textarea keeps focus + selection.
        $host.on('mousedown', '.mfe-tool', function (e) { e.preventDefault(); });
        $host.on('click', '.mfe-tool', function () {
            var tool = $(this).data('tool'), $ed = $(this).closest('.mfe-editor');
            if (tool === 'justify') setEditorJustify($(this).closest('[data-sub]'), !$ed.hasClass('mfe-justify-on'));
            else applyLineTool($ed.find('.mfe-editor-ta')[0], tool);
        });
        $host.on('input', '.mfe-editor-ta', function () { autoGrow(this); });

        // Ctrl+Enter saves, Escape cancels (keeps the stored value), Tab / Shift+Tab indent,
        // Enter continues a bulleted / numbered list.
        $host.on('keydown', '.mfe-editor-ta', function (e) {
            var $row = $(this).closest('[data-sub]');
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) { e.preventDefault(); saveEditor($row); }
            else if (e.key === 'Escape') { e.preventDefault(); closeEditor($row); $row.find('.mfe-value').trigger('focus'); }
            else if (e.key === 'Tab' && !e.ctrlKey && !e.altKey && !e.metaKey) { e.preventDefault(); applyLineTool(this, e.shiftKey ? 'outdent' : 'indent'); }
            else if (e.key === 'Enter' && !e.shiftKey && !e.altKey) { continueList(this, e); }
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
                // Indicators are always emitted as a single MARC character (blank = space).
                indicator: f.indicator.map(function (ind) { return { id: ind.id, indicator: marcIndicatorChar(ind.indicator) }; }),
                subfield: f.subfield.map(function (s, j) {
                    var out = { 'sub-index': j + 1, id: s.id, code: s.code, description: s.description, value: s.value };
                    if (s.align === 'justify') out.align = 'justify';
                    return out;
                })
            };
        });
    }
    function commit($input) { flushEditors(); $input.val(JSON.stringify(serialize())); }

    // Template editor -> the marc_templates.fields shape (ConfigurationController sanitizes it):
    //   [{id:<tag id>, subfield:[<subfield id>], default:[<value>], justify:[0|1], indicator:[<ind id>|0, ...], required:0|1}]
    // Subfield rows with no code picked are dropped; LDR/008 never belong in a template.
    function serializeTemplate() {
        flushEditors();
        return fields.filter(function (f) { return !f.fixed; }).map(function (f) {
            var subs = f.subfield.filter(function (s) { return s.id != null; });
            return {
                id: f.marctag.id,
                subfield: subs.map(function (s) { return s.id; }),
                default: subs.map(function (s) { return s.value || ''; }),
                justify: subs.map(function (s) { return s.align === 'justify' ? 1 : 0; }),
                indicator: f.indicator.map(function (ind) { return parseInt(ind.id, 10) || 0; }),
                required: f.required ? 1 : 0
            };
        });
    }

    function init(hostSelector) {
        $host = $(hostSelector);
        bind();
    }

    return {
        init: init,
        setMode: setMode,
        loadFromSource: loadFromSource,
        loadTemplate: loadTemplate,
        hasValues: hasValues,
        addTag: addTag,
        ensureFixed: ensureFixed,
        removeField: removeField,
        serialize: serialize,
        commit: commit,
        serializeTemplate: serializeTemplate,
        count: function () { return fields.filter(function (f) { return !f.fixed; }).length; },
        _fields: function () { return fields; }   // for tests/inspection
    };
})();


/* ---- Wiring (shared by Create + Edit; config-driven) -------------------- */

$(document).ready(function () {
    // marcEditorMode / marcInitialFields are set per-page before this script loads.
    var mode = (typeof marcEditorMode !== 'undefined') ? marcEditorMode : 'create';
    MarcEditor.setMode(mode);   // 'edit' => LDR$05 record status becomes 'c' (revised)
    MarcEditor.init('#template-div');

    // Normal load (not a parse round-trip): pre-load the initial fields — create: the core
    // bibliographic tags; edit: the record being edited — and ensure/complete the LDR + 008.
    // single-import.js owns the importFlag == 1 (Parse a Record) case on both pages.
    if ($('#importFlag').val() != 1) {
        var initial = (typeof marcInitialFields !== 'undefined') ? marcInitialFields : [];
        if (initial && initial.length) MarcEditor.loadFromSource(initial);
        // Templates hold data fields only; LDR/008 are generated on the record pages.
        if (mode !== 'template') MarcEditor.ensureFixed(selectedMaterialName());   // LDR/06 = 'a' (books) by default
    }

    // Serialize the in-memory state into #fieldData once, at submit time (either form).
    $('#createRecordFrm, #updateRecordFrm').on('submit', function () {
        MarcEditor.commit($('#fieldData'));
    });

    // Template form: serialize to the template shape; refuse to save an empty template.
    $('#templateFrm').on('submit', function (e) {
        if (!MarcEditor.count()) {
            e.preventDefault();
            Swal.fire({ icon: 'warning', title: 'No tags yet', text: 'Add at least one MARC tag to the template.' });
            return;
        }
        $('#fieldIds').val(JSON.stringify(MarcEditor.serializeTemplate()));
    });

    $('#marcTemplateSelect').data('prev', $('#marcTemplateSelect').prop('selectedIndex'));
});

// MARC template selected -> load its fields (LDR/008 are kept), then ensure a leader + 008 exist.
// Replacing wipes what's been entered, so confirm first on Edit (always) and on Create (when
// values have been typed); cancelling puts the dropdown back.
$(document).on('change', '#marcTemplateSelect', function () {
    var $sel = $(this);
    var load = function () {
        $.getJSON(getTemplateDetailsUrl, { template_id: $sel.val() })
            .done(function (res) {
                MarcEditor.loadTemplate(res.fields || []);
                MarcEditor.ensureFixed(selectedMaterialName());
                $sel.data('prev', $sel.prop('selectedIndex'));
            })
            .fail(function () { console.log("Couldn't load the template."); });
    };

    var editing = (typeof marcEditorMode !== 'undefined' && marcEditorMode === 'edit');
    if (!editing && !MarcEditor.hasValues()) return load();

    Swal.fire({
        icon: 'warning',
        title: 'Replace the MARC fields?',
        html: 'Loading <strong>' + $('<div>').text($sel.find('option:selected').text()).html() + '</strong> replaces ' +
              (editing ? 'this record\'s' : 'the') + ' MARC fields and the values entered in them.<br>' +
              'The LDR / 008 fixed fields are kept.',
        showCancelButton: true,
        confirmButtonText: 'Replace fields',
        cancelButtonText: 'Cancel',
        reverseButtons: true
    }).then(function (result) {
        if (result.isConfirmed) load();
        else $sel.prop('selectedIndex', $sel.data('prev') || 0);
    });
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
