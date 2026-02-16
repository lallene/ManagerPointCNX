<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use App\Models\Agent;
use App\Models\Pointage;
use App\Models\User;
use App\Models\Planning;
use App\Models\Projet;
use Illuminate\Http\Request;
use Carbon\Carbon;






class PointageController extends Controller
{
     /**
     * Afficher la liste des pointages (avec filtre optionnel par semaine).
     */

    public function index()
{
    $sites = Projet::select('site_id')->distinct()->pluck('site_id');
    
    // On génère la liste des semaines pour les filtres
    $semaines = [];
    $semaineActuelle = now()->weekOfYear;
    for ($i = $semaineActuelle - 3; $i <= $semaineActuelle + 3; $i++) {
        if ($i < 1 || $i > 53) continue;
        $start = Carbon::now()->startOfYear()->addWeeks($i - 1)->startOfWeek();
        $semaines[] = [
            'numero' => $i,
            'debut' => $start->format('d/m'),
            'label' => "Semaine $i"
        ];
    }

    $selectedWeek = $semaineActuelle;
    $selectedSiteId = null;

    return view('pointages.group', compact('sites', 'semaines', 'selectedWeek', 'selectedSiteId'));
}






    public function create()
{
    // 1. Sécurité : Vérifier le rôle Manager (ou Agent selon tes besoins)
    if (!Auth::user()->hasRole('Manager')) {
        abort(403, 'Accès interdit');
    }

    $now = Carbon::now();
    $currentDate = $now->toDateString();
    $currentTime = $now->format('H:i');
    $user = Auth::user();

    // Formatage semaine identique au store (ex: 2026-07)
    $currentWeekDB = $now->year . '-' . str_pad($now->weekOfYear, 2, '0', STR_PAD_LEFT);

    $agent = Agent::where('work_email', $user->work_email)->first();
    if (!$agent) {
        return redirect()->back()->with('error', "Profil agent introuvable.");
    }

    // 2. Récupération du planning
    $planningDisponible = Planning::where('agent_id', $agent->id)
        ->whereDate('jour', $currentDate)
        ->first(); // Retiré la contrainte 'semaine' car date_pointage suffit et évite les erreurs de format

    // 3. FIX : On cherche par agent_id pour être cohérent avec le store et la table
    $pointageExistant = Pointage::where('agent_id', $agent->id)
                        ->whereDate('date_pointage', $currentDate)
                        ->first();

    // 4. Logique de la prochaine action
    $prochaineAction = 'debut'; 

    if ($pointageExistant) {
        if (!$pointageExistant->pause_debut) {
            $prochaineAction = 'debutpause';
        } elseif (!$pointageExistant->pause_fin) {
            $prochaineAction = 'finpause';
        } elseif (!$pointageExistant->sortie || $pointageExistant->sortie === '00:00:00') {
            $prochaineAction = 'fin';
        } else {
            $prochaineAction = 'termine'; 
        }
    }

    // 5. Variables pour la vue
    return view('pointages.create', [
        'currentWeek'        => $now->weekOfYear,
        'currentDate'        => $currentDate,
        'currentTime'        => $currentTime,
        'pointageDuJour'     => $pointageExistant, // C'est cette variable que ton Blade utilise
        'planningDisponible' => $planningDisponible,
        'prochaineAction'    => $prochaineAction,
        'agent'              => $agent
    ]);
}
    /**
     * Enregistrer un nouveau pointage.
     */
    

    public function store(Request $request)
{
    $user = Auth::user();
    $agent = Agent::where('work_email', $user->work_email)->first();
    
    if (!$agent) {
        return redirect()->back()->with('error', "Erreur système : Aucun profil agent lié.");
    }

    $now = Carbon::now();
    $today = $now->toDateString();
    $currentTime = $now->toTimeString(); 
    $currentWeekDB = $now->year . '-' . str_pad($now->weekOfYear, 2, '0', STR_PAD_LEFT);

    $pointage = Pointage::where('agent_id', $agent->id)
        ->whereDate('date_pointage', $today)
        ->first();

    $action = $request->input('action');

    try {
        if ($action === 'debut') {
            if ($pointage) {
                return redirect()->back()->with('error', "Un pointage existe déjà pour aujourd'hui.");
            }

            $planning = Planning::where('agent_id', $agent->id)->whereDate('jour', $today)->first();

            // CRÉATION : On force les champs de fin à NULL ou 0
            $nouveauPointage = new Pointage();
            $nouveauPointage->agent_id = $agent->id;
            $nouveauPointage->planning_id = $planning ? $planning->id : null;
            $nouveauPointage->user_id = $user->id;
            $nouveauPointage->date_pointage = $today;
            $nouveauPointage->semaine = $currentWeekDB;
            $nouveauPointage->entree = $currentTime;
            
            // --- FIX : Empêcher le pré-remplissage ---
            $nouveauPointage->sortie = null; 
            $nouveauPointage->minutes_travaillees = 0;
            // -----------------------------------------
            
            $nouveauPointage->save();

            return redirect()->back()->with('success', "Entrée enregistrée à $currentTime.");
        }

        if (!$pointage) {
            return redirect()->back()->with('error', "Aucun pointage trouvé pour aujourd'hui.");
        }

        $data = [];
        if ($action === 'debutpause') $data['pause_debut'] = $currentTime;
        if ($action === 'finpause')   $data['pause_fin'] = $currentTime;
        
        if ($action === 'fin') {
            $data['sortie'] = $currentTime;
            // On met à jour l'instance pour le calcul des minutes
            $pointage->sortie = $currentTime; 
            $data['minutes_travaillees'] = $pointage->calculerMinutesEffectives();
        }

        $pointage->update($data);
        return redirect()->back()->with('success', "Action $action enregistrée !");

    } catch (\Exception $e) {
        \Log::error("Erreur Pointage Store: " . $e->getMessage());
        return redirect()->back()->with('error', "Erreur BDD : " . $e->getMessage());
    }
}

