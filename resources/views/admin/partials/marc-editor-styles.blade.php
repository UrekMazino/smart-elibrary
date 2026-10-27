{{-- MARC field cards rendered by the shared MarcEditor (public/js/admin/marc-record-editor.js).
     Included by Create Record, Edit Record and the MARC Template form so all three look the same.
     Each card is split in two: the LEFT half holds the tag controls (indicators, subfield codes),
     the RIGHT half holds the values. --}}
<style>
    #template-div > .row {
        border: 1px solid var(--ui-border, #e4eff1); border-radius: 6px; background: var(--ui-surface-2, #e4eff1);
        padding: 6px 10px; margin: 0 0 6px !important;
    }
    #template-div:empty ~ .cr-empty { display: block; }   /* "No fields yet" (pages that have one) */
    #template-div .additional-subfield { margin-bottom: 0 !important; }
    #template-div .sm-font { width: 15px !important; height: 15px !important; flex: 0 0 auto; }
    #template-div .danger-clr, #template-div .success-clr { cursor: pointer; }
    #template-div .form-control,
    #template-div .form-select { font-size: 0.75rem; padding: 2px 8px; height: 28px; line-height: 1.4; }
    #template-div .form-select { padding-right: 26px; background-position: right 6px center; }

    /* Tag heading: code - name, remove icon, required badge / toggle. */
    #template-div .mfe-head { display: flex; align-items: center; flex-wrap: wrap; gap: 6px; margin-bottom: 4px; }
    #template-div .mfe-head label.strong { font-size: 0.75rem; color: var(--ui-text, #22283b); font-weight: 700; margin: 0; }
    #template-div .mfe-head .form-check { margin: 0; min-height: 0; }

    /* Two-column line: controls | value. */
    #template-div .mfe-line { display: flex; align-items: flex-start; gap: 12px; margin-bottom: 4px; }
    #template-div .mfe-left { flex: 0 0 calc(50% - 6px); display: flex; align-items: center; gap: 6px; min-width: 0; min-height: 28px; }
    #template-div .mfe-right { flex: 1 1 0; display: flex; align-items: flex-start; gap: 4px; min-width: 0; }
    #template-div .mfe-left .form-select { flex: 1 1 0; min-width: 0; width: auto; }
    #template-div .mfe-ind-row > label { font-size: 0.6875rem; font-weight: 700; color: var(--ui-text, #22283b); margin: 0; flex: 0 0 auto; }
    #template-div .mfe-code-badge { min-width: 70px; font-weight: 600; }
    #template-div .mfe-col-label {
        font-size: 0.65625rem; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1);
        font-weight: 700; align-self: flex-end; padding-left: 2px;
    }

    /* Value: a read-only view that keeps line breaks / indentation; click or Edit to change it. */
    #template-div .mfe-value {
        flex: 1 1 0; min-width: 0; min-height: 28px; max-height: 168px; overflow: auto;
        background: var(--ui-surface-2, #f4f7fa); border: 1px solid var(--ui-border, #d9e2ea); border-radius: 4px;
        padding: 4px 8px; font-size: 0.75rem; line-height: 1.45; color: var(--ui-text, #33415c);
        white-space: pre-wrap; overflow-wrap: anywhere; cursor: text;
    }
    #template-div .mfe-value:hover { border-color: var(--ui-border, #b8c6d3); background: var(--ui-surface, #fff); }
    #template-div .mfe-value:focus { outline: 2px solid #9bb5ff; outline-offset: 0; }
    #template-div .mfe-value.mfe-justify,
    #template-div .mfe-justify-on textarea.mfe-editor-ta { text-align: justify; }
    #template-div .mfe-value:empty::before { content: attr(data-placeholder); color: var(--ui-muted-2, #a3adc2); }
    #template-div .mfe-edit-btn { height: 28px; padding: 1px 7px; line-height: 1; flex: 0 0 auto; }

    /* Inline plain-text editor, opened in place of the value view. */
    #template-div .mfe-editor { display: none; flex: 1 1 0; min-width: 0; }
    #template-div .mfe-editing .mfe-value { display: none; }
    #template-div .mfe-editing .mfe-editor { display: block; }
    #template-div .mfe-toolbar { display: flex; align-items: center; flex-wrap: wrap; gap: 2px; margin-bottom: 3px; }
    #template-div .mfe-tool {
        height: 24px; min-width: 26px; padding: 0 5px; font-size: 0.9375rem; line-height: 22px;
        border: 1px solid var(--ui-border, #d9e2ea); background: var(--ui-surface, #fff); color: var(--ui-text-2, #4a5a78); border-radius: 4px;
    }
    #template-div .mfe-tool:hover { background: var(--ui-navy-soft, #eef2ff); border-color: #9bb5ff; color: var(--ui-navy, #33509c); }
    #template-div .mfe-tool.active { background: color-mix(in srgb, var(--t-primary) 18%, var(--ui-surface, #fff)); border-color: var(--t-primary); color: color-mix(in srgb, var(--t-primary), var(--ui-text, #000) 30%); }
    #template-div .mfe-tool-sep { width: 1px; height: 16px; background: var(--ui-surface-2, #d9e2ea); margin: 0 3px; }
    #template-div .mfe-tool-hint { margin-left: auto; font-size: 0.65625rem; color: var(--ui-muted, #8a94a6); }
    #template-div textarea.mfe-editor-ta {
        height: auto; min-height: 56px; max-height: 400px; resize: vertical;
        font-size: 0.75rem; line-height: 1.45; padding: 4px 8px; tab-size: 4;
    }

    /* Narrow screens: stack the value under its controls. */
    @media (max-width: 767.98px) {
        #template-div .mfe-line { flex-direction: column; gap: 4px; }
        #template-div .mfe-left { flex-basis: auto; width: 100%; }
        #template-div .mfe-right { width: 100%; }
        #template-div .mfe-col-label { display: none; }
    }
</style>
