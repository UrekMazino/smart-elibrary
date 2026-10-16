@extends("admin.layouts.master")

@section('css')
    <style>
      .form-label { font-size: 12.5px; font-weight: 600; color: #33415c; margin-bottom: 4px; }
      .form-hint  { color: #a3adc2; font-size: 11.5px; margin-top: 4px; display: block; }
      .req { color: #c92a4b; }

      .form-section {
        font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #7987a1;
        font-weight: 700; padding-bottom: 6px; border-bottom: 1px solid #eef0f2; margin-bottom: 14px;
      }

      /* Identity card */
      .id-avatar {
        width: 74px; height: 74px; border-radius: 50%; margin: 0 auto 12px;
        background: #e4e9f2; color: #4a5a78; font-size: 26px; font-weight: 700;
        display: flex; align-items: center; justify-content: center; letter-spacing: .5px;
      }
      .id-name { font-size: 15px; font-weight: 700; color: #22283b; }
      .id-email { color: #7987a1; font-size: 12.5px; word-break: break-all; }
      .badge-role {
        background: #e8eefc; color: #33509c; font-weight: 600; font-size: 11px; margin: 2px;
      }
      .id-meta { border-top: 1px solid #eef0f2; margin-top: 14px; padding-top: 12px; }
      .id-meta dt {
        font-size: 10.5px; text-transform: uppercase; letter-spacing: .4px;
        color: #a3adc2; font-weight: 700; margin-bottom: 2px;
      }
      .id-meta dd { font-size: 12.5px; color: #33415c; margin-bottom: 10px; }
      .id-meta dd.mono { font-family: ui-monospace, SFMono-Regular, Menlo, monospace; letter-spacing: .5px; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
        </ol>
    </nav>

    @if(session('success'))
        <div class="row"><div class="col-12">
            <div class="alert alert-success py-2" style="font-size: 13px;">{{ session('success') }}</div>
        </div></div>
    @endif

    <div class="row">
        {{-- Who you are: read-only summary --}}
        <div class="col-lg-4 col-md-5 grid-margin stretch-card">
            <div class="card">
                <div class="card-body text-center">
                    @php
                        $parts = preg_split('/\s+/', trim($user->name ?: '?'));
                        $inits = strtoupper(substr($parts[0], 0, 1) . (count($parts) > 1 ? substr(end($parts), 0, 1) : ''));
                    @endphp
                    <div class="id-avatar">{{ $inits }}</div>
                    <div class="id-name">{{ $user->name }}</div>
                    <div class="id-email mb-2">{{ $user->email }}</div>

                    <div>
                        @forelse($user->getRoleNames() as $role)
                            <span class="badge badge-role rounded-pill">{{ $role }}</span>
                        @empty
                            <span class="text-muted" style="font-size: 11.5px;">No role assigned</span>
                        @endforelse
                    </div>

                    <dl class="id-meta text-start mb-0">
                        <dt>Account type</dt>
                        <dd>{{ $user->usertype ?? '—' }}</dd>

                        <dt>Username</dt>
                        <dd>{{ $user->username ?: '—' }}</dd>

                        <dt>Library code</dt>
                        <dd class="mono">{{ $user->library_code ?: '—' }}</dd>

                        <dt>Member since</dt>
                        <dd class="mb-0">{{ $user->created_at ? $user->created_at->format('d M Y') : '—' }}</dd>
                    </dl>
                </div>
            </div>
        </div>

        {{-- What you can change --}}
        <div class="col-lg-8 col-md-7 grid-margin stretch-card">
            <div class="card">
                <div class="card-body">
                    <h6 class="card-title mb-1">YOUR DETAILS</h6>
                    <p class="text-muted mb-3" style="font-size: 12.5px;">
                        Keep your contact details current &mdash; they're how the library reaches you.
                    </p>

                    {{-- Errors from the profile form (the password form uses its own bag) --}}
                    @if($errors->getBag('default')->any())
                        <div class="alert alert-danger py-2" style="font-size: 13px;">
                            <ul class="mb-0 ps-3">
                                @foreach($errors->getBag('default')->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" id="admin-profile-frm" action="{{ url('/admin/profile/update') }}">
                        @csrf

                        <div class="form-section">Personal</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full name <span class="req">*</span></label>
                                <input type="text" name="name" id="name" class="form-control"
                                       placeholder="Juan Dela Cruz" value="{{ old('name', $user->name) }}" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="birthdate" class="form-label">Birthdate <span class="req">*</span></label>
                                <input type="text" name="birthdate" id="birthdate" class="form-control datepicker"
                                       placeholder="mm/dd/yyyy" value="{{ old('birthdate', $user->birthdate ? \Carbon\Carbon::parse($user->birthdate)->format('m/d/Y') : '') }}" required autocomplete="off">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label d-block">Sex</label>
                                @php $sex = old('sex', $user->sex); @endphp
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="sex" id="sexMale" value="Male" @checked($sex === 'Male')>
                                    <label class="form-check-label" for="sexMale">Male</label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="radio" name="sex" id="sexFemale" value="Female" @checked($sex === 'Female')>
                                    <label class="form-check-label" for="sexFemale">Female</label>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="patrontypes_id" class="form-label">Patron type <span class="req">*</span></label>
                                <select class="form-select" name="patron_type_id" id="patrontypes_id" required>
                                    <option value="" disabled @selected(! old('patron_type_id', $user->patrontypes_id))>Select type…</option>
                                    @foreach (getPatronType() as $patronType)
                                        <option value="{{ $patronType->id }}" @selected(old('patron_type_id', $user->patrontypes_id) == $patronType->id)>
                                            {{ $patronType->types }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-section mt-2">Contact</div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email address <span class="req">*</span></label>
                                <input id="email" type="email" name="email" class="form-control"
                                       placeholder="name@example.com" value="{{ old('email', $user->email) }}" required>
                                <span class="form-hint">You sign in with this address.</span>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="contactnum" class="form-label">Contact number <span class="req">*</span></label>
                                <input type="text" name="contactnum" id="contactnum" class="form-control"
                                       placeholder="09XX XXX XXXX" value="{{ old('contactnum', $user->contactnum) }}" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="affiliation" class="form-label">Affiliation / Agency</label>
                                <input type="text" name="affiliation" id="affiliation" class="form-control"
                                       placeholder="e.g. DOST-PCAARRD" value="{{ old('affiliation', $user->affiliation) }}">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="region_id" class="form-label">Region</label>
                                <select class="form-select" name="region_id" id="region_id">
                                    <option value="">Select region…</option>
                                    @foreach (getRegion() as $item)
                                        <option value="{{ $item->id }}" @selected(old('region_id', $user->region_id) == $item->id)>
                                            {{ $item->desc }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="address" class="form-label">Complete address</label>
                            <input type="text" id="address" name="address" class="form-control"
                                   placeholder="Street, Barangay, City/Municipality, Province"
                                   value="{{ old('address', $user->location) }}">
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                            <a href="{{ url('/admin/dashboard') }}" class="btn btn-outline-secondary">Cancel</a>
                        </div>
                    </form>

                    <div class="form-section mt-4">Security</div>
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <span class="text-muted" style="font-size: 12.5px;">
                            Change the password you use to sign in.
                        </span>
                        <button type="button" class="btn btn-outline-danger btn-sm" onclick="showChangePasswordModal()">
                            <i data-feather="lock" class="icon-sm me-1"></i>Change Password
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

</div>
@include('admin.modals.change-password-modal')
@endsection

@section('js')
<script>
    function showChangePasswordModal()
    {
        $('#changePasswordModal').modal('show');
    }

    $(document).ready(function(){
        if (window.feather) feather.replace();

        $('.datepicker').datepicker({
            format: 'mm/dd/yyyy',
            autoclose: true,
            endDate: '0d',
        });

        // Live match hint on the new password. The Save button is NOT gated on this —
        // the server is what actually enforces it, and a disabled button used to leave
        // people stuck with no explanation.
        function checkMatch() {
            var pw = $('#password').val(), confirmPw = $('#password_confirmation').val();
            var $note = $('#password-match');
            if (!confirmPw) { $note.text('').removeClass('text-danger text-success'); return; }
            if (pw === confirmPw) $note.text('Passwords match.').addClass('text-success').removeClass('text-danger');
            else                  $note.text('Passwords do not match.').addClass('text-danger').removeClass('text-success');
        }
        $('#password, #password_confirmation').on('keyup', checkMatch);

        // Re-open the modal when the password form came back with errors.
        @if($errors->updatePassword->any())
            $('#changePasswordModal').modal('show');
        @endif
    });
</script>
@endsection
