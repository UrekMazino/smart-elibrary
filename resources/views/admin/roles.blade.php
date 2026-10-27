@extends("admin.layouts.master")

@section('css')
    <style>
      .ac-head { display:flex; justify-content:space-between; align-items:flex-end; flex-wrap:wrap; gap:10px; margin-bottom:18px; }
      .ac-head h4 { font-weight:700; color:var(--ui-text, #22283b); margin:0; }
      .ac-head p  { color:var(--ui-muted, #8a94a6); margin:2px 0 0; font-size:0.8125rem; }
      .page-content .card { border:1px solid var(--ui-border, #eef0f4); border-radius:12px; box-shadow:0 1px 2px rgba(20,30,60,.04); }
      .mono { font-family:ui-monospace,Menlo,Consolas,monospace; font-size:0.6875rem; color:var(--ui-muted-2, #a3adc2); }

      /* Roles list */
      table.roles-tbl { font-size:0.8125rem; width:100%; }
      table.roles-tbl thead th { font-size:0.6875rem; text-transform:uppercase; letter-spacing:.4px; color:var(--ui-muted, #7987a1); padding:9px 10px; border-bottom:2px solid var(--ui-border, #eef0f2); white-space:nowrap; }
      table.roles-tbl tbody td { padding:9px 10px; vertical-align:middle; border-bottom:1px solid var(--ui-border, #f2f4f8); }
      .role-name { font-weight:600; color:var(--ui-text, #22283b); }
      .pill { display:inline-block; min-width:26px; text-align:center; background:var(--ui-surface-2, #eef1f8); color:var(--ui-text-2, #4a5a78); border-radius:20px; padding:2px 9px; font-size:0.75rem; font-weight:600; }

      /* Access matrix: a row per module with its switch per role; what's inside sits under it, shut until opened,
         and locked for a role whose module is off. */
      .mx-steps { display:flex; flex-wrap:wrap; gap:8px 10px; margin:0 0 10px; padding:0; list-style:none; counter-reset:step; }
      .mx-steps li { counter-increment:step; display:flex; align-items:center; gap:8px; font-size:0.8125rem; color:var(--ui-text, #22283b); background:var(--ui-surface-2, #f4f6fb); border-radius:20px; padding:5px 14px 5px 6px; }
      .mx-steps li::before { content:counter(step); flex:0 0 22px; height:22px; border-radius:50%; background:var(--t-primary); color:#fff; font-size:0.75rem; font-weight:700; display:flex; align-items:center; justify-content:center; }
      .matrix-hint { color:var(--ui-muted, #8a94a6); font-size:0.78125rem; margin:0 0 12px; }
      .mx-legend { display:flex; flex-wrap:wrap; gap:8px 18px; align-items:center; font-size:0.75rem; color:var(--ui-text-2, #6b7489); margin:0 0 14px; }
      .mx-legend > span { display:inline-flex; align-items:center; gap:6px; }
      .mx-tools { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin:0 0 10px; }
      .mx-tools .form-control { max-width:280px; }
      .matrix-wrap { overflow:auto; max-height:70vh; border:1px solid var(--ui-border, #eef0f4); border-radius:10px; }
      table.matrix { border-collapse:separate; border-spacing:0; width:100%; font-size:0.8125rem;
        --lock: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4' y='11' width='16' height='10' rx='2'/%3E%3Cpath d='M8 11V7a4 4 0 0 1 8 0v4'/%3E%3C/svg%3E"); }
      table.matrix th, table.matrix td { padding:8px 12px; border-bottom:1px solid var(--ui-border, #eef0f4); }
      table.matrix thead th { position:sticky; top:0; z-index:3; background:var(--ui-surface, #fff); font-size:0.6875rem; text-transform:uppercase; letter-spacing:.3px; color:var(--ui-muted, #7987a1); border-bottom:2px solid var(--ui-border, #eef0f2); }
      table.matrix thead th.role-col { text-align:center; min-width:104px; }
      table.matrix thead th.role-col small { display:block; font-weight:400; text-transform:none; letter-spacing:0; }
      table.matrix .perm-cell { position:sticky; left:0; z-index:1; background:var(--ui-surface, #fff); text-align:left; min-width:300px; font-weight:400; box-shadow:1px 0 0 var(--ui-border, #eef0f4); }
      table.matrix thead th.perm-cell { z-index:4; font-weight:700; }
      .perm-name { font-weight:600; color:var(--ui-text, #22283b); display:block; line-height:1.3; }
      .mx-cell { text-align:center; vertical-align:middle; }
      tr.mx-none td { text-align:center; color:var(--ui-muted, #7987a1); padding:18px; }

      /* A module's row stands out and holds the switches. */
      tr.mx-module > th, tr.mx-module > td { background:var(--ui-surface-2, #f4f6fb); border-top:1px solid var(--ui-border, #e3e7ef); }
      .mod-head { display:flex; align-items:center; gap:10px; }
      .mod-chevron { flex:0 0 26px; height:26px; padding:0; border:0; border-radius:6px; background:transparent; color:var(--ui-text-2, #5a6a85); display:flex; align-items:center; justify-content:center; cursor:pointer; }
      .mod-chevron:hover { background:var(--ui-surface, #fff); color:var(--ui-text, #22283b); }
      .mod-chevron svg { width:16px; height:16px; transition:transform .15s; }
      tr.mx-module.open .mod-chevron svg { transform:rotate(90deg); }
      .mod-chevron-space { flex:0 0 26px; }
      .mod-icon { flex:0 0 32px; height:32px; border-radius:8px; display:flex; align-items:center; justify-content:center; background:color-mix(in srgb, var(--t-primary) 12%, var(--ui-surface, #fff)); color:var(--ui-accent-text, var(--t-primary)); }
      .mod-icon svg { width:16px; height:16px; }
      .mod-name { display:block; font-weight:700; font-size:0.8125rem; text-transform:uppercase; letter-spacing:.4px; color:var(--ui-text, #22283b); line-height:1.3; }
      .mod-where { display:block; font-size:0.71875rem; color:var(--ui-text-2, #6b7489); line-height:1.4; }
      .mod-count { display:block; min-height:15px; margin-top:3px; font-size:0.6875rem; color:var(--ui-muted, #7987a1); white-space:nowrap; }
      .mod-count.waiting { color:var(--ui-warning-ink, #a06e14); font-weight:700; }

      /* The module switch, and the tick for what's inside */
      .mx-switch { position:relative; display:inline-block; width:38px; height:22px; padding:0; border:0; border-radius:22px; background:var(--ui-muted-2, #c3cad8); cursor:pointer; vertical-align:middle; transition:background .15s; }
      .mx-switch::after { content:''; position:absolute; top:3px; left:3px; width:16px; height:16px; border-radius:50%; background:#fff; box-shadow:0 1px 2px rgba(0,0,0,.25); transition:transform .15s; }
      .mx-switch.on { background:#00a496; }
      .mx-switch.on::after { transform:translateX(16px); }
      tr.mx-perm .perm-cell { padding-left:60px; }
      tr.mx-perm .perm-cell::before { content:''; position:absolute; left:24px; top:0; bottom:0; border-left:2px solid var(--ui-border, #e3e7ef); }
      .mx-toggle { display:inline-block; width:20px; height:20px; padding:0; border-radius:5px; border:1.5px solid var(--ui-border, #cfd6e4); background:var(--ui-surface, #fff); cursor:pointer; position:relative; transition:background .12s, border-color .12s, opacity .12s; vertical-align:middle; }
      .mx-toggle:hover { border-color:#00a496; }
      .mx-toggle.on { background:#00a496; border-color:#00a496; }
      .mx-toggle.on::after { content:''; position:absolute; left:6px; top:2px; width:5px; height:10px; border:solid #fff; border-width:0 2px 2px 0; transform:rotate(45deg); }
      .mx-switch:focus-visible, .mx-toggle:focus-visible, .mod-chevron:focus-visible { outline:2px solid var(--t-primary); outline-offset:2px; }
      .mx-switch.busy, .mx-toggle.busy { opacity:.4; pointer-events:none; }
      /* Locked (the role's module is off): unticked shows a lock; ticked is "waiting", pale with a lock badge. */
      .mx-cell.locked .mx-toggle { border-style:dashed; background:transparent; }
      .mx-cell.locked .mx-toggle:not(.on)::after { content:''; position:absolute; inset:3px; background-color:var(--ui-muted, #7987a1); -webkit-mask:var(--lock) center / contain no-repeat; mask:var(--lock) center / contain no-repeat; }
      .mx-cell.locked .mx-toggle.on { border-style:solid; border-color:transparent; background:color-mix(in srgb, #00a496 40%, var(--ui-surface, #fff)); }
      .mx-cell.locked .mx-toggle.on::before { content:''; position:absolute; top:-7px; right:-7px; width:12px; height:12px; background-color:var(--ui-warning-ink, #a06e14); -webkit-mask:var(--lock) center / contain no-repeat; mask:var(--lock) center / contain no-repeat; }
      .mx-legend .mx-switch, .mx-legend .mx-toggle { cursor:default; }
      .mx-legend { --lock: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='black' stroke-width='2.6' stroke-linecap='round' stroke-linejoin='round'%3E%3Crect x='4' y='11' width='16' height='10' rx='2'/%3E%3Cpath d='M8 11V7a4 4 0 0 1 8 0v4'/%3E%3C/svg%3E"); }
      @media (max-width: 575.98px) { table.matrix .perm-cell { min-width:220px; } }
      .badge-you { background:color-mix(in srgb, var(--t-primary) 10%, var(--ui-surface, #fff)); color:color-mix(in srgb, var(--t-primary), var(--ui-text, #000) 30%); font-weight:600; font-size:0.625rem; border-radius:10px; padding:1px 7px; text-transform:none; letter-spacing:0; }
      .badge-unused { background:var(--ui-warning-soft, #fff4e5); color:var(--ui-warning-ink, #a15c00); font-weight:600; font-size:0.625rem; border-radius:10px; padding:1px 7px; margin-left:4px; }
      .badge-system { background:var(--ui-surface-2, #f1f3f7); color:var(--ui-text-2, #6b7489); font-weight:600; font-size:0.625rem; border-radius:10px; padding:1px 7px; margin-left:4px; }
    </style>
@endsection

@section('content')
<div class="page-content">
    <nav class="page-breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('accounts') }}">Accounts</a></li>
            <li class="breadcrumb-item active" aria-current="page">Roles</li>
        </ol>
    </nav>

    <div class="ac-head">
      <div>
        <h4>Roles &amp; Access</h4>
        <p>Define roles, then grant or revoke permissions in the matrix — changes save instantly.</p>
      </div>
      <button class="btn btn-primary btn-sm" data-call="viewCreateRoleModal"><i data-feather="plus" class="icon-sm me-1"></i>New role</button>
    </div>

    {{-- ===== Roles (metadata) ===== --}}
    <div class="card mb-3">
      <div class="card-body">
        <h6 class="card-title">Roles</h6>
        <div class="table-responsive">
          <table class="roles-tbl">
            <thead>
              <tr>
                <th>Role</th>
                <th>Description</th>
                <th class="text-center" style="width:110px">Permissions</th>
                <th class="text-center" style="width:80px">Users</th>
                <th class="text-center" style="width:90px">Action</th>
              </tr>
            </thead>
            <tbody>
              @foreach($roles as $role)
                <tr>
                  <td><span class="role-name">{{ $role->display_name ?: $role->name }}</span>@if(in_array($role->name, $systemRoles))<span class="badge-system" title="Built-in role: can't be deleted">built-in</span>@endif @if(in_array($role->id, $myRoleIds))<span class="badge-you">your role</span>@endif<br><span class="mono">{{ $role->name }}</span></td>
                  <td class="text-muted">{{ $role->description ?: '—' }}</td>
                  <td class="text-center"><span class="pill" id="role-perm-count-{{ $role->id }}">{{ count($rolePerms[$role->id]) }}</span></td>
                  <td class="text-center">{{ $userCounts[$role->id] }}</td>
                  <td class="text-center">
                    <div class="btn-group btn-group-sm">
                      <button type="button" class="btn btn-outline-primary btn-sm edit-role-btn"
                        data-id="{{ $role->id }}" data-name="{{ $role->name }}"
                        data-display="{{ $role->display_name }}" data-desc="{{ $role->description }}">Edit</button>
                      @if(in_array($role->name, $systemRoles))
                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Built-in role: can't be deleted">Delete</button>
                      @elseif($userCounts[$role->id] > 0)
                        <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="Move its {{ $userCounts[$role->id] }} user(s) to another role first">Delete</button>
                      @else
                        <button type="button" class="btn btn-outline-danger btn-sm delete-role-btn"
                          data-id="{{ $role->id }}" data-name="{{ $role->display_name ?: $role->name }}">Delete</button>
                      @endif
                    </div>
                  </td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>

    {{-- ===== Access matrix: modules first (their switch per role), then what each role may do inside them ===== --}}
    <div class="card">
      <div class="card-body">
        <h6 class="card-title">Access Matrix</h6>
        <ol class="mx-steps">
          <li>Turn a module on for a role, with the switch on the module’s row</li>
          <li>Open the module and tick what that role may do inside it</li>
        </ol>
        <p class="matrix-hint">Changes save at once and apply on the user’s next page. A tick inside a module that is off does
          nothing: it shows a lock, and the module’s row counts it as waiting. The <strong>developer</strong> superuser always
          has full access and isn’t listed.</p>
        <div class="mx-legend" aria-hidden="true">
          <span><span class="mx-switch on"></span> Module on</span>
          <span><span class="mx-switch"></span> Module off</span>
          <span><span class="mx-toggle on"></span> Allowed</span>
          <span><span class="mx-toggle"></span> Not allowed</span>
          <span><span class="mx-cell locked"><span class="mx-toggle"></span></span> Locked: the module is off</span>
          <span><span class="mx-cell locked"><span class="mx-toggle on"></span></span> <span class="mod-count waiting">Waiting</span> ticked, but the module is off</span>
        </div>
        <div class="mx-tools">
          <input type="search" id="mxFilter" class="form-control form-control-sm" placeholder="Find a permission…" aria-label="Find a permission" autocomplete="off">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="mxOpenAll">Open all</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="mxCloseAll">Close all</button>
        </div>
        <div class="matrix-wrap">
          <table class="matrix">
            <thead>
              <tr>
                <th class="perm-cell">Module / permission</th>
                @foreach($roles as $role)
                  <th class="role-col">{{ $role->display_name ?: $role->name }}<small class="mono">{{ $role->name }}</small>@if(in_array($role->id, $myRoleIds))<span class="badge-you">your role</span>@endif</th>
                @endforeach
              </tr>
            </thead>
            <tbody>
              @foreach($sections as $section)
                @php $gate = $section['gate']; $inside = $section['perms']; $slug = $section['slug']; @endphp
                <tr class="mx-module" data-module="{{ $slug }}" data-label="{{ $section['name'] }}">
                  <th class="perm-cell" scope="row">
                    <div class="mod-head">
                      @if(count($inside))
                        <button type="button" class="mod-chevron" aria-expanded="false" title="Show what’s inside"><i data-feather="chevron-right"></i></button>
                      @else
                        <span class="mod-chevron-space"></span>
                      @endif
                      <span class="mod-icon" aria-hidden="true"><i data-feather="{{ $section['icon'] }}"></i></span>
                      <span>
                        <span class="mod-name">{{ $section['name'] }}</span>
                        <span class="mod-where">{{ $section['where'] }}@if($gate) · <span class="mono">{{ $gate->name }}</span>@endif</span>
                      </span>
                    </div>
                  </th>
                  @foreach($roles as $role)
                    @php
                      $roleName = $role->display_name ?: $role->name;
                      $gateOn = $gate && in_array($gate->id, $rolePerms[$role->id]);
                      $ticked = array_filter($inside, fn ($p) => in_array($p->id, $rolePerms[$role->id]));
                      // In effect: ticked, with the module on (or none needed); waiting: ticked, needing the module, which is off.
                      $active = count(array_filter($ticked, fn ($p) => ! $gate || $gateOn || isset($worksAlone[$p->name])));
                      $waiting = count($ticked) - $active;
                      $countText = implode(' · ', array_filter([
                          ($active || ! $gate || $gateOn) ? $active . ' of ' . count($inside) : '',
                          $waiting ? $waiting . ' waiting' : '',
                      ]));
                    @endphp
                    <td class="mx-cell">
                      @if($gate)
                        <button type="button" class="mx-switch {{ $gateOn ? 'on' : '' }}" role="switch" aria-checked="{{ $gateOn ? 'true' : 'false' }}"
                                data-role="{{ $role->id }}" data-perm="{{ $gate->id }}" data-module="{{ $slug }}" data-mine="{{ in_array($role->id, $myRoleIds) ? 1 : 0 }}"
                                aria-label="{{ $section['name'] }} for {{ $roleName }}" title="{{ $section['name'] }} for {{ $roleName }} ({{ $gate->display_name ?: $gate->name }})"></button>
                      @endif
                      @if(count($inside))
                        <span class="mod-count {{ $waiting ? 'waiting' : '' }}" data-role="{{ $role->id }}">{{ $countText }}</span>
                      @endif
                    </td>
                  @endforeach
                </tr>
                @foreach($inside as $perm)
                  @php $permName = $perm->display_name ?: $perm->name; $alone = $worksAlone[$perm->name] ?? null; @endphp
                  <tr class="mx-perm" data-module="{{ $slug }}" hidden>
                    <td class="perm-cell">
                      <span class="perm-name">{{ $permName }}</span>@if(in_array($perm->name, $unused))<span class="badge-unused" title="Nothing in the app checks this permission yet, so ticking it changes nothing">not used yet</span>@endif @if($alone)<span class="badge-system" title="{{ $alone }}">works without the module</span>@endif
                      <span class="mono">{{ $perm->name }}</span>
                    </td>
                    @foreach($roles as $role)
                      @php
                        $roleName = $role->display_name ?: $role->name;
                        $on = in_array($perm->id, $rolePerms[$role->id]);
                        $locked = $gate && ! $alone && ! in_array($gate->id, $rolePerms[$role->id]);
                      @endphp
                      <td class="mx-cell {{ $locked ? 'locked' : '' }}">
                        <button type="button" class="mx-toggle {{ $on ? 'on' : '' }}" aria-pressed="{{ $on ? 'true' : 'false' }}"
                                data-role="{{ $role->id }}" data-perm="{{ $perm->id }}" data-name="{{ $permName }}" data-mine="{{ in_array($role->id, $myRoleIds) ? 1 : 0 }}" @if($alone) data-alone="1" @endif
                                aria-label="{{ $roleName }}: {{ $permName }}" title="{{ $locked ? 'Turn on ' . $section['name'] . ' for ' . $roleName . ' first' : $roleName . ': ' . $permName }}"></button>
                      </td>
                    @endforeach
                  </tr>
                @endforeach
              @endforeach
              <tr class="mx-none" hidden><td colspan="{{ count($roles) + 1 }}">No permission matches.</td></tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>
</div>

@include('admin.modals.add-role-modal')
@include('admin.modals.edit-role-modal')
<form method="post" action="" id="frm_delete">
    @csrf
    @method('DELETE')
</form>
@endsection

@section('js')
<script nonce="{{ cspNonce() }}">
// This page's buttons (data-call; onclick= is blocked by the Content Security Policy).
AdminActions.allow(['viewCreateRoleModal']);

$(function () {
    if (window.feather) feather.replace();

    // ---- Access matrix: modules first, then what each role may do inside them ----
    var toggleUrl = @json(route('admin.role_toggle_permission')), token = @json(csrf_token());
    var roleNames = @json($roles->mapWithKeys(fn ($r) => [$r->id => $r->display_name ?: $r->name]));
    var neutral = '#6c757d', OPEN_KEY = 'elib.roles.open';

    function moduleRow(slug) { return $('tr.mx-module[data-module="' + slug + '"]'); }
    function insideRows(slug) { return $('tr.mx-perm[data-module="' + slug + '"]'); }
    function switchOf(slug, roleId) { return moduleRow(slug).find('.mx-switch[data-role="' + roleId + '"]'); }

    // Set one (role, permission) to a state; the answer says what is stored now.
    function setGrant(roleId, permId, granted) {
        return $.ajax({ type: 'POST', url: toggleUrl, dataType: 'json',
                        data: { _token: token, role_id: roleId, permission_id: permId, granted: granted ? 1 : 0 } });
    }

    function report(roleId, res, mine, next) {
        $('#role-perm-count-' + roleId).text(res.permCount);
        var text = [next, mine ? 'This is your own role — reload the page to see your menu change.' : ''].filter(Boolean).join(' ');
        Swal.fire({ toast: true, position: 'top-end', icon: 'success', showConfirmButton: false, timer: mine || next ? 4200 : 2200,
                    title: res.message, text: text });
    }

    function failed(xhr) {
        if (xhr.status !== 403 && xhr.status !== 419) Swal.fire('Error', 'Could not update the permission. Please try again.', 'error');
    }

    // One module for one role: its locked cells, and its count ("3 of 9"; "2 waiting": ticked, but the module is off).
    // A permission marked data-alone works without the module, so it never locks or waits.
    function refreshModule(slug, roleId) {
        var $row = moduleRow(slug), $sw = switchOf(slug, roleId), on = !$sw.length || $sw.hasClass('on');
        var label = $row.data('label'), role = roleNames[roleId];
        var $toggles = insideRows(slug).find('.mx-toggle[data-role="' + roleId + '"]');
        $toggles.each(function () {
            var locked = !on && !this.dataset.alone;
            $(this).closest('td').toggleClass('locked', locked);
            this.title = locked ? 'Turn on ' + label + ' for ' + role + ' first' : role + ': ' + this.dataset.name;
        });
        var waiting = waitingIn(slug, roleId), active = $toggles.filter('.on').length - waiting;
        var words = [(active || on) ? active + ' of ' + $toggles.length : '', waiting ? waiting + ' waiting' : ''];
        $row.find('.mod-count[data-role="' + roleId + '"]').toggleClass('waiting', waiting > 0)
            .text(words.filter(Boolean).join(' · '));
    }

    // Ticked inside a module that is off for the role, and needing it.
    function waitingIn(slug, roleId) {
        var $sw = switchOf(slug, roleId);
        if (!$sw.length || $sw.hasClass('on')) return 0;
        return insideRows(slug).find('.mx-toggle.on[data-role="' + roleId + '"]').filter(function () { return !this.dataset.alone; }).length;
    }

    // A module on or off for a role. On opens it: the next step is to tick what the role may do inside.
    function switchModule($sw, on) {
        var slug = $sw.data('module'), roleId = $sw.data('role');
        $sw.addClass('busy');
        return setGrant(roleId, $sw.data('perm'), on).done(function (res) {
            $sw.toggleClass('on', res.granted).attr('aria-checked', res.granted ? 'true' : 'false');
            refreshModule(slug, roleId);
            var inside = insideRows(slug).length > 0;
            if (res.granted && inside) openModule(slug, true);
            report(roleId, res, $sw.data('mine') == 1, res.granted && inside ? 'Now tick what this role may do inside it.' : '');
        }).fail(failed).always(function () { $sw.removeClass('busy'); });
    }

    function allow($t, on) {
        var slug = $t.closest('tr').data('module'), roleId = $t.data('role');
        $t.addClass('busy');
        return setGrant(roleId, $t.data('perm'), on).done(function (res) {
            $t.toggleClass('on', res.granted).attr('aria-pressed', res.granted ? 'true' : 'false');
            refreshModule(slug, roleId);
            report(roleId, res, $t.data('mine') == 1);
        }).fail(failed).always(function () { $t.removeClass('busy'); });
    }

    // The module first, then the permission inside it: one action, one message.
    function turnOnAndAllow($sw, $t) {
        var slug = $sw.data('module'), roleId = $sw.data('role');
        $sw.addClass('busy'); $t.addClass('busy');
        setGrant(roleId, $sw.data('perm'), true).then(function (res) {
            $sw.toggleClass('on', res.granted).attr('aria-checked', res.granted ? 'true' : 'false');
            return setGrant(roleId, $t.data('perm'), true);
        }).done(function (res) {
            $t.toggleClass('on', res.granted).attr('aria-pressed', res.granted ? 'true' : 'false');
            report(roleId, res, $t.data('mine') == 1, moduleRow(slug).data('label') + ' is on for this role now too.');
        }).fail(failed).always(function () {
            refreshModule(slug, roleId);
            $sw.removeClass('busy'); $t.removeClass('busy');
        });
    }

    $(document).on('click', '.matrix .mx-switch', function () {
        var $sw = $(this), slug = $sw.data('module'), roleId = $sw.data('role');
        if (!$sw.hasClass('on')) return switchModule($sw, true);
        var ticked = insideRows(slug).find('.mx-toggle.on[data-role="' + roleId + '"]').filter(function () { return !this.dataset.alone; }).length;
        if (!ticked) return switchModule($sw, false);
        Swal.fire({
            title: 'Turn off ' + moduleRow(slug).data('label') + ' for ' + roleNames[roleId] + '?',
            text: (ticked === 1 ? 'The 1 permission ticked inside stops' : 'The ' + ticked + ' permissions ticked inside stop')
                + ' working until you turn it back on. They stay ticked.',
            icon: 'warning', showCancelButton: true, reverseButtons: true, focusCancel: true,
            confirmButtonText: 'Turn off', confirmButtonColor: '#dc3545', cancelButtonText: 'Cancel', cancelButtonColor: neutral
        }).then(function (r) { if (r.isConfirmed) switchModule($sw, false); });
    });

    // Inside a module: a plain tick, unless the role's module is off (then say so, and offer to turn it on).
    $(document).on('click', '.matrix .mx-perm .mx-toggle', function () {
        var $t = $(this), slug = $t.closest('tr').data('module'), roleId = $t.data('role');
        if (!$t.closest('td').hasClass('locked')) return allow($t, !$t.hasClass('on'));
        var $sw = switchOf(slug, roleId), label = moduleRow(slug).data('label'), role = roleNames[roleId], name = $t.data('name');
        if (!$t.hasClass('on')) {
            Swal.fire({
                title: label + ' is off for ' + role,
                text: 'Nothing inside it works for this role yet. Turn ' + label + ' on, and allow “' + name + '”?',
                icon: 'question', showCancelButton: true, reverseButtons: true,
                confirmButtonText: 'Turn on and allow', confirmButtonColor: 'var(--t-primary)', cancelButtonText: 'Cancel', cancelButtonColor: neutral
            }).then(function (r) { if (r.isConfirmed) turnOnAndAllow($sw, $t); });
            return;
        }
        Swal.fire({
            title: '“' + name + '” is waiting',
            text: 'It is ticked, but ' + label + ' is off for ' + role + ', so it does nothing yet.',
            icon: 'info', showCancelButton: true, showDenyButton: true, reverseButtons: true,
            confirmButtonText: 'Turn ' + label + ' on', confirmButtonColor: 'var(--t-primary)',
            denyButtonText: 'Remove the tick', denyButtonColor: '#dc3545', cancelButtonText: 'Cancel', cancelButtonColor: neutral
        }).then(function (r) { if (r.isConfirmed) switchModule($sw, true); else if (r.isDenied) allow($t, false); });
    });

    // Open and close modules; the ones left open are remembered on this browser.
    function openModule(slug, open) {
        var $row = moduleRow(slug), $chevron = $row.find('.mod-chevron');
        if (!$chevron.length) return;
        $row.toggleClass('open', open);
        $chevron.attr('aria-expanded', open ? 'true' : 'false').attr('title', open ? 'Hide what’s inside' : 'Show what’s inside');
        if (!$('#mxFilter').val()) insideRows(slug).prop('hidden', !open);
        try { localStorage.setItem(OPEN_KEY, JSON.stringify($('tr.mx-module.open').map(function () { return $(this).data('module'); }).get())); } catch (e) {}
    }
    $(document).on('click', '.mod-chevron', function () {
        var slug = $(this).closest('tr').data('module');
        openModule(slug, !moduleRow(slug).hasClass('open'));
    });
    $('#mxOpenAll, #mxCloseAll').on('click', function () {
        var open = this.id === 'mxOpenAll';
        $('tr.mx-module').each(function () { openModule($(this).data('module'), open); });
    });
    try { (JSON.parse(localStorage.getItem(OPEN_KEY) || '[]') || []).forEach(function (slug) { openModule(slug, true); }); } catch (e) {}

    // Find a permission: shows the modules and permissions that match, whatever is open.
    $('#mxFilter').on('input', function () {
        var q = $.trim(this.value).toLowerCase(), found = false;
        $('tr.mx-module').each(function () {
            var $row = $(this), $inside = insideRows($row.data('module'));
            if (!q) { $row.prop('hidden', false); $inside.prop('hidden', !$row.hasClass('open')); return; }
            var hit = function ($el) { return $el.children('.perm-cell').text().toLowerCase().indexOf(q) >= 0; };
            // Matching permissions first; a module that matches only by its own words shows all it holds.
            var all = hit($row), $hits = $inside.filter(function () { return hit($(this)); });
            $row.prop('hidden', !all && !$hits.length);
            $inside.prop('hidden', true);
            ($hits.length ? $hits : (all ? $inside : $())).prop('hidden', false);
            found = found || all || $hits.length > 0;
        });
        $('tr.mx-none').prop('hidden', !q || found);
    });

    // ---- Edit role (metadata only) ----
    $(document).on('click', '.edit-role-btn', function () {
        var d = $(this).data();
        $('#roleIdInput').val(d.id);
        $('#roleNameInput').val(d.name);
        $('#editDisplayNameInput').val(d.display || '');
        $('#editDescriptionInput').val(d.desc || '');
        if (window.feather) feather.replace();
        $('#editRoledModal').modal('show');
    });

    // ---- Delete role ----
    $(document).on('click', '.delete-role-btn', function () {
        var id = $(this).data('id'), name = $(this).data('name');
        Swal.fire({
            title: 'Delete "' + name + '" role?',
            text: 'Users with this role lose its access.',
            icon: 'warning', showCancelButton: true,
            confirmButtonText: 'Yes, delete', cancelButtonText: 'No', reverseButtons: true
        }).then(function (result) {
            if (!result.value) return;
            var act = "{{ route('admin.delete_role', ['id' => ':id']) }}".replace(':id', id);
            $('#frm_delete').prop('action', act).submit();
        });
    });
});

function viewCreateRoleModal() {
    $('#addRoledModal').modal('show');
}

function showSwal(type, message, icon) {
    Swal.fire({ position: 'top-end', toast: true, icon: icon, title: message, showConfirmButton: false, timer: 2000 });
}
</script>
@if (session('error'))   <script nonce="{{ cspNonce() }}">$(function(){ Swal.fire({ icon: 'error', title: 'Not done', text: @json(session('error')) }); });</script> @endif
@if (session('success')) <script nonce="{{ cspNonce() }}">$(function(){ showSwal('t', "{{ session('success') }}", 'success'); });</script> @endif
@if ($errors->any())     <script nonce="{{ cspNonce() }}">$(function(){ Swal.fire({ icon: 'error', title: 'Not saved', text: @json($errors->first()) }); });</script> @endif
@endsection
