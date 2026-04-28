<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\{Auth, DB, Log};
use App\Models\{Agent, Pointage, Planning, Projet, User};
use Illuminate\Http\{Request, JsonResponse, RedirectResponse};
use Illuminate\View\View;
use Illuminate\Support\Carbon;
    use App\Exports\PointageExport;
use Maatwebsite\Excel\Facades\Excel;

class PointageController extends Controller
{
    /**
     * DASHBOARD : Vue du tableau de bord filtrée par périmètre.
     */
    public function index(): View
{
    $user = Auth::user();
    $hasFullAccess = ($user->work_email === 'admin@concentrix.com') || 
                     $user->hasAnyRole(['IT', 'RH', 'Directeur']);
    
    if ($hasFullAccess) {
        $sites = Projet::select('site_id')->distinct()->whereNotNull('site_id')->pluck('site_id');
        $projetsList = Projet::orderBy('designation')->get();
    } else {
        // Récupération de l'agent connecté pour connaître ses sites et projets
        $agentConnecte = Agent::with('projets.site')->where('work_email', $user->work_email)->firstOrFail();
        $mySiteIds = $agentConnecte->projets->pluck('site_id')->unique()->filter();
        
        // Si c'est un rôle "Top", il voit tous les projets de SON site
        if ($user->hasAnyRole(['Top Manager', 'Top Formateur', 'Top CQ', 'Top Superviseur'])) {
            $sites = $mySiteIds;
            $projetsList = Projet::whereIn('site_id', $mySiteIds)->orderBy('designation')->get();
        } else {
            // Manager standard : uniquement ses projets rattachés
            $sites = $mySiteIds;
            $projetsList = $agentConnecte->projets;
        }
    }

    return view('pointages.group', [
        'sites' => $sites,
        'projetsList' => $projetsList,
        'semaines' => $this->generateWeekRange(),
        'selectedWeek' => now()->weekOfYear,
        'isManager' => !$hasFullAccess
    ]);
}


public function getPointageData(Request $request): JsonResponse
{
    try {
        $user = Auth::user();
        $hasFullAccess = ($user->work_email === 'admin@concentrix.com') || 
                         $user->hasAnyRole(['IT', 'RH', 'Directeur']);
        
        // 1. Définition des dates (Année 2026)
        $weekNum = (int)$request->input('week', now()->weekOfYear);
        $dateDebut = now()->setISODate(2026, $weekNum)->startOfWeek(); 
        $dates = [];
        for ($i = 0; $i < 7; $i++) { 
            $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d'); 
        }

        // 2. Récupération des infos du Manager connecté
        $me = Agent::with('projets')->where('work_email', $user->work_email)->first();
        $mySiteIds = $me ? $me->projets->pluck('site_id')->unique()->filter()->toArray() : [];

        // 3. Filtrage des projets (Périmètre)
        $projets = Projet::query()
            ->when(!$hasFullAccess, function($q) use ($user, $mySiteIds) {
                // Top Formateur et Top CQ : voient tout le SITE
                if ($user->hasAnyRole(['Top Formateur', 'Top CQ'])) {
                    $q->whereIn('site_id', $mySiteIds);
                } 
                // Top Superviseur, Top Manager et Standard : voient uniquement leurs PROJETS
                else {
                    $q->whereHas('agents', fn($a) => $a->where('work_email', $user->work_email));
                }
            })
            ->when($request->site_id, fn($q) => $q->where('site_id', $request->site_id))
            ->when($request->projet_id && $request->projet_id !== 'null', fn($q) => $q->where('id', $request->projet_id))
            ->get();

        // 4. Liste des emails avec le rôle 'Manager'
        $managerEmails = DB::table('users')
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->where('roles.name', 'Manager')
            ->pluck('users.work_email')->toArray();

        $resultat = [];
        foreach ($projets as $projet) {
            $queryAgents = Agent::whereHas('projets', fn($q) => $q->where('projets.id', $projet->id))
                                ->whereIn('work_email', $managerEmails);

            // --- FILTRAGE PAR FONCTION (MÉTIER) ---
            if (!$hasFullAccess) {
                if ($user->hasRole('Top Formateur')) {
                    $queryAgents->where('fonction', 'LIKE', 'FORMATEUR%');
                } elseif ($user->hasRole('Top CQ')) {
                    $queryAgents->where('fonction', 'LIKE', 'CONTRÔLEUR%');
                } elseif ($user->hasRole('Top Superviseur')) {
                    $queryAgents->where('fonction', 'LIKE', 'SUPERVISEUR%');
                } elseif ($user->hasRole('Top Manager')) {
                    $queryAgents->where(function($q) {
                        $q->where('fonction', 'LIKE', 'FORMATEUR%')
                          ->orWhere('fonction', 'LIKE', 'CONTRÔLEUR%')
                          ->orWhere('fonction', 'LIKE', 'SUPERVISEUR%');
                    });
                }
            }

            $agentsDuProjet = $queryAgents->get();

            $superviseursData = [];
            foreach ($agentsDuProjet as $agent) {
                $superviseursData[] = [
                    'nom' => "{$agent->prenom} {$agent->nom}",
                    'fonction' => $agent->fonction ?? 'MANAGER',
                    'stats' => $this->mapStats($agent->id, $dates)
                ];
            }

            if (!empty($superviseursData)) {
                $resultat[] = [
                    'projet' => $projet->designation, 
                    'superviseurs' => $superviseursData
                ];
            }
        }

        return response()->json(['dates' => $dates, 'resultat' => $resultat]);

    } catch (\Exception $e) {
        Log::error("Erreur API Pointage Data: " . $e->getMessage());
        return response()->json(['error' => "Erreur interne"], 500);
    }
}

