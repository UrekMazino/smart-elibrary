<div class="row">          
    <div class="col-md-6 col-sm-12 offset-md-3">
        <div class="modal fade" id="showImportModal" tabindex="-1" aria-labelledby="showImportModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="showImportModalTitle">Upload File for Parsing</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                        <form method="post" action="{{ url('/admin/import/single-marcfile') }}" id="marcFileFrm" enctype="multipart/form-data">
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-md-12">
                                        <div class="card">
                                            <div class="card-body">
                                                @csrf
                                                <div class="mb-3">
                                                    <label class="form-label" for="formFile">File upload</label>
                                                    <input class="form-control" type="file" id="marcFile" name="marcFile">
                                                    <input type="hidden" name="action" value="create" id="import-action">
                                                    <input type="hidden" name="record_id"  value="{{  isset($record) ? $record->id : ''}}" >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="submit" class="btn btn-primary" id="parseButton">Parse</button>
                            </div>
                        </form>
                </div>
            </div>
    </div>
    </div>
</div>