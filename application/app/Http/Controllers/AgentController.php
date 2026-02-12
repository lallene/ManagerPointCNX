<?php

namespace App\Http\Controllers;

use App\Imports\AgentsImport;
use App\Models\Agent;
use App\Models\Contrat;
use App\Models\Emploi;
use App\Models\Projet;
use App\Models\Societe;
use App\Models\Sub_fonction;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;


use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;

class AgentController extends Controller
{
    private $templatePath = 'configuration.effectif';
    private $link = 'effectif';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Ressources Humaines|IT|Responsables d’équipe');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
         return view('configuration.effectif.index', ['titre' => 'Liste des collaborateurs']);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $projets = Projet::all();
        $fonctions = Agent::distinct()->pluck('fonction');
        //dd($fonctions);
        return view('configuration.effectif.create', compact('projets',  'fonctions'));

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */


    public function store(Request $request)
{
    $roleMapping = [
          "DIRECTEUR D'ACTIVITE" => 9,
        "DIRECTEUR RH FILIALE" => 2,
        "HR BUSINESS PARTNER" => 2,
        'SUPERVISEUR SENIOR' => 9,
        'ASSISTANT(E) RH' => 2,
        "RESPONSABLE D'ACTIVITE" => 9,
        'FORMATEUR METIER' => 8,
        'CHEF DE PROJETS' => 9,
        'Supervisor Quality, Trainee (Non Agent)' => 8,
        'CONTROLEUR QUALITE PRODUCTION' => 8,
        'TECHNICIEN INFORMATIQUE' => 7,
        'RESPONSABLE TECHNIQUE SITES' => 7,
        'INGENIEUR QUALITE FORMATION' => 8,
        'Formateur Métier Senior' => 9,
        'EXPERT METIER' => 8,
        'Contrôleur Qualité Mission' => 8,
        'LOCAL IT TEAM LEADER' => 7,
        'FORMATEUR METIER SENIOR' => 9,
        'Quality Lead' => 8,
        'DIRECTEUR DE SITE' => 10,
        'Team Leader Trainee, Operations' => 8,
        'Superviseur' => 8,
        'Expert Produit Mission' => 8,
        'Trainer II' => 8,
        'CHEF DE PROJETS AMEL CONT SITE' => 8,
        'CHEF DE PROJETS RH' => 2,
        'People Solutions Generalist I' => 2,
        'RESPONSABLE QUALITE FORMATION' => 9,
        'Sr. Quality Evaluator' => 8,
        'Responsable opération RH' => 2,
        'Superviseur Senior (mission)' => 8,
        'Trainer I' => 8,
        'Contrôleur Qualité Production (mission)' => 8,
        'Team Leader, Operations' => 8,
        'Quality Evaluator - Trainee (Agent)' => 8,
        'Contrôleur Qualité (mission)' => 8,
        'Expert Produit' => 8,
        'CONTROLEUR QUALITE' => 8,
        'Representative, People Solutions' => 2,
        'ASSISTANTE RH' => 2,
        'SUBSIDIARIES CEO' => 10,
     ];

    DB::transaction(function () use ($request, $roleMapping) {
        try {
            $agent = Agent::where('workday_id', $request['workday_id'])->first();

            if (!$agent) {
                $agent = Agent::create([
                    'workday_id' => $request['workday_id'],
                    'projet_id' => $request['projet_id'],
                    'nom' => $request['nom'],
                    'prenom' => $request['prenom'],
                    'email' => $request['email'] ?? null,
                    'fonction' => $request['fonction'] ?? null,
                    'manager' => $request['workday_id_manager'] ?? null,
                ]);
                Log::info('Agent created:', $agent->toArray());
            } else {
                $agent->update([
                    'projet_id' => $request['projet_id'],
                    'manager' => $request['workday_id_manager'] ?? null,
                    'fonction' => $request['fonction'] ?? null,
                ]);
                Log::info('Agent updated:', ['workday_id' => $request['workday_id']]);
            }

            // Créer le User si non existant
            if (!empty($request['email']) && !\App\Models\User::where('email', $request['email'])->exists()) {
                $user = \App\Models\User::create([
                    'name' => $request['prenom'] . ' ' . $request['nom'],
                    'email' => $request['email'],
                    'password' => \Hash::make($request['workday_id']),
                    'password_first_connection' => true,
                ]);

                $fonction = $request['fonction'];
                if (isset($roleMapping[$fonction])) {
                    $user->assignRole($roleMapping[$fonction]);
                    Log::info('User created with role:', [
                        'email' => $user->email,
                        'role_id' => $roleMapping[$fonction],
                    ]);
                }
            }
        } catch (\Exception $e) {
            Log::error('Error processing store:', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);
            throw $e;
        }
    });

    return redirect()->route('effectifs');
}


    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Agent  $agent
     * @return \Illuminate\Http\Response
     */
    public function show(Agent $agent)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Agent  $agent
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $item = Agent::find($id);
        $projets = Projet::all();
        $emplois = Emploi::all();
        $sousfonctions = Sub_fonction::all();
        $contrat = Contrat::all();
        $societe = Societe::all();
        $managers = Agent::all();