    /**
     * AGENT : Interface de pointage (Check-in/Check-out)
     */
    public function create(): View|RedirectResponse
    {
        $user = Auth::user();
        $agent = Agent::where('work_email', $user->work_email)->first();
        if (!$agent) return redirect()->back()->with('error', "Profil agent introuvable.");

        $today = now()->toDateString();
        $planning = Planning::where('agent_id', $agent->id)->whereDate('jour', $today)->first();
        $pointage = Pointage::where('agent_id', $agent->id)->whereDate('date_pointage', $today)->first();

        // Machine à état pour les boutons
        $prochaineAction = 'debut'; 
        if ($pointage) {
            if (!$pointage->pause_debut) $prochaineAction = 'debutpause';
            elseif (!$pointage->pause_fin) $prochaineAction = 'finpause';
            elseif (!$pointage->sortie) $prochaineAction = 'fin';
            else $prochaineAction = 'termine';
        }

        return view('pointages.create', [
            'currentWeek' => now()->weekOfYear,
            'currentDate' => $today,
            'currentTime' => now()->format('H:i'),
            'pointageDuJour' => $pointage,
            'planningDisponible' => $planning,
            'prochaineAction' => $prochaineAction,
            'agent' => $agent
        ]);
    }

    /**
     * STORE : Enregistre l'action de pointage
     */
    public function store(Request $request): RedirectResponse
    {
        $agent = Agent::where('work_email', Auth::user()->work_email)->first();
        if (!$agent) return redirect()->back()->with('error', "Profil non lié.");

        $now = now();
        $today = $now->toDateString();
        $pointage = Pointage::where('agent_id', $agent->id)->whereDate('date_pointage', $today)->first();
        $action = $request->input('action');

        try {
            if ($action === 'debut') {
                if ($pointage) return redirect()->back()->with('error', "Déjà pointé.");
                $planning = Planning::where('agent_id', $agent->id)->whereDate('jour', $today)->first();

                Pointage::create([
                    'agent_id' => $agent->id,
                    'planning_id' => $planning?->id,
                    'user_id' => Auth::id(),
                    'date_pointage' => $today,
                    'semaine' => $now->year . '-' . str_pad($now->weekOfYear, 2, '0', STR_PAD_LEFT),
                    'entree' => $now->toTimeString(),
                    'minutes_travaillees' => 0
                ]);
                return redirect()->back()->with('success', "Entrée enregistrée.");
            }

            if (!$pointage) return redirect()->back()->with('error', "Aucun pointage actif.");

            $updateData = match($action) {
                'debutpause' => ['pause_debut' => $now->toTimeString()],
                'finpause'   => ['pause_fin' => $now->toTimeString()],
                'fin'        => [
                    'sortie' => $now->toTimeString(),
                    'minutes_travaillees' => $pointage->setRelation('agent', $agent)->calculerMinutesEffectives()
                ],
                default => []
            };

            $pointage->update($updateData);
            return redirect()->back()->with('success', "Action enregistrée.");

        } catch (\Exception $e) {
            Log::error("Erreur Pointage Store: " . $e->getMessage());
            return redirect()->back()->with('error', "Erreur BDD : " . $e->getMessage());
        }
    }

