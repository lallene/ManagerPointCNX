<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Liste tous les profils (rôles)
     */
    public function index()
    {
        $roles = Role::all();
        return view('configuration.role.liste', [
            'titre' => "Liste des Profils Utilisateur", 
            'roles' => $roles
        ]);
    }

    public function create()
    {
        return view('configuration.role.create', ['titre' => "Ajouter un Profil Utilisateur"]);
    }

    /**
     * Enregistre un nouveau profil
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string|max:190|unique:roles,name',
            'guard_name' => 'required|string|max:190',
        ]);

        try {
            Role::create([
                'name'       => $request->input('name'),
                'guard_name' => $request->input('guard_name'),
            ]);

            return redirect()->route('profil.index')->with('success', 'Profil créé avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()->withInput()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $role = Role::findOrFail($id);
        return view('configuration.role.edit', [
            'titre' => "Modifier Profil : ".$role->name, 
            'role' => $role
        ]);
    }

    /**
     * Met à jour le nom technique ou le guard
     */
    public function update(Request $request, $id)
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:190|unique:roles,name,' . $id,
            'guard_name' => 'required|string|max:190',
        ]);

        try {
            $role->update([
                'name'       => $request->input('name'),
                'guard_name' => $request->input('guard_name'),
            ]);

            return redirect()->route('profil.index')->with('success', 'Profil mis à jour.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur de mise à jour.');
        }
    }

    /**
     * Affiche les permissions actuelles du profil
     * C'est cette méthode qui résout ta 404 sur 'profil/permission/{id}'
     */
    public function permissions($id)
    {
        $role = Role::with('permissions')->findOrFail($id);
        return view('configuration.role.listePermission', [
            'titre'       => "Permissions du Profil " . $role->name, 
            'permissions' => $role->permissions, 
            'role'        => $role
        ]);
    }

    /**
     * Formulaire pour ajouter/modifier les permissions (Checkboxes)
     */
    public function addPermission($id)
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();

        // On récupère les noms des permissions déjà associées à ce rôle dans un tableau
        $rolePermissions = $role->permissions->pluck('name')->toArray();

        foreach ($permissions as $key => $permission) {
            // On vérifie si le nom de la permission est dans le tableau du rôle
            // Cela évite l'appel à hasPermissionTo() qui fait planter la page
            $permissions[$key]->Checked = in_array($permission->name, $rolePermissions) ? 'checked' : '';
        }

        return view('configuration.role.ajouterPermission', [
            'titre' => "Gérer les accès : " . $role->name, 
            'role' => $role, 
            'permissions' => $permissions
        ]);
    }

    /**
     * Traitement massif des permissions (Attribution/Révocation)
     */
    public function grantPermission(Request $request, $id)
{
    $role = Role::findOrFail($id);
    
    // On récupère toutes les permissions existantes en base de données
    $permissions = Permission::all();

    foreach ($permissions as $permission) {
        $inputName = 'role_' . $permission->id;
        
        if ($request->has($inputName) && $request->input($inputName) == 'on') {
            // On donne la permission en utilisant l'objet directement (évite l'erreur de nom)
            $role->givePermissionTo($permission);
        } else {
            // On révoque uniquement si le rôle possède déjà la permission
            if ($role->hasPermissionTo($permission->name)) {
                $role->revokePermissionTo($permission);
            }
        }
    }

    return redirect()->route('profil.permissions', $id)->with('success', 'Droits mis à jour.');
}
    /**
     * Supprime un profil avec vérification de sécurité
     */
    public function destroy($id)
    {
        try {
            $role = Role::findOrFail($id);
            
            // Sécurité : Ne pas supprimer si des agents ont ce profil
            $userCount = DB::table('model_has_roles')->where('role_id', $id)->count();
            if ($userCount > 0) {
                return redirect()->back()->with('error', "Ce profil est utilisé par $userCount agent(s).");
            }

            $role->delete();
            return redirect()->route('profil.index')->with('success', 'Profil supprimé.');
        } catch (\Exception $e) {
            return redirect()->route('profil.index')->with('error', 'Erreur de suppression.');
        }
    }
}