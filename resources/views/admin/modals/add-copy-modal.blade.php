<div class="row">          
      <div class="col-md-6 col-sm-12 offset-md-3">
          <div class="modal fade" id="addCopydModal" tabindex="-1" aria-labelledby="addCopydModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addCopydModalTitle">Add Copy/ies</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ url('/admin/view-record/add-copy') }}" method="POST">
                            @csrf
                            <input type="hidden" value="{{ $record->id }}" name="catalog_id">
                            <input type="hidden" value="{{ $title }}" name="title">
                            <div class="row">
                                <div class="col-md-3 col-sm-12 mb-3 text-center">
                                    <label for="inputCirculationNumber" class="form-label">CIRCULATION NO.</label>
                                </div>
                                <div class="col-md-2 col-sm-12 mb-3 text-center">
                                    <label for="inputStatus" class="form-label">STATUS</label>
                                </div>
                                <div class="col-md-3 col-sm-12 mb-3 text-center">
                                    <label for="inputSource" class="form-label">SOURCE</label>
                                </div>
                                <div class="col-md-3 col-sm-12 mb-3 text-center">
                                    <label for="inputLocation" class="form-label">LOCATION</label>
                                </div>
                                <div class="col-md-1 col-sm-12 mb-3 text-center">
                                    <label class="form-label">ACTION</label>
                                </div>

                            </div>
                            <div class="row" id="first-row">
                                <div class="col-md-3 col-sm-12 mb-3 text-center">
                                    <input type="text" class="form-control" name="circulation_number[]" id="inputCirculationNumber" autocomplete="off" placeholder="Circulation No.">
                                </div>
                                <div class="col-md-2 col-sm-12 mb-3 text-center">
                                    <select class="form-select" name="status[]" id="inputStatus" required>
                                        <option value="">Status</option>
                                        @foreach(getCopiesStatus() as $status) 
                                            <option value="{{ $status->order_no }}">{{ $status->name }}</option>
                                        @endforeach
                     
                                    </select>
                                </div>
                                <div class="col-md-3 col-sm-12 mb-3 text-center">
                                    <input type="text" class="form-control" name="source[]" id="inputSource" autocomplete="off" placeholder="Source">
                                </div>
                                <div class="col-md-3 col-sm-12 mb-3 text-center">
                                    <input type="text" class="form-control" name="location[]" id="inputLocation" autocomplete="off" placeholder="Location">
                                </div>
                                 <div class="col-md-1 col-sm-12 mb-3 text-center">
                                    <i class="delete-row delete-icon" data-feather="trash-2"></i>
                                </div>

                            </div>
                               
                            <div id="dynamic-rows-container"></div>
                            <div class="row">
                                <div class="col-md-12 col-sm-12 mb-3 text-center">
                                    <button type="submit" class="btn btn-primary me-2">Save</button>
                                    <button type="button" class="btn btn-secondary" id="addRowButton" style="color: white;">Add Row</button>
                                </div>
                            </div>
                        </form>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
              </div>
        </div>
      </div>
    </div>