    /**
     * HELPERS
     */
    private function mapStats($agentId, $dates)
{
    $stats = [];
    
    // On force la clé en format string 'YYYY-MM-DD' pour correspondre exactement au tableau $dates
    $plannings = Planning::where('agent_id', $agentId)
        ->whereIn('jour', $dates)
        ->get()
        ->keyBy(function($item) {
            return \Illuminate\Support\Carbon::parse($item->jour)->format('Y-m-d');
        });

    $pointages = Pointage::where('agent_id', $agentId)
        ->whereIn('date_pointage', $dates)
        ->get()
        ->keyBy(function($item) {
            return \Illuminate\Support\Carbon::parse($item->date_pointage)->format('Y-m-d');
        });

    foreach ($dates as $date) {
        $p = $plannings->get($date);
        $pt = $pointages->get($date);

        $stats[$date] = [
            'p_in'  => $p && $p->entree ? Carbon::parse($p->entree)->format('H:i') : null,
            'p_out' => $p && $p->sortie ? Carbon::parse($p->sortie)->format('H:i') : null,
            'a_in'  => $pt && $pt->entree ? Carbon::parse($pt->entree)->format('H:i') : null,
            'a_out' => $pt && $pt->sortie ? Carbon::parse($pt->sortie)->format('H:i') : null,
        ];
    }
    return $stats;
}

private function generateWeekRange(): array
{
    $semaines = [];
    $year = now()->year;
    $currentWeek = now()->weekOfYear; // Semaine actuelle (ex: 18)

    // On boucle de la semaine 1 jusqu'à la semaine actuelle uniquement
    for ($i = 1; $i <= $currentWeek; $i++) {
        $start = now()->setISODate($year, $i)->startOfWeek();
        
        $semaines[] = [
            'numero' => $i,
            'debut'  => $start->format('d/m'),
            'label'  => "Semaine $i"
        ];
    }

    return $semaines;
}


 public function apiData(Request $request)
{
    $week = $request->get('week');
    $siteId = $request->get('site_id');
    $projetId = $request->get('projet_id');

    $date = now()->setISODate(now()->year, $week);
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = $date->copy()->startOfWeek()->addDays($i)->format('Y-m-d');
    }

    $agents = Agent::with(['plannings' => function($q) use ($dates) {
        $q->whereIn('jour', $dates);
    }, 'pointages' => function($q) use ($dates) {
        $q->whereIn('date', $dates);
    }])
    ->when($siteId, fn($q) => $q->where('site_id', $siteId))
    ->when($projetId, fn($q) => $q->whereHas('projets', fn($p) => $p->where('id', $projetId)))
    ->get();

