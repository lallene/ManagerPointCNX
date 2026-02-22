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
            // Admin voit tout
            $sites = Projet::select('site_id')->distinct()->whereNotNull('site_id')->pluck('site_id');
            $projetsList = Projet::orderBy('designation')->get();
        } else {
            // Manager ne voit que son périmètre
            $agentConnecte = Agent::with('projets')->where('work_email', $user->work_email)->firstOrFail();
            $sites = $agentConnecte->projets->pluck('site_id')->unique()->filter();
            $projetsList = $agentConnecte->projets;
        }

        return view('pointages.group', [
            'sites' => $sites,
            'projetsList' => $projetsList,
            'semaines' => $this->generateWeekRange(),
            'selectedWeek' => now()->weekOfYear,
            'isManager' => !$hasFullAccess
        ]);
    }

    /**
     * API AJAX : Données pour le tableau de comparaison (Planning vs Réel)
     */
    public function getPointageData(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();

            $hasFullAccess = ($user->work_email === 'admin@concentrix.com') || 
            $user->hasAnyRole(['IT', 'RH', 'Directeur']);
            
            // 1. Définition des dates de la semaine
            $weekNum = (int)$request->input('week', now()->weekOfYear);
            $dateDebut = now()->setISODate(now()->year, $weekNum)->startOfWeek();
            $dates = [];
            for ($i = 0; $i < 7; $i++) { 
                $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d'); 
            }

            // 2. Filtrage des projets autorisés
            $projets = Projet::query()
                ->when(!$hasFullAccess, function($q) use ($user) {
                    $q->whereHas('agents', fn($a) => $a->where('work_email', $user->work_email));
                })
                ->when($request->site_id, fn($q) => $q->where('site_id', $request->site_id))
                ->when($request->projet_id && $request->projet_id !== 'null', fn($q) => $q->where('id', $request->projet_id))
                ->get();

            // 3. Récupération des emails ayant le rôle 'Manager'
            $managerEmails = DB::table('users')
                ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
                ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
                ->where('roles.name', 'Manager')
                ->pluck('users.work_email')->toArray();

            $resultat = [];
            foreach ($projets as $projet) {
                // Eager loading pour éviter le N+1
                $agentsDuProjet = Agent::whereHas('projets', fn($q) => $q->where('projets.id', $projet->id))
                    ->whereIn('work_email', $managerEmails)->get();

                $superviseursData = [];
                foreach ($agentsDuProjet as $agent) {
                    $superviseursData[] = [
                        'nom' => "{$agent->prenom} {$agent->nom}",
                        'fonction' => $agent->fonction ?? 'MANAGER',
                        'stats' => $this->mapStats($agent->id, $dates)
                    ];
                }

                if (!empty($superviseursData)) {
                    $resultat[] = ['projet' => $projet->designation, 'superviseurs' => $superviseursData];
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
        $current = now()->weekOfYear;
        for ($i = $current - 3; $i <= $current + 3; $i++) {
            if ($i < 1 || $i > 53) continue;
            $start = now()->setISODate(now()->year, $i)->startOfWeek();
            $semaines[] = ['numero' => $i, 'debut' => $start->format('d/m'), 'label' => "Semaine $i"];
        }
        return $semaines;
    }



public function exportExcel(Request $request) 
{
    // On récupère les filtres de la requête
    $site_id = $request->site_id;
    $projet_id = $request->projet_id;
    $week = $request->week;

    $fileName = "Export_Pointage_S{$week}.xlsx";

    return Excel::download(new PointageExport($site_id, $projet_id, $week), $fileName);
}
}