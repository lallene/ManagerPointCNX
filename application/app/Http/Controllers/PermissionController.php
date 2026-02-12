<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
   //     $this->middleware('role:IT');

    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        $permissions = Permission::all();
        return view('configuration.permission.liste', ['titre' => "Liste des Permissions", 'permissions' => $permissions]);
    }

    public function create()
    {
        $permissions = Permission::all();
        return view('configuration.permission.create', ['titre' => "Ajouter une Permission", 'permissions' => $permissions]);
    }

    public function store(Request $request)
    {
        Permission::create(
            [
                'name' => $request->input('guard_name'),
                'guard_name' => 'web'
            ]
        );

        return redirect()->route('permission.index');
    }

    public function edit($id)
    {
        $permission = Permission::find($id);
        return view('configuration.permission.edit', ['titre' => "Modifier une Permission ".$permission->name, 'role' => $permission]);
    }

    public function update(Request $request, $id)
    {
        $permission = Permission::find($id);

        $permission->guard_name = $request->input('guard_name');

        try{
            $permission->save();
        }catch (\Exception $e){
            echo'e';
        }
        return redirect()->route('permission.index');
    }
}
