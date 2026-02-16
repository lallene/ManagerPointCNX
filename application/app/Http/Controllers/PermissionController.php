<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{

    public function index(): View
    {
        $permissions = Permission::all();
        return view('configuration.permission.liste', [
            'titre' => "Liste des Permissions", 
            'permissions' => $permissions
        ]);
    }

    public function create(): View
    {
        return view('configuration.permission.create', [
            'titre' => "Ajouter une Permission"
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'guard_name' => 'required|string|max:255'
        ]);

        Permission::create($request->only('name', 'guard_name'));

        return redirect()->route('permission.index')->with('success', 'Permission créée !');
    }

    public function edit(int $id): View
    {
        $permission = Permission::findOrFail($id);
        return view('configuration.permission.edit', [
            'titre' => "Modifier la Permission : " . $permission->name, 
            'role' => $permission // Gardé 'role' pour la compatibilité avec ta vue existante
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name,' . $id,
            'guard_name' => 'required|string|max:255',
        ]);

        try {
            $permission = Permission::findOrFail($id);
            $permission->update($request->only('name', 'guard_name'));

            return redirect()->route('permission.index')
                             ->with('success', 'La permission a été mise à jour avec succès.');
        } catch (\Exception $e) {
            return redirect()->back()
                             ->withInput()
                             ->with('error', 'Erreur lors de la mise à jour : ' . $e->getMessage());
        }
    }

    public function destroy(int $id): RedirectResponse
    {
        try {
            $permission = Permission::findOrFail($id);

            // Sécurité Lead Dev : On empêche de supprimer si c'est utilisé
            if ($permission->roles()->count() > 0) {
                 return redirect()->back()->with('error', 'Action annulée : cette permission est liée à des rôles.');
            }

            $permission->delete();
            return redirect()->route('permission.index')
                             ->with('success', 'La permission a été supprimée.');
        } catch (\Exception $e) {
            return redirect()->route('permission.index')
                             ->with('error', 'Erreur lors de la suppression.');
        }
    }
}