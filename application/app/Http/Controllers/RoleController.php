<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
      //  $this->middleware('role:IT');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $roles = Role::all();
        return view('configuration.role.liste', ['titre' => "Liste des Profils Utilisateur", 'roles' => $roles]);
    }

    public function create()
    {
        $roles = Role::all();
        return view('configuration.role.create', ['titre' => "Ajouter un Profil Utilisateur", 'roles' => $roles]);
    }

    public function store(Request $request)
    {
        Role::create(
            [
                'name' => $request->input('guard_name'),
                'guard_name' => 'web'
            ]
        );

        return redirect()->route('profil.index');
    }

    public function edit($id)
    {
        $role = Role::find($id);
        return view('configuration.role.edit', ['titre' => "Modifier Profil Utilisateur ".$role->name, 'role' => $role]);
    }

    public function update(Request $request, $id)
    {
        $role = Role::find($id);

        $role->guard_name = $request->input('guard_name');

        try{
            $role->save();
        }catch (\Exception $e){
            echo'e';
        }
        return redirect()->route('profil.index');
    }

    public function permissions($id)
    {

        $role = Role::with('permissions')->findOrFail($id);

        $permissions = $role->permissions;

        return view('configuration.role.listePermission', [
            'titre'       => "Liste des Permissions du Profil " . $role->name, 
            'permissions' => $permissions, 
            'role'        => $role
        ]);
    }
    public function addPermission($id){
        $temp = Role::find($id);
        $role = Role::findById($temp->id, $temp->guard_name);

        $x = $role->load('permissions');

        $permissions = Permission::all();

        foreach ($permissions as $key => $permission) {
            //echo('<pre>'); die(print_r($permission));
            if($role->hasPermissionTo($permission->name)){
                $permissions[$key]->Checked = 'checked';
            }else{
                $permissions[$key]->Checked = '';
            }
            //$role->hasPermissionTo('edit articles');
        }

        //echo('<pre>'); die(print_r($permissions));
        return view('configuration.role.ajouterPermission', ['titre' => "Ajouter Permissions du Profil ".$role->name, 'role' => $role, 'permissions' => $permissions]);
    }

    public function grantPermission(Request $request, $id){
        $temp = Role::find($id);
        $role = Role::findByName($temp->name);

        $permissions = Permission::all();

        //echo('<pre>'); die(print_r($role));

        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission->name);
            if(isset($_POST['role_'.$permission->id]) AND $_POST['role_'.$permission->id] == 'on'){
                $role->givePermissionTo($permission->name);
            }
        }
        return redirect('profil/permission/'.$id);
        //echo('<pre>'); die(print_r($_POST));
    }

    public function revoquer($idRole, $idPermission){
        $role = Role::findById($idRole);

        $permission = Permission::findById($idPermission);

        if($role->hasPermissionTo($permission->name)){
            $role->revokePermissionTo($permission->name);
        }

        return redirect()->back()->withInput();
    }

        public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('profil.index')->with('success', 'Rôle supprimé avec succès.');
    }
}
