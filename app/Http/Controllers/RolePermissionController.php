<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
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
            'access.configuration' => 'Configuration', 'view.config.template' => 'Configuration', 'create.config.template' => 'Configuration', 'edit.config.template' => 'Configuration', 'delete.config.template' => 'Configuration', 'manage.ai.mode' => 'Configuration',
            'access.inquiry' => 'Inquiry', 'access.inventory' => 'Inventory', 'access.news' => 'News', 'access.reports' => 'Reports',
        ];
        return $map[$name] ?? 'Other';
    }

    /** All permissions grouped by module, in a sensible display order. */
    private function groupedPermissions(): array
    {
        $order = ['Dashboard','Cataloging','Circulation','Accounts & Patrons','Configuration','Inquiry','Inventory','News','Reports','Other'];
        $grouped = [];
        foreach (Permission::orderBy('display_name')->get() as $p) {
            $grouped[$this->moduleOf($p->name)][] = $p;
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

        return view('admin.roles', compact('roles', 'modules', 'rolePerms', 'userCounts'));
    }

    /** Grant or revoke a single (role, permission) pair — powers the access matrix. */
    public function toggleRolePermission(Request $request)
    {
        $data = $request->validate([
            'role_id' => 'required|integer',
            'permission_id' => 'required|integer',
        ]);

        $role = Role::find($data['role_id']);
        $permission = Permission::find($data['permission_id']);
        abort_if(!$role || !$permission, 404);
        abort_if($role->name === 'developer', 403); // superuser row is never editable (avoids lockout)

        if ($role->hasPermissionTo($permission)) {
            $role->revokePermissionTo($permission);
            $granted = false;
        } else {
            $role->givePermissionTo($permission);
            $granted = true;
        }

        return response()->json(['granted' => $granted, 'permCount' => $role->permissions()->count()]);
    }

    public function rolePermissions(Request $request)
    {
        $draw = isset($request->search_title) ? 1 : $request->get('draw');

        $role = Role::where('id', $request->role_id)->first();

        $totalRecords = count($role->permissions);
    
        $totalRecordswithFilter = count($role->permissions);
    
        $records = $role->permissions;

            $resultArr = [];

            foreach($records as $record) {
                $resultArr[] = [
                    'id' => $record->id,
                    'name' => $record->name,
                    'display_name' => $record->display_name,
                    'description' => $record->description,
                ];
            }

         $response = array(
            "draw" => intval($draw),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $resultArr,
         );
 
         return response()->json($response); 
    }


    public function addPermissionToRole(Request $request)
    {
        $role = Role::where('id', $request->role_id)->first(); 

        $permission = Permission::where('id', $request->permission_id)->first();
        
        $role->givePermissionTo($permission->id);

        $totalRecords = count($role->permissions);
    
        $totalRecordswithFilter = count($role->permissions);
    
        $records = $role->permissions;

            $resultArr = [];

            foreach($records as $record) {
                $resultArr[] = [
                    'id' => $record->id,
                    'name' => $record->name,
                    'display_name' => $record->display_name,
                    'description' => $record->description,
                ];
            }

         $response = array(
            "draw" => intval(1),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $resultArr,
         );
 
         return response()->json($response);
    }

    public function removePermissionFromRole(Request $request)
    {


        $role = Role::where('id', $request->role_id)->first(); 
   
        $role->revokePermissionTo($request->permission_name);

        $totalRecords = count($role->permissions);
    
        $totalRecordswithFilter = count($role->permissions);
    
        $records = $role->permissions;

            $resultArr = [];

            foreach($records as $record) {
                $resultArr[] = [
                    'id' => $record->id,
                    'name' => $record->name,
                    'display_name' => $record->display_name,
                    'description' => $record->description,
                ];
            }

        $response = array(
            "draw" => intval(1),
            "iTotalRecords" => $totalRecords,
            "iTotalDisplayRecords" => $totalRecordswithFilter,
            "aaData" => $resultArr,
         );
 
         return response()->json($response);
    }

    public function storeRole(Request $request)
    {
        Role::create([
            'name' => $request->role_name,
            'display_name' => $request->display_name,
            'description' => $request->description
        ]);

        return redirect()->route('admin.list_roles')->with('success', 'Role created successfully.');
    }

    public function updateRole(Request $request)
    {
        Role::where('id', $request->role_id)->update([
            'name' => $request->role_name,
            'display_name' => $request->display_name,
            'description' => $request->description
        ]);

        return redirect()->route('admin.list_roles')->with('success', 'Role updated successfully.');
    }
    
    public function destroyRole($id)
    {   
        // Find the role by name
        $role = Role::findById ($id);

        // Alternatively, find the role by ID
        // $role = Role::findById($roleId);

        if ($role) {
            // Delete the role
            $role->delete();

            // Optionally, you can also remove the role from all associated users
            $role->users()->detach();

            // Optionally, you can also remove all permissions associated with the role
            $role->permissions()->detach();

            // Output a success message
            return redirect()->route('admin.list_roles')->with('success', 'Role deleted successfully.');
        } else {
            // Output an error message if the role is not found
            return redirect()->route('admin.list_roles')->with('error', 'Role not found.');
        }
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

        return view('admin.permissions', compact('modules', 'roleCounts'));
    }


    public function storePermission(Request $request)
    {
        Permission::create([
            'name' => $request->permission_name,
            'display_name' => $request->display_name,
            'description' => $request->description
        ]);

        return redirect()->route('admin.list_permissions')->with('success', 'Permission created successfully.');
    }

    public function updatePermission(Request $request)
    {
        Permission::where('id', $request->permission_id)->update([
            'name' => $request->permission_name,
            'display_name' => $request->display_name,
            'description' => $request->description
        ]);

        return redirect()->route('admin.list_permissions')->with('success', 'Permission updated successfully.');
    }
    
    public function destroyPermission($id)
    {   
        // Find the permission by name
        $permission = Permission::findById ($id);

        if ($permission) {
            // Delete the permission
            $permission->delete();

            // Optionally, you can also remove the permission from all associated users
            $permission->users()->detach();

            // Optionally, you can also remove all permissions associated with the permission
            $permission->roles()->detach();

            // Output a success message
            return redirect()->route('admin.list_permissions')->with('success', 'Permission deleted successfully.');
        } else {
            // Output an error message if the permission is not found
            return redirect()->route('admin.list_permissions')->with('error', 'Permission not found.');
        }
    }

}
