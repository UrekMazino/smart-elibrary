/* Parse a Record (single MARC import) for the Create Record page.
 *
 * The parse is a server round-trip: uploading a MARC file re-renders this page with
 * #fieldData pre-filled with the parsed record in SOURCE shape ({fields:[...]}) and
 * #importFlag = 1. Here we hand those fields to the shared MarcEditor, which owns all
 * rendering and state — the same module the template loader and Add Tag use. (Previously
 * this file was a ~300-line second copy of the renderer with duplicate ids and
 * per-keystroke JSON rebuilds.)
 */
$(document).ready(function () {
    if ($('#importFlag').val() != 1) return;

    var parsed;
    try { parsed = JSON.parse($('#fieldData').val() || '{}'); } catch (e) { parsed = {}; }
    if (!parsed || !parsed.fields || !parsed.fields.length) return;

    // A parsed record supplies its own identifiers; the accession number isn't required here.
    $('#accessionNumber').removeAttr('required');

    MarcEditor.loadFromSource(parsed.fields);
});

function showImportModal(action) {
    if (action === 'edit') $('#import-action').val('edit');
    $('#showImportModal').modal('show');
    $('#showImportModal').fadeIn();
}
