<div class="row">          
      <div class="col-md-6 col-sm-12 offset-md-3">
          <div class="modal fade" id="editAdminRoleModal" tabindex="-1" aria-labelledby="editAdminRoleModalTitle" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="editAdminRoleModalTitle">Admin Role</h5>

                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                    </div>
                    <div class="modal-body">
                        <form action="{{ route('admin.change_role') }}" method="POST">
                            @csrf
                            <div class="container">
                                <div class="card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-3 text-right">
                                                <select class="form-select" id="role-list" name="role_id">
                                                    <option value="0" selected >Select a role..</option>
                                                    @foreach($assignableRoles ?? getRoles() as $role)
                                                        <option value="{{ $role->id }}">{{ $role->display_name}}</option>
                                                    @endforeach
                                                </select>
                                                <input type="hidden" id="user-id" name="user_id">
                                            </div>
                            
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12 col-sm-12 mb-3 text-center">
                                                <button type="submit" class="btn btn-primary me-2">Change Role</button>
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