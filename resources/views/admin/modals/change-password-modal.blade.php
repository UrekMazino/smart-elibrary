{{-- Errors come back in the "updatePassword" bag so a failed password change can't be
     confused with a failed profile save (both post from the same page). --}}
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" id="admin-change-password-frm" action="{{ url('/admin/profile/update/password') }}">
                @csrf
                <div class="modal-header">
                    <h6 class="modal-title" id="changePasswordModalTitle">
                        <i data-feather="lock" class="icon-sm me-1"></i>Change Password
                    </h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password <span class="req">*</span></label>
                        <input id="current_password" name="current_password" type="password" required
                               autocomplete="current-password" placeholder="Enter current password"
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                        @error('current_password', 'updatePassword')
                            <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password <span class="req">*</span></label>
                        <input id="password" name="password" type="password" required
                               autocomplete="new-password" placeholder="Enter new password"
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                        @error('password', 'updatePassword')
                            <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                        @enderror
                        @unless($errors->updatePassword->has('password'))
                            <span class="form-hint">At least 12 characters. A few unrelated words make a strong password that is easy to remember.</span>
                        @endunless
                    </div>

                    <div class="mb-1">
                        <label for="password_confirmation" class="form-label">Confirm new password <span class="req">*</span></label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password" placeholder="Re-enter new password" class="form-control">
                        <span class="form-hint" id="password-match"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-sm" id="change-password-btn">Update Password</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>
