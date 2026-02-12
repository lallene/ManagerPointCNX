<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Spatie\Permission\PermissionRegistrar;

class UtilisateurController extends Controller
{
    /**
     * Constructeur : ajoute des middlewares pour l'authentification et le rôle IT.
     */
    public function __construct()
    {
       // $this->middleware('auth');
       // $this->middleware('role:IT');
    }

    /**
     * Affiche la liste des utilisateurs.
     */
    public function index()
    {



        return view('configuration.users.liste', ['titre' => 'Liste des utilisateurs']);

    }

    /**
     * Affiche le formulaire de création d’un utilisateur.
     */
    public function create()
    {
        $roles = Role::all();

        return view('users.create', [
            'title' => 'Créer un Utilisateur',
            'roles' => $roles,
        ]);
    }

    /**
     * Enregistre un nouvel utilisateur.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|max:255',
            'password_first_connection' => 'required|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make('temporary'), // Mot de passe temporaire
            'password_first_connection' => true,  // Flag pour la première connexion
        ]);

        $user->assignRole($request->role_id);

        return redirect()->route('users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Affiche les détails d’un utilisateur spécifique.
     */
    public function show($id)
    {
        $user = User::findOrFail($id);

        return view('users.show', [
            'title' => 'Détails de l’Utilisateur',
            'user' => $user,
        ]);
    }

    /**
     * Affiche le formulaire d’édition d’un utilisateur.
     */
    public function edit($id)
    {
        $user = User::findOrFail($id);
        $roles = Role::all();

        return view('users.edit', [
            'title' => 'Modifier Utilisateur',
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Met à jour les informations d’un utilisateur.
     */
    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'password_first_connection' => 'nullable|string|min:8',
            'role_id' => 'required|exists:roles,id',
        ]);

        $user->name = $request->name;
        $user->email = $request->email;

        if ($request->filled('password_first_connection')) {
            $user->password_first_connection = Hash::make($request->password_first_connection);
            $user->password = Hash::make($request->password_first_connection);

        }

        $user->save();
        $user->syncRoles([$request->role_id]);

        return redirect()->route('users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Supprime un utilisateur.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return redirect()->route('users.index')->with('success', 'Utilisateur supprimé avec succès.');
    }

    /**
     * Affiche le formulaire pour changer le mot de passe à la première connexion.
     */
    public function showChangePasswordForm()
    {
        return view('auth.change-password');
    }

    /**
     * Met à jour le mot de passe de l'utilisateur lors de la première connexion.
     */
    public function updatePassword(Request $request)
    {


        // Validation des données
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        // Récupérer l'utilisateur authentifié
        $user = User::find(Auth::user()->id);

    //

        // Vérifier que c'est la première connexion
        if (!$user->password_first_connection) {
            return redirect()->route('home')->with('error', 'Vous ne pouvez pas changer votre mot de passe à ce moment.');
        }


        // Mettre à jour le mot de passe
        $user->password = Hash::make($request->password);
        $user->password_first_connection = null;  // Indiquer que le mot de passe a été changé
        $user->save();

        // Rediriger l'utilisateur vers la page d'accueil
        return redirect()->route('home')->with('success', 'Votre mot de passe a été modifié avec succès !');
    }

    /**
     * Gère les permissions d’un rôle.
     */
    public function permissions($id)
    {


        $role = Role::findById($id)->load('permissions');

        return view('configuration.role.listePermission', [
            'titre' => "Liste des Permissions du Profil " . $role->guard_name,
            'permissions' => $role->permissions,
            'role' => $role,
        ]);
    }

    /**
     * Ajoute des permissions à un rôle.
     */
    public function addPermission($id)
    {
        $role = Role::findById($id, 'web');
        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            $permission->Checked = $role->hasPermissionTo($permission->name) ? 'checked' : '';
        }

        return view('configuration.role.ajouterPermission', [
            'titre' => "Ajouter Permissions du Profil " . $role->guard_name,
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Attribue ou révoque des permissions à un rôle.
     */
    public function grantPermission(Request $request, $id)
    {
        $role = Role::findById($id);
        $permissions = Permission::all();

        foreach ($permissions as $permission) {
            $role->revokePermissionTo($permission->name);
            if ($request->has('role_' . $permission->id)) {
                $role->givePermissionTo($permission->name);
            }
        }

        return redirect()->route('roles.permissions', ['id' => $id])->with('success', 'Permissions mises à jour avec succès.');
    }

    /**
     * Révoque une permission spécifique d’un rôle.
     */
    public function revoquer($idRole, $idPermission)
    {
        $role = Role::findById($idRole);
        $permission = Permission::findById($idPermission);

        if ($role->hasPermissionTo($permission->name)) {
            $role->revokePermissionTo($permission->name);
        }

        return redirect()->back();
    }

    public function ajax()
{
    $query = DB::table('users')
        ->join('agents', 'agents.email', '=', 'users.email')
        ->join('projets', 'agents.projet_id', '=', 'projets.id')
        ->join('sites', 'projets.site_id', '=', 'sites.id')
        ->leftJoin('model_has_roles', function ($join) {
            $join->on('model_has_roles.model_id', '=', 'users.id')
                 ->where('model_has_roles.model_type', '=', User::class);
        })
        ->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id')
        ->select([
            'users.id as user_id', // Alias pour l'affichage
            'users.name',
            'users.email',
            'agents.fonction as fonction',
            'roles.name as role',
            'projets.designation as projet',
            'sites.designation as site',
        ]);

    return DataTables::of($query)
        // Correction pour le filtrage de l'ID
        ->filterColumn('user_id', function($query, $keyword) {
            $query->where('users.id', 'like', "%{$keyword}%");
        })
        // Correction pour le filtrage des colonnes avec alias/jointures
        ->filterColumn('role', function($query, $keyword) {
            $query->where('roles.name', 'like', "%{$keyword}%");
        })
        ->filterColumn('projet', function($query, $keyword) {
            $query->where('projets.designation', 'like', "%{$keyword}%");
        })
        ->filterColumn('site', function($query, $keyword) {
            $query->where('sites.designation', 'like', "%{$keyword}%");
        })
        ->addColumn('action', function ($row) {
            $editUrl = route('users.edit', $row->user_id);
            $deleteUrl = route('users.destroy', $row->user_id);
            $csrf = csrf_token();

            return <<<HTML
                <a href="{$editUrl}" class="btn btn-sm btn-primary me-1" title="Modifier">
                    <i class="fa fa-edit"></i>
                </a>
                <form action="{$deleteUrl}" method="POST" style="display:inline;" onsubmit="return confirm('Confirmer la suppression ?')">
                    <input type="hidden" name="_token" value="{$csrf}">
                    <input type="hidden" name="_method" value="DELETE">
                    <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                        <i class="fa fa-trash"></i>
                    </button>
                </form>
            HTML;
        })
        ->rawColumns(['action'])
        ->make(true);
}
}