        return view($this->templatePath.'.edit', [
            'titre' => "Modifier Collaborateur ".$item->nom.' '.$item->prenom,
            'item' => $item,
            'link' => $this->link,
            'projets' => $projets,
            'emplois' => $emplois,
            'sousfonctions' => $sousfonctions,
            'contrats' => $contrat,
            'societes' => $societe,
            'managers' => $managers
        ]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Agent  $agent
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = Agent::find($id);

        $item->entite = $request->input('entite');
        $item->societe_id = $request->input('societe_id');
        $item->sexe = $request->input('sexe');
        $item->nom = $request->input('nom');
        $item->prenom = $request->input('prenom');
        $item->dateembauche = $request->input('dateembauche');
        $item->projet_id = $request->input('projet_id');
        $item->manager = $request->input('manager');
        $item->emploi_id = $request->input('emploi_id');
        $item->sousfonction_id = $request->input('sousfonction_id');
        $item->contrat_id = $request->input('contrat_id');

        try{
            $item->save();
        }catch (\Exception $e){
            echo'e';
        }
        return redirect()->route('effectif.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Agent  $agent
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Agent::find($id);

        $item->delete();

        return redirect()->route('effectif.index')
            ->with('success', "Collaborateur retiré avec succes");
    }

    public function getAgentByIris(Request $request){
        $iris = $request->input('id');
        $agent = Agent::where('iris', '=', $iris)->get();

        $array = array();

        foreach ($agent as $i) {
            $item = Agent::find($i->id);
            $array['Id'] = $item->id;
            $array['Nom'] = $item->nom;
            $array['Prenom'] = $item->prenom;
            $array['Sexe'] = ($item->sexe == 'M') ? 'Masculin' : 'Feminin';
            $array['DateEmbauche'] = $item->dateembauche;
            $array['Projet'] = $item->Projet->designation;
            $array['Fonction'] = $item->SousFonction->Fonction->intitule;
            $array['Emploi'] = $item->Emploi->designation;
        }

        return json_encode($array);
    }

    public function import (Request $req){

       Excel::import(new AgentsImport, $req->file('agent_file'),);

        $datenow = Carbon::now();
        $datenow=   $datenow->format('Y/m/d');
        $dateInsertion=   Carbon::now()->format('d/m/Y');



    return redirect()->route('effectifs')->with('success','Les  collaborateurs ont bien été enregistrés.');
    }

    public function agents()
{
    $agents = DB::table('agents')
    ->select ('id', 'nom', 'prenom', 'workday_id', 'email', 'fonction', 'manager')->get();
    return response()->json($agents);
}
public function liste()
{
    $agents = DB::table('agents')
        ->join('projets', 'agents.projet_id', '=', 'projets.id')
        ->leftJoin('agents as managers', 'agents.manager', '=', 'managers.workday_id')
        ->whereNotNull('agents.workday_id')
        ->select(
            'agents.id as id',
            'projets.site_id as site',
            'agents.workday_id',
            'agents.nom',
            'agents.prenom',
            'agents.fonction',
            'agents.email',
            'projets.designation as projet',
            DB::raw("COALESCE(CONCAT(managers.prenom, ' ', managers.nom), agents.manager) as manager_nom")
        )
        ;

    //dd($agents->get());
    return view($this->templatePath . '.liste', [
        'titre' => 'Liste des collaborateurs',
        'agents' => $agents,
        'link' => $this->link
    ]);
}



public function ajax()
{
    $query = DB::table('agents')
        ->join('projets', 'agents.projet_id', '=', 'projets.id')
        ->leftJoin('agents as managers', 'agents.manager', '=', 'managers.workday_id')
        ->whereNotNull('agents.workday_id')
        ->select(
            'projets.site_id as site',
            'agents.workday_id',
            'agents.nom',
            'agents.prenom',
            'agents.fonction',
            'agents.email',
            'projets.designation as projet',
            DB::raw("COALESCE(CONCAT(managers.prenom, ' ', managers.nom), agents.manager) as manager_nom")
        );

    return DataTables::of($query)->toJson();
}




}
