<?php

namespace App\Http\Controllers;

use App\Imports\AgentsImport;
use App\Models\Agent;
use App\Models\Contrat;
use App\Models\Emploi;
use App\Models\Projet;
use App\Models\Societe;
use App\Models\Sub_fonction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\DataTables;
use Illuminate\Support\Facades\Auth;

class AgentController extends Controller
{
    private string $templatePath = 'configuration.effectif';
    private string $link = 'effectif';

    
public function index(Request $request): View
{
    $user = Auth::user();
    $titre = 'Liste des collaborateurs';

    // Accès complet pour le Board
    $isFullAccess = ($user->work_email === 'admin@concentrix.com') || 
                    $user->hasAnyRole(['IT', 'RH', 'Directeur']);

    $userProjectIds = [];
    if (!$isFullAccess) {
        // Sécurité : On passe par l'agent rattaché pour avoir les IDs réels de la table pivot
        $userProjectIds = $user->agent ? $user->agent->projets->pluck('id')->toArray() : [];
        $titre = 'Mes Collaborateurs (Projets rattachés)';
    }

    return view($this->templatePath . '.index', compact('titre', 'isFullAccess', 'userProjectIds'));
}
    public function ajax(Request $request)
{
    $user = Auth::user();

    $isFullAccess = ($user->work_email === 'admin@concentrix.com') || 
                    $user->hasAnyRole(['IT', 'RH', 'Directeur']);

    // Eager loading pour éviter le N+1
    $query = Agent::with(['projets.site', 'supervisor'])->select('agents.*');

    if (!$isFullAccess) {
        // On récupère les IDs via l'agent lié au User
        $userProjectIds = $user->agent ? $user->agent->projets->pluck('id')->toArray() : [0];
        
        $query->whereHas('projets', function($q) use ($userProjectIds) {
            $q->whereIn('projets.id', $userProjectIds);
        });
    }

    return DataTables::of($query)
        // Colonne SITE
        ->addColumn('site', function($agent) {
            // On récupère les désignations des sites via les projets de l'agent
            $siteNames = $agent->projets->map(function($p) {
                return $p->site ? $p->site->designation : null;
            })->filter()->unique();

            if ($siteNames->isEmpty()) return '<span class="text-muted">-</span>';
            
            return $siteNames->map(function($name) {
                return '<div class="site-badge">' . e($name) . '</div>';
            })->implode(' ');
        })

        // Colonne PROJET
        ->addColumn('projet', function($agent) {
            if ($agent->projets->isEmpty()) return '<span class="text-muted small">Hors projet</span>';
            
            return $agent->projets->map(function($p) {
                return '<span class="badge badge-projet mb-1">' . e($p->designation) . '</span>';
            })->implode(' ');
        })

        // Colonne MANAGER
        ->addColumn('manager_nom', function($agent) {
            if ($agent->supervisor) {
                return '<strong>' . strtoupper(e($agent->supervisor->nom)) . '</strong> ' . e($agent->supervisor->prenom);
            }
            return '<span class="badge bg-light text-secondary border small">DIRECTION</span>';
        })

        // Filtre de recherche sur les projets
        ->filterColumn('projet', function($query, $keyword) {
            $query->whereHas('projets', function($q) use ($keyword) {
                $q->where('designation', 'like', "%{$keyword}%");
            });
        })

        ->rawColumns(['site', 'projet', 'manager_nom']) 
        ->make(true);
}
    public function create(): View
    {
        $projets = Projet::all();
        $fonctions = Agent::distinct()->pluck('fonction');
        return view($this->templatePath . '.create', compact('projets', 'fonctions'));
    }
    public function store(Request $request): RedirectResponse
    {
        $roleMapping = [
                "DIRECTEUR D'ACTIVITE" => "Top Manager",
                "DIRECTEUR RH FILIALE" => "RH",
                "HR BUSINESS PARTNER" => "RH",
                'SUPERVISEUR SENIOR' => "Top Manager",
                'ASSISTANT(E) RH' => "RH",
                "RESPONSABLE D'ACTIVITE" => "Top Manager",
                'FORMATEUR METIER' => "Manager",
                'CHEF DE PROJETS' => "Top Manager",
                'Supervisor Quality, Trainee (Non Agent)' => "Manager",
                'CONTROLEUR QUALITE PRODUCTION' => "Manager",
                'TECHNICIEN INFORMATIQUE' => "IT",
                'RESPONSABLE TECHNIQUE SITES' => "IT",
                'INGENIEUR QUALITE FORMATION' => "Manager",
                'Formateur Métier Senior' => "Top Manager",
                'EXPERT METIER' => "Manager",
                'Contrôleur Qualité Mission' => "Manager",
                'LOCAL IT TEAM LEADER' => "IT",
                'FORMATEUR METIER SENIOR' => "Top Manager",
                'Quality Lead' => "Manager",
                'DIRECTEUR DE SITE' => "Directeur",
                'Team Leader Trainee, Operations' => "Manager",
                'Superviseur' => "Manager",
                'Expert Produit Mission' => "Manager",
                'Trainer II' => "Manager",
                'CHEF DE PROJETS AMEL CONT SITE' => "Manager",
                'CHEF DE PROJETS RH' => "RH",
                'People Solutions Generalist I' => "RH",
                'RESPONSABLE QUALITE FORMATION' => "Top Manager",
                'Sr. Quality Evaluator' => "Manager",
                'Responsable opération RH' => "RH",
                'Superviseur Senior (mission)' => "Manager",
                'Trainer I' => "Manager",
                'Contrôleur Qualité Production (mission)' => "Manager",
                'Team Leader, Operations' => "Manager",
                'Quality Evaluator - Trainee (Agent)' => "Manager",
                'Contrôleur Qualité (mission)' => "Manager",
                'Expert Produit' => "Manager",
                'CONTROLEUR QUALITE' => "Manager",
                'Representative, People Solutions' => "RH",
                'ASSISTANTE RH' => "RH",
                'SUBSIDIARIES CEO' => "Directeur",
            ];

        try {
            DB::transaction(function () use ($request, $roleMapping) {
                // 1. Gestion de l'Agent avec updateOrCreate (plus propre)
                $agent = Agent::updateOrCreate(
                    ['workday_id' => $request->workday_id],
                    [
                        'projet_id'  => $request->projet_id,
                        'nom'        => $request->nom,
                        'prenom'     => $request->prenom,
                        'work_email' => $request->work_email, // nullable par défaut via fillable
                        'fonction'   => $request->fonction,
                        'manager'    => $request->workday_id_manager,
                    ]
                );

                // 2. Gestion de l'Utilisateur (User)
                if (!empty($request->work_email)) {
                    $user = User::updateOrCreate(
                        ['work_email' => $request->work_email],
                        [
                            'name'     => trim($request->prenom . ' ' . $request->nom),
                            // On ne change le password que si c'est une création
                            'password' => Hash::make($request->workday_id), 
                            'password_first_connection' => true,
                        ]
                    );

                    // 3. Synchronisation du rôle (Crucial !)
                    $fonction = $request->fonction;
                    $roleToAssign = $roleMapping[$fonction] ?? 'Agent'; // Rôle par défaut

                    // syncRoles remplace l'ancien rôle par le nouveau (Sécurité ACL)
                    $user->syncRoles([$roleToAssign]);
                    
                    Log::info("User access updated for: {$user->work_email} with role: {$roleToAssign}");
                }
            });

            return redirect()->route('effectifs')->with('success', 'Agent et accès mis à jour avec succès.');

        } catch (\Exception $e) {
            Log::error('Erreur lors du store agent/user : ' . $e->getMessage());
            return back()->withInput()->with('error', 'Une erreur technique est survenue.');
        }
    }
   