    $resultat = $agents->groupBy(fn($a) => $a->projets->first()->designation ?? 'Sans Projet')
        ->map(function ($group, $projetNom) use ($dates) {
            return [
                'projet' => $projetNom,
                'superviseurs' => $group->map(function ($agent) use ($dates) {
                    $stats = [];
                    foreach ($dates as $date) {
                        $p = $agent->plannings->firstWhere('jour', $date);
                        $a = $agent->pointages->firstWhere('date', $date);

                        $ecartAffichage = "00:00";
                        $typeEcart = 'normal'; // normal, deficit, surplus
                        $retardAffichage = "00:00";

                        if ($a && $a->reel_in && $a->reel_out) {
                            $in = \Carbon\Carbon::parse($a->reel_in);
                            $out = \Carbon\Carbon::parse($a->reel_out);

                            $minutesTravaillees = $out->diffInMinutes($in);
                            $objectifMinutes = 8 * 60; // 480 minutes

                            $difference = $minutesTravaillees - $objectifMinutes;

                            if ($difference < 0) {
                                $typeEcart = 'deficit';
                                $absDiff = abs($difference);
                                $ecartAffichage = sprintf('%02d:%02d', floor($absDiff/60), $absDiff%60);
                            } elseif ($difference > 0) {
                                $typeEcart = 'surplus';
                                $ecartAffichage = sprintf('%02d:%02d', floor($difference/60), $difference%60);
                            }

                            // Calcul du retard si entrée après prévue
                            if ($p && $in->gt(\Carbon\Carbon::parse($p->entree))) {
                                $retardMinutes = $in->diffInMinutes(\Carbon\Carbon::parse($p->entree));
                                $retardAffichage = sprintf('%02d:%02d', floor($retardMinutes/60), $retardMinutes%60);
                            }
                        }

                        $stats[$date] = [
                            'p_in'   => $p ? substr($p->entree, 0, 5) : null,
                            'p_out'  => $p ? substr($p->sortie, 0, 5) : null,
                            'a_in'   => $a ? substr($a->reel_in, 0, 5) : null,
                            'a_out'  => $a ? substr($a->reel_out, 0, 5) : null,
                            'ecart'  => $ecartAffichage,
                            'status' => $typeEcart,
                            'retard' => $retardAffichage
                        ];
                    }
                    return [
                        'nom' => $agent->nom . ' ' . $agent->prenom,
                        'fonction' => $agent->fonction,
                        'stats' => $stats
                    ];
                })
            ];
        })->values();

    return response()->json(['dates' => $dates, 'resultat' => $resultat]);
}


public function exportExcel(Request $request) 
{
    $user = auth()->user();
    
    // 1. Récupération des dates (Lead Dev : Fallback sur la semaine actuelle si vide)
    $dateDebut = $request->date_debut ?? now()->startOfWeek()->format('Y-m-d');
    $dateFin = $request->date_fin ?? now()->endOfWeek()->format('Y-m-d');
    
    // 2. Définition des accès
    $isFullAccess = $user->hasAnyRole(['IT', 'RH', 'Directeur']) || ($user->work_email === 'admin@concentrix.com');

    // 3. Restriction de périmètre pour les Managers
    $restrictedProjectIds = null;
    if (!$isFullAccess) {
        $restrictedProjectIds = $user->agent ? $user->agent->projets->pluck('id')->toArray() : [0];
        
        if ($request->projet_id && !in_array($request->projet_id, $restrictedProjectIds)) {
            return abort(403, "Accès non autorisé à ce projet.");
        }
    }

    // 4. Nom du fichier dynamique (format : Export_du_2024-05-01_au_2024-05-07)
    $fileName = "Export_Pointage_du_{$dateDebut}_au_{$dateFin}_" . now()->format('His') . ".xlsx";

    // 5. Appel de la classe d'export
    // Note : On remplace $week par un tableau ou deux variables selon ce que ta classe PointageExport attend
    return Excel::download(
        new PointageExport(
            $request->site_id, 
            $request->projet_id, 
            $dateDebut, // Passé à la place de $week
            $dateFin,   // Nouvel argument
            $isFullAccess, 
            $restrictedProjectIds
        ), 
        $fileName
    );
}

}