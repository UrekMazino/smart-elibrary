{{-- Posts to the patron's OWN endpoint. It used to post to /admin/profile/update/password,
     which is behind the `admin` middleware, so a patron was bounced to the homepage and the
     password was never changed. Errors come back in the "updatePassword" bag so a failed
     password change can't be confused with a failed profile save.
     An account made with Google has no password: "Set a Password", after a recent Google sign-in
     or "Confirm with Google" (App\Services\GoogleSignIn) in place of the current password. --}}
@php
    $pwHas = Auth::user()->hasPassword();
    $pwConfirmed = $pwHas || \App\Services\GoogleSignIn::recentlyConfirmed(request()->session());
@endphp
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content pw-modal">
            <form method="POST" id="patron-change-password-frm" action="{{ route('update.patron.password') }}">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="changePasswordModalTitle">{{ $pwHas ? 'Change Password' : 'Set a Password' }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="btn-close"></button>
                </div>

                <div class="modal-body">
                    @if (! $pwHas)
                        <p class="pw-note">You sign in with Google, so your account has no password. A password lets you sign in with your email too.</p>
                        @unless ($pwConfirmed)
                            <p class="pw-note">First confirm it’s you with Google.</p>
                            <a class="pf-btn pf-btn-save pw-google" href="{{ route('google.confirm') }}">Confirm with Google</a>
                        @endunless
                        @error('current_password', 'updatePassword')
                            <span class="pw-error d-block mt-2" role="alert">{{ $message }}</span>
                        @enderror
                    @else
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Current password</label>
                        <input id="current_password" name="current_password" type="password" required
                               autocomplete="current-password" placeholder="Enter current password"
                               class="form-control @error('current_password', 'updatePassword') is-invalid @enderror">
                        @error('current_password', 'updatePassword')
                            <span class="pw-error" role="alert">{{ $message }}</span>
                        @enderror
                    </div>
                    @endif

                    @if ($pwConfirmed)
                    <div class="mb-3">
                        <label for="password" class="form-label">New password</label>
                        <input id="password" name="password" type="password" required
                               autocomplete="new-password" placeholder="Enter new password"
                               class="form-control @error('password', 'updatePassword') is-invalid @enderror">
                        @error('password', 'updatePassword')
                            <span class="pw-error" role="alert">{{ $message }}</span>
                        @enderror
                        @unless($errors->updatePassword->has('password'))
                            <span class="pw-hint">At least 12 characters. A few unrelated words make a strong password that is easy to remember.</span>
                        @endunless
                    </div>

                    <div class="mb-1">
                        <label for="password_confirmation" class="form-label">Confirm new password</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required
                               autocomplete="new-password" placeholder="Re-enter new password" class="form-control">
                        <span class="pw-hint" id="password-match"></span>
                    </div>
                    @endif
                </div>

                <div class="modal-footer">
                    <button type="button" class="pf-btn pf-btn-pass" data-bs-dismiss="modal">Cancel</button>
                    @if ($pwConfirmed)
                        <button type="submit" class="pf-btn pf-btn-save" id="change-password-btn">{{ $pwHas ? 'Update Password' : 'Set Password' }}</button>
                    @endif
                </div>
            </form>
        </div>
    </div>
</div>