    public function update(Request $request, $id): RedirectResponse
    {
        // 1. Récupération de l'agent
        $item = Agent::findOrFail($id);
        
        try {
            // 2. Mise à jour des données de la table 'agents'
            // On exclut 'projet_ids' car il appartient à la table pivot, pas à 'agents'
            $item->update($request->except('projet_ids'));

            // 3. SYNCHRONISATION de la table pivot
            // sync() va supprimer les anciens liens et créer les nouveaux automatiquement
            // On passe un tableau vide si aucun projet n'est sélectionné
            $projetIds = $request->input('projet_ids', []);
            $item->projets()->sync($projetIds);

            return redirect()->route('effectif.index')->with('success', 'Mise à jour réussie');

        } catch (\Exception $e) {
            Log::error("Erreur update Agent ID {$id}: " . $e->getMessage());
            return redirect()->back()->withInput()->withErrors("Erreur lors de la modification.");
        }
    }

    /**
     * DataTables Ajax : Affichage des projets multiples

    /**
     * Formulaire d'édition
     */
    public function edit($id)
    {
        $agent = Agent::with('projets')->findOrFail($id);
        $projetsList = Projet::orderBy('designation', 'asc')->get();
        $sites = Projet::distinct()->whereNotNull('site_id')->pluck('site_id');

        return view('configuration.effectif.edit', compact('agent', 'projetsList', 'sites'));
    }
    public function import(Request $req): RedirectResponse
    {

       
        $req->validate(['agent_file' => 'required|mimes:xlsx,xls,csv']);
        Excel::import(new AgentsImport, $req->file('agent_file'));
        return redirect()->route('effectifs')->with('success', 'Importation terminée.');
    }

    public function liste(): View
    {
        $agents = DB::table('agents')
            ->join('projets', 'agents.projet_id', '=', 'projets.id')
            ->leftJoin('agents as managers', 'agents.manager', '=', 'managers.workday_id')
            ->whereNotNull('agents.workday_id')
            ->select(
                'agents.id',
                'projets.site_id as site',
                'agents.workday_id',
                'agents.nom',
                'agents.prenom',
                'agents.fonction',
                'agents.work_email',
                'projets.designation as projet',
                DB::raw("COALESCE(CONCAT(managers.prenom, ' ', managers.nom), agents.manager) as manager_nom")
            );

        return view($this->templatePath . '.liste', [
            'titre' => 'Liste des collaborateurs',
            'agents' => $agents,
            'link' => $this->link
        ]);
    }

   
    public function destroy($id)
{
    $agent = Agent::findOrFail($id);
    $agent->delete();

    return redirect()->route('effectif.index')->with('success', 'Agent supprimé avec succès.');
}

    
}