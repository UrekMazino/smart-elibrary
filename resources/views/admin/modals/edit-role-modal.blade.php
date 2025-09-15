<div class="row">          
      <div class="col-md-6 col-sm-12 offset-md-3">
          <div class="modal fade" id="editRoledModal" tabindex="-1" aria-labelledby="editRoledModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered modal-xl">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editRoledModalTitle">Edit Role</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.update_role') }}" method="POST">
                            @csrf
                            <div class="container">
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <label for="roleNameInput" class="form-label">Role Name</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 mb-3 text-center">
                                                <input type="text" id="roleNameInput"  class="form-control" name="role_name" placeholder="Enter new role name..." readonly>
                                                <input type="hidden" id="roleIdInput" name="role_id"> 
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
                                            <div class="col-md-3 col-sm-12">
                                                <label for="editDescriptionInput" class="form-label">Description</label>
                                            </div>
                                            <div class="col-md-9 col-sm-12 text-center">
                                                <input type="text" id="editDescriptionInput"  class="form-control" name="description" placeholder="Enter description name...">
                                            </div>

                                        </div>
                                    </div>
                                </div>
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-12 mb-1">
                                                <label class="form-label">Permissions</label>
                                            </div>
                                            <div class="col-md-6 col-sm-12 mb-3 text-right">
                                                <select class="form-select" id="permission-list">
                                                    <option value="0" selected >Select a permission to add..</option>
                                                    @foreach(getPermissions() as $permission)
                                                        <option value="{{ $permission->id }}">{{ $permission->display_name}}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            
                                            <div class="col-md-3 col-sm-12 mb-3">
                                                <button type="button" class="btn btn-primary me-2" style="color: white;" onclick="addPermission()">+ Permission</button>
                                            </div>
                                            <div class="col-md-12 col-sm-12 mb-3 text-center">
                                                <div class="table-responsive">
                                                    <table id="permissionTbl" class="table">
                                                        <thead>
                                                            <tr>
                                                                <th style="width: 10%">ID</th>
                                                                <th style="width: 20%">Name</th>
                                                                <th style="width: 20%">Display Name</th>
                                                                <th style="width: 40%">Description</th>
                                                                <th style="width: 10%"></th>
                                                            </tr>
                                                        </thead>
                                                        <tbody></tbody>
                                                    </table>
                                                </div>
                                            </div>

                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-3 text-center">
                                                <button type="submit" class="btn btn-info me-2" style="color: white;">Update</button>
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