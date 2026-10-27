@extends("admin.layouts.master")
{{-- Administration → Accounts → Signed in now (SignedInNowController, App\Services\BrowserSessions): who is signed in,
     on which browsers; sign one person out everywhere, or everyone. No IP addresses: who and how, not where. --}}

@section('css')
    <style>
      .sn-tiles { display: grid; grid-template-columns: repeat(auto-fit, minmax(190px, 1fr)); gap: 12px; margin-bottom: 16px; }
      .sn-tile { padding: 12px 16px; border: 1px solid var(--ui-border, #eef0f4); border-radius: 10px; background: var(--ui-surface, #fff); }
      .sn-tile strong { display: block; font-size: 1.5rem; font-weight: 700; line-height: 1.2; color: var(--ui-text, #22283b); }
      .sn-tile span { font-size: 0.75rem; color: var(--ui-muted, #7987a1); }

      #sn-table { font-size: 0.8125rem; }
      #sn-table thead th {
        font-size: 0.6875rem; text-transform: uppercase; letter-spacing: .4px; color: var(--ui-muted, #7987a1);
        white-space: nowrap; padding: 9px 10px; border-bottom: 2px solid var(--ui-border, #eef0f2);
      }
      #sn-table tbody td { padding: 8px 10px; vertical-align: middle; white-space: normal; }   /* the template's tables don't wrap */
      .u-cell { display: flex; align-items: center; gap: 9px; }
      .u-avatar {
        flex: 0 0 30px; width: 30px; height: 30px; border-radius: 50%; background: var(--ui-surface-2, #e4e9f2);
        color: var(--ui-text-2, #4a5a78); font-size: 0.71875rem; font-weight: 700; display: flex; align-items: center; justify-content: center;
      }
      .u-name { font-weight: 600; color: var(--ui-text, #22283b); }
      .u-sub { color: var(--ui-muted, #7987a1); font-size: 0.71875rem; word-break: break-all; }
      .sn-session { color: var(--ui-text-2, #4a5a78); }
      .sn-session + .sn-session { margin-top: 3px; }
      .sn-when { color: var(--ui-muted, #7987a1); font-size: 0.75rem; }
      /* `#sn-table` prefix beats the template's `.badge { color:#fff }`. */
      #sn-table .sn-pill { background: var(--ui-navy-soft, #e8eefc); color: var(--ui-navy, #33509c); font-weight: 600; font-size: 0.6875rem; }
    </style>
@endsection

@section('content')
<div class="page-content">

    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('accounts') }}">Accounts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Signed in now</li>
        </ol>
    </nav>

    @if (session('success'))
        <div class="alert alert-success py-2" style="font-size: 0.8125rem;" role="status">{{ session('success') }}</div>
    @endif

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-1">
                <h6 class="card-title mb-0">SIGNED IN NOW</h6>
                @if ($available)
                    <div class="d-flex gap-2">
                        <a href="{{ request()->fullUrl() }}" class="btn btn-outline-secondary btn-sm"><i data-feather="refresh-cw" class="icon-sm me-1"></i>Refresh</a>
                        <button type="button" class="btn btn-outline-danger btn-sm" data-sign-out="everyone">Sign out everyone</button>
                    </div>
                @endif
            </div>
            <p class="text-muted mb-3" style="font-size: 0.78125rem;">
                Who is signed in right now, and on which browsers. The page doesn’t update by itself: refresh it to see
                changes. Signing someone out ends their sessions on every device and turns off “Keep me signed in”; they
                can sign in again.
            </p>

            @unless ($available)
                <div class="alert alert-warning mb-0" style="font-size: 0.8125rem;">
                    This page needs sessions kept in the database: set <code>SESSION_DRIVER=database</code> in <code>.env</code>.
                </div>
            @else
                <div class="sn-tiles">
                    <div class="sn-tile"><strong data-count="staff">{{ $counts['staff'] }}</strong><span>Staff signed in</span></div>
                    <div class="sn-tile"><strong data-count="patrons">{{ $counts['patrons'] }}</strong><span>Patrons signed in</span></div>
                    <div class="sn-tile"><strong data-count="visitors">{{ $counts['visitors'] }}</strong><span>Visitors not signed in, last 30 minutes (roughly; search engines left out)</span></div>
                </div>

                <form method="GET" action="{{ route('admin.sessions') }}" class="d-flex flex-wrap gap-2 mb-3" role="search">
                    <select name="type" class="form-select form-select-sm" style="width: auto;" aria-label="Show">
                        <option value="all" @selected($type === 'all')>Everyone</option>
                        <option value="staff" @selected($type === 'staff')>Staff</option>
                        <option value="patrons" @selected($type === 'patrons')>Patrons</option>
                    </select>
                    <input type="search" name="q" value="{{ $search }}" class="form-control form-control-sm" style="max-width: 260px;"
                           placeholder="Name or email" aria-label="Search by name or email">
                    <button type="submit" class="btn btn-primary btn-sm">Show</button>
                </form>

                <div class="table-responsive">
                    <table id="sn-table" class="table table-hover table-sm align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th style="width: 90px">Account</th>
                                <th>Browsers</th>
                                <th style="width: 130px">Last active</th>
                                <th style="width: 100px" class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($people as $person)
                                @php
                                    $words = preg_split('/\s+/', trim((string) $person->user->name));
                                    $initials = mb_strtoupper(mb_substr($words[0] ?? '', 0, 1) . mb_substr($words[1] ?? '', 0, 1));
                                    $guarded = $person->user->hasRole('developer') && ! $developer;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="u-cell">
                                            <span class="u-avatar" aria-hidden="true">{{ $initials ?: '?' }}</span>
                                            <div>
                                                <div class="u-name">{{ $person->user->name }}@if ($person->you) <span class="badge sn-pill">You</span>@endif</div>
                                                <div class="u-sub">{{ $person->user->email }}</div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>{{ $person->staff ? 'Staff' : 'Patron' }}</td>
                                    <td>
                                        @foreach ($person->sessions as $session)
                                            <div class="sn-session">
                                                {{ $session->browser }}
                                                <span class="sn-when">· {{ $session->signed_in_at ? 'signed in ' . $session->signed_in_at->format('M j, g:i A') : 'signed in earlier' }}</span>
                                                @if ($session->remembered) <span class="badge sn-pill">Kept signed in</span>@endif
                                            </div>
                                        @endforeach
                                    </td>
                                    <td title="{{ $person->last_active->format('M j, Y g:i A') }}">{{ $person->last_active->diffForHumans() }}</td>
                                    <td class="text-center">
                                        @if ($person->you || $guarded)
                                            <span class="text-muted" title="{{ $person->you ? 'Use Log Out, or your Profile for your other sessions' : 'Only a developer can sign out a developer account' }}">—</span>
                                        @else
                                            <button type="button" class="btn btn-outline-danger btn-xs" data-sign-out="{{ $person->user->id }}" data-name="{{ $person->user->name }}">Sign out</button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="text-center text-muted py-4">Nobody {{ $search !== '' ? 'matching “' . $search . '” ' : '' }}is signed in.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            @endunless
        </div>
    </div>
</div>

{{-- Signing out takes your password. A wrong one comes back in the "signOut" bag and the dialog opens again. --}}
<div class="modal fade" id="signOutModal" tabindex="-1" aria-labelledby="signOutTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="{{ route('admin.sessions.sign-out') }}">
                @csrf
                @method('DELETE')
                <input type="hidden" name="target" value="{{ old('target') }}">
                <div class="modal-header">
                    <h6 class="modal-title" id="signOutTitle">Sign out</h6>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" data-sign-out-text></p>
                    <label for="sign-out-password" class="form-label">Your password</label>
                    <input id="sign-out-password" name="password" type="password" required autocomplete="current-password"
                           class="form-control @error('password', 'signOut') is-invalid @enderror">
                    @error('password', 'signOut')
                        <span class="invalid-feedback d-block" role="alert">{{ $message }}</span>
                    @enderror
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger btn-sm">Sign out</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
$(function () {
    if (window.feather) feather.replace();

    var $modal = $('#signOutModal'), form = $modal.find('form')[0];
    var everyone = 'Sign out everyone? Everyone signed in, except you on this device, is signed out, and “Keep me signed in” stops working on their devices.'
        + @json($developer ? '' : ' Developer accounts stay signed in.');

    // Whom the dialog signs out: a person's id, or "everyone".
    function aim(target, name) {
        form.elements.target.value = target;
        $modal.find('[data-sign-out-text]').text(target === 'everyone' ? everyone
            : 'Sign out ' + name + ' everywhere? Their sessions end on every device, and “Keep me signed in” stops working.');
    }

    $(document).on('click', '[data-sign-out]', function () {
        aim(this.dataset.signOut, this.dataset.name);
        $modal.modal('show');
    });
    $modal.on('shown.bs.modal', function () { form.elements.password.focus(); });
    $modal.on('hidden.bs.modal', function () { form.elements.password.value = ''; });

    @if ($errors->signOut->any())
        // A wrong password: the dialog again, for the same person.
        aim(@json((string) old('target')), @json($people->first(fn ($p) => (string) $p->user->id === (string) old('target'))?->user->name ?? 'that person'));
        $modal.modal('show');
    @endif
});
</script>
@endsection
