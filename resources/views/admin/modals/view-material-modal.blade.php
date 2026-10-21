<div class="row">          
      <div class="col-md-6 col-sm-12 offset-md-3">
          <div class="modal fade" id="viewMaterialModal" tabindex="-1" aria-labelledby="viewMaterialModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="viewMaterialModalTitle">Material View</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-sm-12 mb-3 mb-3">
                                <label for="material-circulation-numbers" class="form-label">Circulation Number <sup><span style="color:red; font-size:14px;">*</span></sup></label>
                                <select class="form-select" id="material-circulation-numbers">
                                    <option selected disabled>Select a circulation number</option>
                                </select>
                                <input type="text" id="material-circulation-number" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 mb-3">
                                <label for="material-title" class="form-label">Title</label>
                                <input type="text" id="material-title" class="form-control" placeholder="Title" disabled>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="material-catalog-id" class="form-label">Catalog ID</label>
                                <input type="text" id="material-catalog-id" class="form-control" placeholder="Catalog ID" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="material-status" class="form-label">Status</label>
                                <input type="text" id="material-status" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-6 mb-3">
                                <label for="material-cost" class="form-label">Cost</label>
                                <input type="number" id="material-cost" class="form-control" disabled>
                            </div>
                            <div class="col-sm-6 mb-3">
                                <label for="material-acquisition-mode" class="form-label">Aquisition type</label>
                                <input type="text" id="material-acquisition-mode" class="form-control" disabled>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-sm-12 mb-3">
                                <label for="material-source" class="form-label">Source</label>
                                <input type="text" id="material-source" class="form-control" placeholder="www" disabled>
                            </div>
                        </div>


                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
              </div>
        </div>
      </div>
    </div>