<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Hash, Auth, DB, Log};
use Spatie\Permission\Models\Role;
use Yajra\DataTables\DataTables;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class UtilisateurController extends Controller
{
  

    public function index(): View
    {
        return view('configuration.users.liste', ['titre' => 'Liste des utilisateurs']);
    }

    public function create(): View
    {
        return view('configuration.users.create', [
            'titre' => 'Créer un Utilisateur',
            'roles' => Role::all(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'work_email' => 'required|email|unique:users,work_email|max:255',
            'password_first_connection' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'work_email' => $request->work_email,
            'password' => Hash::make($request->password_first_connection),
            'password_first_connection' => true,
        ]);

        $user->assignRole($request->role_id);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    public function edit(int $id): View
    {
        $user = User::findOrFail($id);
        return view('configuration.users.edit', [
            'titre' => 'Modifier Utilisateur',
            'user' => $user,
            'roles' => Role::all(),
        ]);
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'work_email' => 'required|email|unique:users,work_email,' . $id,
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->name = $request->name;
        $user->work_email = $request->work_email;

        if ($request->filled('password_new')) {
            $user->password = Hash::make($request->password_new);
            $user->password_first_connection = true; // On force le reset au prochain login
        }

        $user->save();
        $role = Role::findById($request->role_id, 'web');
        $user->syncRoles($role);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour.');
    }

    public function destroy(int $id): RedirectResponse
{
    $user = User::findOrFail($id);

    if ($user->id === Auth::id()) {
        return redirect()->back()->with('error', "Vous ne pouvez pas supprimer votre propre compte.");
    }

    // 1. Supprimer les dépendances d'abord
    \App\Models\Planning::where('user_id', $user->id)->delete();
    
    // Si tu as une liaison dans la table agents, fais de même :
    // \App\Models\Agent::where('work_email', $user->work_email)->delete();

    // 2. Supprimer l'utilisateur
    $user->delete();

    return redirect()->route('users.index')->with('success', 'Utilisateur et ses données associés ont été supprimés.');
}

    // --- Gestion du premier Mot de passe ---

    public function showChangePasswordForm(): View
    {
        return view('auth.change-password');
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if (!$user->password_first_connection) {
            return redirect()->route('home');
        }

        $user->update([
            'password' => Hash::make($request->password),
            'password_first_connection' => false
        ]);

        return redirect()->route('home')->with('success', 'Mot de passe mis à jour !');
    }

    public function ajax()
{
    // 1. On utilise Eloquent pour charger proprement les relations N-N
    // User -> Agent -> Projets -> Site
    $query = User::with(['agent.projets.site', 'roles']);

    return DataTables::of($query)
        // Correction de la colonne SITE (Unique pour tous les projets rattachés)
        ->addColumn('site_nom', function ($user) {
            if (!$user->agent || $user->agent->projets->isEmpty()) return '-';
            return $user->agent->projets->map(function($p) {
                return $p->site->designation ?? '-';
            })->unique()->implode(', ');
        })

        // Correction de la colonne PROJET (Affiche tous les projets rattachés)
        ->addColumn('projet_nom', function ($user) {
            if (!$user->agent || $user->agent->projets->isEmpty()) {
                return '<span class="text-muted small">Aucun projet</span>';
            }
            return $user->agent->projets->map(function($p) {
                return '<span class="badge bg-info text-dark" style="font-size: 0.7rem; margin-right: 2px;">' 
                    . e($p->designation) . '</span>';
            })->implode('');
        })

        // Colonne FONCTION
        ->addColumn('fonction', function ($user) {
            return $user->agent->fonction ?? '-';
        })

        // Colonne RÔLE (Spatie)
        ->addColumn('role_name', function ($user) {
            return $user->roles->pluck('name')->map(function($role) {
                return '<span class="badge bg-secondary">' . e($role) . '</span>';
            })->implode(' ');
        })

        // Filtrage de la recherche pour les projets (indispensable en Many-to-Many)
        ->filterColumn('projet_nom', function($q, $kw) {
            $q->whereHas('agent.projets', function($sub) use ($kw) {
                $sub->where('designation', 'like', "%$kw%");
            });
        })

        // Boutons d'action
        ->addColumn('action', function ($user) {
            $editUrl = route('users.edit', $user->id);
            $deleteUrl = route('users.destroy', $user->id);
            $csrf = csrf_token();
            return '
                <div class="btn-group shadow-sm">
                    <a href="'.$editUrl.'" class="btn btn-sm btn-outline-primary"><i class="fa fa-edit"></i></a>
                    <form action="'.$deleteUrl.'" method="POST" style="display:inline" onsubmit="return confirm(\'Confirmer la suppression ?\')">
                        <input type="hidden" name="_token" value="'.$csrf.'">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fa fa-trash"></i></button>
                    </form>
                </div>';
        })
        ->rawColumns(['projet_nom', 'role_name', 'action'])
        ->make(true);
}
}