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
        $request->validate([
            'name' => 'required|unique:permissions,name',
            'guard_name' => 'required'
        ]);

        Permission::create([
            'name' => $request->name,
            'guard_name' => $request->guard_name
        ]);

        return redirect()->route('permission.index')->with('success', 'Permission créée !');
    }

    public function edit($id)
    {
        $permission = Permission::find($id);
        return view('configuration.permission.edit', ['titre' => "Modifier une Permission ".$permission->name, 'role' => $permission]);
    }

        public function update(Request $request, $id)
    {
        // 1. Validation des données (Crucial pour la sécurité)
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'guard_name' => 'required|string|max:255',
        ]);

        // 2. Récupération de l'instance
        $permission = Permission::findOrFail($id);

        try {
            // 3. Mise à jour de tous les champs
            $permission->name = $request->input('name');
            $permission->guard_name = $request->input('guard_name');
            $permission->save();

            // 4. Feedback positif (Notification)
            return redirect()->route('permission.index')
                            ->with('success', 'La permission a été mise à jour avec succès.');

        } catch (\Exception $e) {
            // 5. Gestion d'erreur propre
            return redirect()->back()
                            ->withInput()
                            ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }


    /**
 * Supprime une permission de la base de données.
 */
public function destroy($id)
{
    try {
        // 1. On récupère la permission ou on renvoie une erreur 404 si elle n'existe pas
        $permission = \Spatie\Permission\Models\Permission::findOrFail($id);

        // 2. Sécurité : On peut vérifier si la permission est liée à des rôles avant de supprimer
        // if ($permission->roles()->count() > 0) {
        //    return redirect()->back()->with('error', 'Impossible de supprimer : cette permission est utilisée par des rôles.');
        // }

        // 3. Suppression
        $permission->delete();

        // 4. Retour avec message de succès
        return redirect()->route('permission.index')
                         ->with('success', 'La permission a été supprimée avec succès.');

    } catch (\Exception $e) {
        // En cas d'erreur imprévue
        return redirect()->route('permission.index')
                         ->with('error', 'Erreur lors de la suppression : ' . $e->getMessage());
    }
}
}
