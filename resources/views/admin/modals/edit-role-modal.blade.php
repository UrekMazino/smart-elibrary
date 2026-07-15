<div class="modal fade" id="editRoledModal" tabindex="-1" aria-labelledby="editRoledModalTitle" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editRoledModalTitle">Edit role</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <form action="{{ route('admin.update_role') }}" method="POST">
        @csrf
        <div class="modal-body">
          <input type="hidden" id="roleIdInput" name="role_id">
          <div class="mb-3">
            <label for="roleNameInput" class="form-label">Role name <small class="text-muted">(system key — not editable)</small></label>
            <input type="text" id="roleNameInput" class="form-control" name="role_name" readonly>
          </div>
          <div class="mb-3">
            <label for="editDisplayNameInput" class="form-label">Display name</label>
            <input type="text" id="editDisplayNameInput" class="form-control" name="display_name" placeholder="e.g. Circulation Admin">
          </div>
          <div class="mb-3">
            <label for="editDescriptionInput" class="form-label">Description</label>
            <input type="text" id="editDescriptionInput" class="form-control" name="description" placeholder="What this role is for">
          </div>
          <p class="text-muted small mb-0"><i data-feather="grid" class="icon-sm me-1"></i>Assign permissions to this role in the <strong>Access Matrix</strong> below.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
          <button type="submit" class="btn btn-primary">Update role</button>
        </div>
      </form>
    </div>
  </div>
</div>
