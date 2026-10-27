<div class="row">          
      <div class="col-md-6 col-sm-12 offset-md-3">
          <div class="modal fade" id="editPermissionModal" tabindex="-1" aria-labelledby="editPermissionModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editPermissionModalTitle">Edit Permission/s</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.update_permission') }}" method="POST">
                            @csrf
                            <div class="container">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="editNameInput" class="form-label">Permission Name</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="editNameInput"  class="form-control" name="permission_name" placeholder="Enter new permission name..." readonly>
                                                <input type="hidden" id="permissionIdInput" name="permission_id"> 
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="editDisplayNameInput" class="form-label">Display Name</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="editDisplayNameInput"  class="form-control" name="display_name" placeholder="Enter display name...">
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="editDescriptionInput" class="form-label">Description</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="editDescriptionInput"  class="form-control" name="description" placeholder="Enter description name...">
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-3 text-center">
                                                <button type="submit" class="btn btn-primary me-2">Save</button>
                                            </div>
                                        </div>
                                    </div>
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