    /**
     * Modifier un pointage existant.
     */
    public function update(Request $request, Pointage $pointage)
    {
        $request->validate([
            'semaine' => 'required|integer',
            'date_pointage' => 'required|date_pointage',
            'heure' => 'required',
            'commentaires' => 'nullable|string',
            'user_id' => 'required|exists:users,id',
            'planning_id' => 'required|exists:plannings,id',
        ]);

        $pointage->update($request->all());

        return redirect()->back()->with('success', 'Pointage mis à jour.');
    }

    /**
     * Supprimer un pointage.
     */
    public function destroy(Pointage $pointage)
    {
        $pointage->delete();

        return redirect()->back()->with('success', 'Pointage supprimé.');
    }

    public function PointageGlobal(Request $request)
{
    $fonctionsSuperviseurs = [
        'Superviseur',
        'Team Leader Trainee, Operations',
        'Team Leader, Operations',
        'SUPERVISEUR',
    ];

    $selectedSiteId   = $request->input('site_id');
    $selectedProjetId = $request->input('projet_id');
    $selectedWeek     = $request->input('week', Carbon::now()->weekOfYear);

    // 1. Calcul des dates (Format Carbon compatible 7.2)
    $dateDebut = Carbon::now()->startOfYear()->addWeeks($selectedWeek - 1)->startOfWeek(Carbon::MONDAY);
    $dateFin = (clone $dateDebut)->addDays(6);
    
    $dates = [];
    for ($i = 0; $i < 7; $i++) {
        $dates[] = $dateDebut->copy()->addDays($i)->format('Y-m-d');
    }

    // 2. La Requête SQL Maîtresse (Basée sur les plannings)
    $rawPointages = Planning::select(
            'plannings.jour as planning_jour',
            'plannings.entree as planning_entree',
            'plannings.sortie as planning_sortie',
            'pointages.entree as pointage_entree',
            'pointages.sortie as pointage_sortie',
            'agents.work_email',
            'agents.nom',
            'agents.prenom',
            'agents.fonction',
            'projets.designation as projet_nom'
        )
        ->join('agents', 'plannings.agent_id', '=', 'agents.id')
        ->join('projets', 'agents.projet_id', '=', 'projets.id')
        ->leftJoin('pointages', 'plannings.id', '=', 'pointages.planning_id')
        ->whereBetween('plannings.jour', [$dateDebut->format('Y-m-d'), $dateFin->format('Y-m-d')])
        ->whereIn('agents.fonction', $fonctionsSuperviseurs)
        ->when($selectedSiteId, function ($q) use ($selectedSiteId) {
            return $q->where('projets.site_id', $selectedSiteId);
        })
        ->when($selectedProjetId, function ($q) use ($selectedProjetId) {
            return $q->where('projets.id', $selectedProjetId);
        })
        ->get();

    // 3. Structuration des données (On groupe par projet puis par agent)
    $projetsMap = [];

    foreach ($rawPointages as $p) {
        $projNom = $p->projet_nom;
        $email = $p->work_email;

        if (!isset($projetsMap[$projNom])) {
            $projetsMap[$projNom] = [
                'projet' => $projNom,
                'superviseurs' => []
            ];
        }

        if (!isset($projetsMap[$projNom]['superviseurs'][$email])) {
            $projetsMap[$projNom]['superviseurs'][$email] = [
                'nom' => $p->nom,
                'prenom' => $p->prenom,
                'fonction' => $p->fonction,
                'stats' => [] // contiendra les jours
            ];
        }

        // On indexe par date pour que le JS retrouve la donnée instantanément
        $projetsMap[$projNom]['superviseurs'][$email]['stats'][$p->planning_jour] = [
            'p_in'  => $p->planning_entree ? substr($p->planning_entree, 0, 5) : null,
            'p_out' => $p->planning_sortie ? substr($p->planning_sortie, 0, 5) : null,
            'a_in'  => $p->pointage_entree ? substr($p->pointage_entree, 0, 5) : null,
            'a_out' => $p->pointage_sortie ? substr($p->pointage_sortie, 0, 5) : null,
        ];
    }

    // Conversion en tableau simple pour le JSON (évite les objets vides {} en JS)
    $finalResult = [];
    foreach ($projetsMap as $proj) {
        $proj['superviseurs'] = array_values($proj['superviseurs']);
        $finalResult[] = $proj;
    }

    return response()->json([
        'dates' => $dates,
        'resultat' => $finalResult,
        'week' => $selectedWeek
    ]);
}

}
