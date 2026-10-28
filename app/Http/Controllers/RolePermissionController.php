<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    /** Roles the app's own checks depend on: they can't be renamed or deleted. */
    private const SYSTEM_ROLES = ['developer', 'super_admin'];

    /**
     * Permissions defined but not checked anywhere yet — flagged in the matrix so nobody
     * expects ticking them to change anything.
     */
    private const UNUSED_PERMISSIONS = ['edit.catalog.import'];

    /**
     * Module "access" permission each permission depends on. A permission only takes effect
     * when the role also has its module's access permission (that's how the routes + sidebar
     * gate them), so the matrix dims cells that are ticked but can't apply.
     */
    private const MODULE_ACCESS = [
        'Cataloging'         => 'access.cataloging',
        'Circulation'        => 'access.circulation',
        'Accounts & Patrons' => 'access.accounts',
        'Configuration'      => 'access.configuration',
        'Utilities'          => 'access.utilities',
    ];

    /**
     * Permissions listed under a module that work without its access permission, because other modules' pages use
     * them too (their routes need only themselves), and why. The matrix never locks these.
     */
    private const WORKS_ALONE = [
        'manage.patron.status' => 'Inquiries and Transactions use it too, so it works without Accounts & Patrons',
    ];

    /** How each module shows in the matrix: the sidebar's icon for it, and where its pages are. */
    private const MODULE_LOOK = [
        'Dashboard'          => ['grid', 'The dashboard and the notification bell'],
        'Cataloging'         => ['book', 'Create New Record, Batch Import, Record List, On-Hold'],
        'Circulation'        => ['repeat', 'Transactions, Check-Out/Check-In, History'],
        'Accounts & Patrons' => ['users', 'Accounts → Patrons'],
        'Security'           => ['shield', 'Accounts → Signed in now'],
        'Configuration'      => ['settings', 'Administration → Settings'],
        'Utilities'          => ['tool', 'Administration → Utilities: Material Types, MARC Templates, AI Sync Monitor, Theme'],
        'Inquiry'            => ['message-square', 'Inquiries'],
        'Inventory'          => ['package', 'Physical Count'],
        'News'               => ['rss', 'News Article'],
        'Reports'            => ['bar-chart-2', 'Reports'],
    ];

    public function __construct()
    {
        // Access-control administration is restricted to the top roles (matches the
        // sidebar @hasanyrole gate — now enforced at the route too).
        $this->middleware(['auth','admin','role:super_admin|developer']);
    }

    /** Human module label for a permission name (drives the grouped UI + matrix). */
    private function moduleOf(string $name): string
    {
        $map = [
            'access.dashboard' => 'Dashboard', 'access.notifications' => 'Dashboard',
            'access.cataloging' => 'Cataloging', 'view.catalog.list' => 'Cataloging', 'view.catalog.importlist' => 'Cataloging',
            'create.catalog' => 'Cataloging', 'edit.catalog' => 'Cataloging', 'delete.catalog' => 'Cataloging',
            'create.catalog.import' => 'Cataloging', 'edit.catalog.import' => 'Cataloging', 'delete.catalog.import' => 'Cataloging', 'publish-hold.catalog' => 'Cataloging',
            'access.circulation' => 'Circulation', 'access.circulation.transaction' => 'Circulation', 'circulation.reservation.view' => 'Circulation', 'manage.circulation.reservation' => 'Circulation', 'manage.circulation.checkouts' => 'Circulation',
            'access.accounts' => 'Accounts & Patrons', 'create.patron' => 'Accounts & Patrons', 'edit.patron' => 'Accounts & Patrons', 'delete.patron' => 'Accounts & Patrons', 'manage.patron.status' => 'Accounts & Patrons',
            'access.configuration' => 'Configuration', 'access.settings' => 'Configuration', 'manage.ai.mode' => 'Configuration',
            'access.utilities' => 'Utilities', 'view.config.template' => 'Utilities', 'create.config.template' => 'Utilities', 'edit.config.template' => 'Utilities', 'delete.config.template' => 'Utilities',
            'manage.material.types' => 'Utilities', 'access.ai.sync.monitor' => 'Utilities', 'manage.theme' => 'Utilities',
            'access.inquiry' => 'Inquiry', 'access.inventory' => 'Inventory', 'access.news' => 'News', 'access.reports' => 'Reports',
            'access.sessions' => 'Security', 'access.statistics.watch' => 'Security',
        ];
        return $map[$name] ?? 'Other';
    }

    /** All permissions grouped by module, in a sensible display order. */
    private function groupedPermissions(): array
    {
        $order = ['Dashboard','Cataloging','Circulation','Accounts & Patrons','Security','Configuration','Utilities','Inquiry','Inventory','News','Reports','Other'];
        $grouped = [];
        foreach (Permission::orderBy('display_name')->get() as $p) {
            $grouped[$this->moduleOf($p->name)][] = $p;
        }
        // A module's access permission leads its group: nothing under it works without it.
        foreach ($grouped as $m => $ps) {
            $access = self::MODULE_ACCESS[$m] ?? null;
            usort($grouped[$m], fn ($a, $b) => ($b->name === $access) <=> ($a->name === $access));
        }
        $ordered = [];
        foreach ($order as $m) { if (isset($grouped[$m])) $ordered[$m] = $grouped[$m]; }
        foreach ($grouped as $m => $ps) { if (!isset($ordered[$m])) $ordered[$m] = $ps; }
        return $ordered;
    }

    public function rolesIndex()
    {
        // developer (superuser) is excluded — it always has everything and must stay uneditable.
        $roles = Role::with('permissions')->where('name', '!=', 'developer')->orderBy('id')->get();
        $modules = $this->groupedPermissions();

        $rolePerms = [];  // role_id => [permission_id,...] -> ticked matrix cells
        $userCounts = []; // role_id => number of users
        foreach ($roles as $r) {
            $rolePerms[$r->id] = $r->permissions->pluck('id')->all();
            $userCounts[$r->id] = $r->users()->count();
        }

        // The matrix, module by module. A module's switch is its access permission, and what's inside only works
        // while it's on; a module of one permission is just that switch; Dashboard's two stand on their own.
        $sections = [];
        foreach ($modules as $module => $perms) {
            $gate = collect($perms)->firstWhere('name', self::MODULE_ACCESS[$module] ?? null)
                ?? (count($perms) === 1 ? $perms[0] : null);
            [$icon, $where] = self::MODULE_LOOK[$module] ?? ['more-horizontal', ''];
            $sections[] = [
                'name'  => $module,
                'slug'  => Str::slug($module),
                'icon'  => $icon,
                'where' => $where,
                'gate'  => $gate,
                'perms' => array_values(array_filter($perms, fn ($p) => $p !== $gate)),
            ];
        }
        $unused = self::UNUSED_PERMISSIONS;
        $worksAlone = self::WORKS_ALONE;
        $systemRoles = self::SYSTEM_ROLES;
        $myRoleIds = auth()->user()->roles->pluck('id')->all();

        return view('admin.roles', compact('roles', 'sections', 'rolePerms', 'userCounts', 'unused', 'worksAlone', 'systemRoles', 'myRoleIds'));
    }

    /**
     * Grant or revoke a single (role, permission) pair — powers the access matrix. With `granted` it sets that state
     * (the matrix's two-step actions, e.g. turn the module on, then allow a permission inside, so a page left open
     * can't flip a cell someone else changed); without it, it flips what is stored.
     */
    public function toggleRolePermission(Request $request)
    {
        $data = $request->validate([
            'role_id' => 'required|integer',
            'permission_id' => 'required|integer',
            'granted' => 'sometimes|boolean',
        ]);

        $role = Role::find($data['role_id']);
        $permission = Permission::find($data['permission_id']);
        abort_if(!$role || !$permission, 404);
        abort_if($role->name === 'developer', 403); // superuser row is never editable (avoids lockout)

        // Decide from the stored grant (the ticked cell), never from an implied/derived check,
        // so the checkbox always flips exactly what it shows.
        $had = $role->permissions()->whereKey($permission->getKey())->exists();
        $granted = array_key_exists('granted', $data) ? (bool) $data['granted'] : ! $had;
        if ($granted && ! $had) $role->givePermissionTo($permission);
        if (! $granted && $had) $role->revokePermissionTo($permission);

        return response()->json([
            'granted'   => $granted,
            'permCount' => $role->permissions()->count(),
            'message'   => ($granted ? 'Granted' : 'Revoked') . ' “' . ($permission->display_name ?: $permission->name)
                           . '” ' . ($granted ? 'to' : 'from') . ' ' . ($role->display_name ?: $role->name) . '.',
        ]);
    }

    public function storeRole(Request $request)
    {
        $data = $request->validate([
            'role_name'    => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9_]*$/', 'unique:roles,name'],
            'display_name' => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:255'],
        ], [
            'role_name.regex' => 'Role key: lowercase letters, numbers and underscores, starting with a letter (e.g. circulation_staff).',
        ]);

        Role::create([
            'name'         => $data['role_name'],
            'guard_name'   => 'web',
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
        ]);

        return redirect()->route('admin.list_roles')->with('success', 'Role created. Tick its permissions in the Access Matrix.');
    }

    /** Display name + description only: the role key is what the code checks, so it never changes. */
    public function updateRole(Request $request)
    {
        $data = $request->validate([
            'role_id'      => ['required', 'integer'],
            'display_name' => ['required', 'string', 'max:255'],
            'description'  => ['nullable', 'string', 'max:255'],
        ]);

        $role = Role::findOrFail($data['role_id']);
        abort_if($role->name === 'developer', 403);

        // Model update (not a query-builder update) so Spatie's permission cache is refreshed.
        $role->update(['display_name' => $data['display_name'], 'description' => $data['description'] ?? null]);

        return redirect()->route('admin.list_roles')->with('success', 'Role updated.');
    }

    public function destroyRole($id)
    {
        $role = Role::find($id);
        if (! $role) {
            return redirect()->route('admin.list_roles')->with('error', 'Role not found.');
        }
        if (in_array($role->name, self::SYSTEM_ROLES, true)) {
            return redirect()->route('admin.list_roles')->with('error', 'The ' . ($role->display_name ?: $role->name) . ' role is built in and can’t be deleted.');
        }
        $users = $role->users()->count();
        if ($users > 0) {
            return redirect()->route('admin.list_roles')->with('error', 'Move the ' . $users . ' user' . ($users === 1 ? '' : 's') . ' with this role to another role first (Administrators → Change Role).');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.list_roles')->with('success', 'Role deleted.');
    }

    // public function createPermission(Request $request)
    // {
    //     Permission::create(['name' => $request->input('permission_name')]);

    //     return redirect()->route('roles_permissions.index')->with('success', 'Permission created successfully.');
    // }

    // Add methods for updating and deleting roles/permissions if needed

    public function permissionsIndex()
    {
        $modules = $this->groupedPermissions();
        // permission_id => number of roles that have it (usage shown as a chip)
        $roleCounts = DB::table('role_has_permissions')
            ->select('permission_id', DB::raw('count(*) as c'))
            ->groupBy('permission_id')->pluck('c', 'permission_id');

        // Permissions the app checks (grouped into a module) can't be deleted.
        $inUse = collect($modules)->flatten()->filter(fn ($p) => $this->moduleOf($p->name) !== 'Other')->pluck('id')->all();

        return view('admin.permissions', compact('modules', 'roleCounts', 'inUse'));
    }


    /** Developer only (route). The new name only does something once code checks it. */
    public function storePermission(Request $request)
    {
        $data = $request->validate([
            'permission_name' => ['required', 'string', 'max:100', 'regex:/^[a-z][a-z0-9._-]*$/', 'unique:permissions,name'],
            'display_name'    => ['required', 'string', 'max:255'],
            'description'     => ['nullable', 'string', 'max:255'],
        ], [
            'permission_name.regex' => 'Permission key: lowercase letters, numbers, dots, dashes or underscores (e.g. export.catalog).',
        ]);

        Permission::create([
            'name'         => $data['permission_name'],
            'guard_name'   => 'web',
            'display_name' => $data['display_name'],
            'description'  => $data['description'] ?? null,
        ]);

        return redirect()->route('admin.list_permissions')->with('success', 'Permission created.');
    }

    /** Display name + description only: the permission key is referenced by the code. */
    public function updatePermission(Request $request)
    {
        $data = $request->validate([
            'permission_id' => ['required', 'integer'],
            'display_name'  => ['required', 'string', 'max:255'],
            'description'   => ['nullable', 'string', 'max:255'],
        ]);

        Permission::findOrFail($data['permission_id'])
            ->update(['display_name' => $data['display_name'], 'description' => $data['description'] ?? null]);

        return redirect()->route('admin.list_permissions')->with('success', 'Permission updated.');
    }

    /** Developer only (route). Permissions the app checks can't be deleted. */
    public function destroyPermission($id)
    {
        $permission = Permission::find($id);
        if (! $permission) {
            return redirect()->route('admin.list_permissions')->with('error', 'Permission not found.');
        }
        if ($this->moduleOf($permission->name) !== 'Other') {
            return redirect()->route('admin.list_permissions')->with('error', '“' . ($permission->display_name ?: $permission->name) . '” is used by the app and can’t be deleted.');
        }

        $permission->roles()->detach();
        $permission->users()->detach();
        $permission->delete();

        return redirect()->route('admin.list_permissions')->with('success', 'Permission deleted.');
    }
}
