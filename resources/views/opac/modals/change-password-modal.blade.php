{{-- Posts to the patron's OWN endpoint. It used to post to /admin/profile/update/password,
     which is behind the `admin` middleware, so a patron was bounced to the homepage and the
     password was never changed. Errors come back in the "updatePassword" bag so a failed
     password change can't be confused with a failed profile save. --}}
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pw-modal">
            <form method="POST" id="patron-change-password-frm" action="{{ route('update.patron.password') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalTitle">Change Password</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                </div>

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input id="current_password" name="current_password" type="password" required
                               autocomplete="current-password" placeholder="Enter current password"
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                        @error('current_password', 'updatePassword')
                            <span class="pw-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password" required
                               autocomplete="new-password" placeholder="Enter new password"
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                        @error('password', 'updatePassword')
                            <span class="pw-error" role="alert">{{ $message }}</span>
                        @enderror
                        @unless($errors->updatePassword->has('password'))
                            <span class="pw-hint">At least 8 characters.</span>
                        @endunless
                    </div>

                    <div class="mb-1">
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password" placeholder="Re-enter new password" class="form-control">
                        <span class="pw-hint" id="password-match"></span>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="pf-btn pf-btn-pass" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="pf-btn pf-btn-save" id="change-password-btn">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
