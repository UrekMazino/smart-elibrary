<?php

namespace App\Http\Controllers;

use App;
use Auth;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware(['auth','admin']);
    }
    
    public function rolesIndex()
    {
        $roles = Role::orderBy('id', 'desc')->where('name', '!=', 'developer')->get();
        
        $permissions = Permission::orderBy('id', 'desc')->get();

        return view('admin.roles', compact('roles', 'permissions'));
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
        $roles = Role::orderBy('id', 'desc')->get();

        $permissions = Permission::orderBy('display_name', 'asc')->get();

        return view('admin.permissions', compact('roles', 'permissions'));
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
