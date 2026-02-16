<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\DB;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:IT'); // Seul l'IT devrait gérer la structure des rôles
    }

    public function index(): View
    {
        $roles = Role::all();
        return view('configuration.role.liste', [
            'titre' => "Liste des Profils Utilisateur", 
            'roles' => $roles
        ]);
    }

    public function create(): View
    {
        return view('configuration.role.create', ['titre' => "Ajouter un Profil Utilisateur"]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name'       => 'required|string|max:190|unique:roles,name',
            'guard_name' => 'required|string|max:190',
        ]);

        Role::create($request->only('name', 'guard_name'));

        return redirect()->route('profil.index')->with('success', 'Profil créé avec succès.');
    }

    public function edit(int $id): View
    {
        $role = Role::findOrFail($id);
        return view('configuration.role.edit', [
            'titre' => "Modifier Profil : ".$role->name, 
            'role' => $role
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);

        $request->validate([
            'name'       => 'required|string|max:190|unique:roles,name,' . $id,
            'guard_name' => 'required|string|max:190',
        ]);

        $role->update($request->only('name', 'guard_name'));

        return redirect()->route('profil.index')->with('success', 'Profil mis à jour.');
    }

    /**
     * Affiche les permissions liées au rôle
     */
    public function permissions(int $id): View
    {
        $role = Role::with('permissions')->findOrFail($id);
        return view('configuration.role.listePermission', [
            'titre'       => "Permissions du Profil " . $role->name, 
            'permissions' => $role->permissions, 
            'role'        => $role
        ]);
    }

    /**
     * Interface de gestion des checkboxes (Optimisée)
     */
    public function addPermission(int $id): View
    {
        $role = Role::findOrFail($id);
        $permissions = Permission::all();
        
        // On récupère les IDs des permissions déjà possédées pour une recherche ultra-rapide
        $rolePermissionIds = $role->permissions->pluck('id')->toArray();

        return view('configuration.role.ajouterPermission', [
            'titre' => "Gérer les accès : " . $role->name, 
            'role' => $role, 
            'permissions' => $permissions,
            'rolePermissionIds' => $rolePermissionIds // À utiliser dans le Blade avec in_array()
        ]);
    }

    /**
     * Mise à jour massive des permissions (Sync)
     */
    public function grantPermission(Request $request, int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        
        // On filtre toutes les clés de la requête qui commencent par 'role_'
        // et on extrait l'ID de la permission
        $permissionIds = collect($request->all())
            ->filter(fn($value, $key) => str_starts_with($key, 'role_') && $value === 'on')
            ->map(fn($value, $key) => (int) str_replace('role_', '', $key))
            ->values()
            ->toArray();

        // syncPermissions est la méthode magique de Spatie : 
        // elle ajoute les nouvelles et retire celles qui ne sont plus cochées en UNE SEULE FOIS.
        $role->syncPermissions($permissionIds);

        return redirect()->route('profil.permissions', $id)->with('success', 'Droits mis à jour avec succès.');
    }

    public function destroy(int $id): RedirectResponse
    {
        $role = Role::findOrFail($id);
        
        // Vérification de sécurité via la table pivot de Spatie
        $userCount = DB::table('model_has_roles')->where('role_id', $id)->count();
        
        if ($userCount > 0) {
            return redirect()->back()->with('error', "Action impossible : ce profil est assigné à $userCount utilisateur(s).");
        }

        $role->delete();
        return redirect()->route('profil.index')->with('success', 'Profil supprimé.');
    }
}