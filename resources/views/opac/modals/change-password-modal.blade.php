<div class="row">          
    <div class="col-md-6 col-sm-12 offset-md-3">
        <div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalTitle" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <form method="POST" id="admin-change-password-frm" action="{{ url('/admin/profile/update/password/') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="modal-header">
                            <h5 class="modal-title" id="changePasswordModalTitle">Change Password</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                        </div>
                        <div class="modal-body">
                                <div class="container">
                                    <div class="row mb-3">
                                        <label for="current_password" class="form-label  @error('current_password') is-invalid @enderror">Current Password</label>
                                        <input id="current_password" class="form-control" name="current_password" type="password" placeholder="Enter current password" required>
                                            @error('current_password')
                                                <span class="invalid-feedback" role="alert">
                                                    {{ $message }}
                                                </span>
                                            @enderror
                                        </div>
                                    <div class="row mb-3">
                                        <label for="password" class="form-label">New Password</label>
                                        <input id="password" class="form-control  @error('password') is-invalid @enderror" name="password" type="password" placeholder="New password" required>
                                            @error('password')
                                                <span class="invalid-feedback" role="alert">
                                                    {{ $message }}
                                                </span>
                                            @enderror
                                    </div>
                                    <div class="row mb-3">
                                        <label for="password_confirmation" class="form-label">Confirm password</label>
                                        <input id="password_confirmation" class="form-control" name="password_confirmation" type="password" placeholder="Confirm new password">
                                    </div>
                                </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-info" id="change-password-btn" disabled>Save</button>
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        </div>
                    
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>