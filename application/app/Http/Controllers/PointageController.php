<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{Auth, Gate, Log};
use App\Models\{Agent, Pointage, User, Planning, Projet};
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Carbon;


class PointageController extends Controller
{
   

    /**
     * Vue du tableau de bord des pointages.
     */
    public function index(): View
    {
        $sites = Projet::select('site_id')->distinct()->pluck('site_id');
        
        $semaines = [];
        $semaineActuelle = now()->weekOfYear;
        
        // Génération de la plage de semaines (Standard 2026)
        for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
            if ($i < 1 || $i > 53) continue;
            $start = now()->setISODate(now()->year, $i)->startOfWeek();
            $semaines[] = [
                'numero' => $i,
                'debut'  => $start->format('d/m'),
                'label'  => "Semaine $i"
            ];
        }

        return view('pointages.group', [
            'sites' => $sites,
            'semaines' => $semaines,
            'selectedWeek' => $semaineActuelle,
            'selectedSiteId' => null
        ]);
    }

    /**
     * Interface de pointage pour l'agent (Create).
     */
    public function create(): View|RedirectResponse
    {
        $now = now();
        $currentDate = $now->toDateString();
        $user = Auth::user();

        $agent = Agent::where('work_email', $user->work_email)->first();
        if (!$agent) {
            return redirect()->back()->with('error', "Profil agent introuvable.");
        }

        $planningDisponible = Planning::where('agent_id', $agent->id)
            ->whereDate('jour', $currentDate)
            ->first();

        $pointageExistant = Pointage::where('agent_id', $agent->id)
            ->whereDate('date_pointage', $currentDate)
            ->first();

        // Logique d'état (State Machine)
        $prochaineAction = 'debut'; 
        if ($pointageExistant) {
            if (!$pointageExistant->pause_debut) {
                $prochaineAction = 'debutpause';
            } elseif (!$pointageExistant->pause_fin) {
                $prochaineAction = 'finpause';
            } elseif (!$pointageExistant->sortie) {
                $prochaineAction = 'fin';
            } else {
                $prochaineAction = 'termine'; 
            }
        }

        return view('pointages.create', [
            'currentWeek'        => $now->weekOfYear,
            'currentDate'        => $currentDate,
            'currentTime'        => $now->format('H:i'),
            'pointageDuJour'     => $pointageExistant,
            'planningDisponible' => $planningDisponible,
            'prochaineAction'    => $prochaineAction,
            'agent'              => $agent
        ]);
    }

    /**
     * Enregistrement des actions de pointage (Store).
     */
    public function store(Request $request): RedirectResponse
    {
        $agent = Agent::where('work_email', Auth::user()->work_email)->first();
        if (!$agent) return redirect()->back()->with('error', "Profil agent non lié.");

        $now = now();
        $today = $now->toDateString();
        $currentTime = $now->toTimeString(); 
        $currentWeekDB = $now->year . '-' . str_pad($now->weekOfYear, 2, '0', STR_PAD_LEFT);

        $pointage = Pointage::where('agent_id', $agent->id)
            ->whereDate('date_pointage', $today)
            ->first();

        $action = $request->input('action');

        try {
            if ($action === 'debut') {
                if ($pointage) return redirect()->back()->with('error', "Déjà pointé aujourd'hui.");

                $planning = Planning::where('agent_id', $agent->id)->whereDate('jour', $today)->first();

                Pointage::create([
                    'agent_id' => $agent->id,
                    'planning_id' => $planning?->id,
                    'user_id' => Auth::id(),
                    'date_pointage' => $today,
                    'semaine' => $currentWeekDB,
                    'entree' => $currentTime,
                    'sortie' => null,
                    'minutes_travaillees' => 0
                ]);

                return redirect()->back()->with('success', "Entrée enregistrée à $currentTime.");
            }

            if (!$pointage) return redirect()->back()->with('error', "Aucun pointage actif.");

            $updateData = match($action) {
                'debutpause' => ['pause_debut' => $currentTime],
                'finpause'   => ['pause_fin' => $currentTime],
                'fin'        => [
                    'sortie' => $currentTime,
                    'minutes_travaillees' => $pointage->setRelation('agent', $agent)->calculerMinutesEffectives()
                ],
                default      => []
            };

            if (!empty($updateData)) {
                $pointage->update($updateData);
            }

            return redirect()->back()->with('success', "Action enregistrée !");

        } catch (\Exception $e) {
            Log::error("Erreur Pointage Store: " . $e->getMessage());
            return redirect()->back()->with('error', "Erreur BDD : " . $e->getMessage());
        }
    }

    
    public function getPointageData(Request $request): JsonResponse
{
    try {
        $user = Auth::user();
        
        $fullAccessRoles = ['IT', 'RH', 'Directeur', 'Top Manager'];
        $hasFullAccess = $user->hasAnyRole($fullAccessRoles) || ($user->work_email === 'admin@concentrix.com');

        // 1. Gestion des dates (Sécurisée)
        $rawWeek = $request->input('week', now()->format('Y-W')); 
        if (!str_contains($rawWeek, '-')) {
            $year = (int)now()->year;
            $weekNum = (int)$rawWeek;
        } else {
            $parts = explode('-', str_replace('-W', '-', $rawWeek));
            $year = (int)$parts[0];
            $weekNum = (int)($parts[1] ?? now()->weekOfYear);
        }

        $dateDebut = \Carbon\Carbon::now()->setISODate($year, $weekNum)->startOfWeek();
        $dates = [];
        for ($i = 0; $i < 7; $i++) {
            $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d');
        }

        $managerEmails = \DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Manager')
            ->pluck('users.work_email')
            ->toArray();

        // 2. Détermination des projets (Compatible Pivot)
        if ($hasFullAccess) {
            $selectedSiteId = $request->input('site_id');
            $selectedProjetId = $request->input('projet_id');

            $projets = \App\Models\Projet::when($selectedSiteId, fn($q) => $q->where('site_id', $selectedSiteId))
                ->when($selectedProjetId && $selectedProjetId !== 'null', fn($q) => $q->where('id', $selectedProjetId))
                ->get();
        } else {
            // Un utilisateur peut être lié à plusieurs projets
            $agentConnecte = \App\Models\Agent::where('work_email', $user->work_email)->first();
            $projets = $agentConnecte ? $agentConnecte->projets : collect();
        }

        // 3. Récupération des Agents Managers (Utilisation du Scope forProjet)
        $projetIds = $projets->pluck('id');
        
        // On récupère tous les agents qui appartiennent à ces projets
        $agentsManagers = \App\Models\Agent::whereHas('projets', function($q) use ($projetIds) {
                $q->whereIn('projets.id', $projetIds);
            })
            ->whereIn('work_email', $managerEmails)
            ->with('projets') // Eager loading pour éviter les requêtes en boucle
            ->get();

        $agentIds = $agentsManagers->pluck('id');

        // 4. Plannings et Pointages
        $allPlannings = \App\Models\Planning::whereIn('agent_id', $agentIds)
            ->whereBetween('jour', [$dates[0], $dates[6]])
            ->get()
            ->groupBy('agent_id');

        $allPointages = \DB::table('pointages')
            ->whereIn('agent_id', $agentIds)
            ->whereBetween('date_pointage', [$dates[0], $dates[6]])
            ->get()
            ->groupBy('agent_id');

        $resultat = [];

        foreach ($projets as $projet) {
            $superviseurs = [];
            
            // Filtre les agents qui possèdent ce projet dans leur collection 'projets'
            $agentsDuProjet = $agentsManagers->filter(function($agent) use ($projet) {
                return $agent->projets->contains('id', $projet->id);
            });

            foreach ($agentsDuProjet as $agent) {
                $stats = [];
                $agentPlannings = $allPlannings->get($agent->id) ?? collect();
                $agentPointages = $allPointages->get($agent->id) ?? collect();

                foreach ($dates as $date) {
                    $p = $agentPlannings->first(fn($v) => \Carbon\Carbon::parse($v->jour)->format('Y-m-d') === $date);
                    $pt = $agentPointages->first(fn($v) => $v->date_pointage === $date);

                    $stats[$date] = [
                        'p_in'  => ($p && $p->entree) ? date('H:i', strtotime($p->entree)) : null,
                        'p_out' => ($p && $p->sortie) ? date('H:i', strtotime($p->sortie)) : null,
                        'a_in'  => ($pt && $pt->entree) ? date('H:i', strtotime($pt->entree)) : null,
                        'a_out' => ($pt && $pt->sortie) ? date('H:i', strtotime($pt->sortie)) : null,
                    ];
                }

                $superviseurs[] = [
                    'nom'      => "{$agent->prenom} {$agent->nom}",
                    'fonction' => $agent->fonction ?? 'MANAGER',
                    'stats'    => $stats
                ];
            }

            if (count($superviseurs) > 0) {
                $resultat[] = [
                    'projet'       => $projet->designation,
                    'superviseurs' => $superviseurs
                ];
            }
        }

        return response()->json([
            'dates'    => $dates,
            'resultat' => $resultat
        ]);

    } catch (\Exception $e) {
        \Log::error("Erreur Pointage : " . $e->getMessage());
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    

    public function getProjetsBySite(Request $request): JsonResponse
    {
        $projets = Projet::where('site_id', $request->site_id)->orderBy('designation')->get();
        return response()->json($projets);
    }
}

