<div class="row">          
      <div class="col-md-6 col-sm-12 offset-md-3">
          <div class="modal fade" id="addRoledModal" tabindex="-1" aria-labelledby="addRoledModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addRoledModalTitle">Add Role/s</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.store_role') }}" method="POST">
                            @csrf
                            <div class="container">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="roleName" class="form-label">Role Name</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="roleName"  class="form-control" name="role_name" placeholder="Enter new role name...">
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="roleDisplayName" class="form-label">Display Name</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="roleDisplayName"  class="form-control" name="display_name" placeholder="Enter display name...">
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="roleDescription" class="form-label">Description</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="roleDescription"  class="form-control" name="description" placeholder="Enter description name...">
                                            </div>

                                        </div>

                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-3 text-center">
                                                <button type="submit" class="btn btn-info me-2" style="color: white;">Save</button>
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