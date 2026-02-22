<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Projet;
use Illuminate\Http\Request;
use App\Imports\ProjetsImport; 
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ProjetController extends Controller
{
 
    private string $templatePath = 'configuration.projet';
    private string $link = 'projet';

   public function index() {
        return view('configuration.projet.liste');
    }

    public function import(Request $request) {
        $request->validate(['file' => 'required|mimes:xlsx,xls,csv']);
        
        try {
            Excel::import(new ProjetsImport, $request->file('file'));
            return back()->with('success', 'Référentiel mis à jour avec succès !');
        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de l\'import : ' . $e->getMessage());
        }
    }

    public function getProjetAjax() {
        // Jointure avec sites pour le regroupement
        $query = Projet::join('sites', 'projets.site_id', '=', 'sites.id')
        ->select([
            'sites.designation as site_nom', // Indispensable pour data: 'site_nom'
            'projets.msa_id',
            'projets.designation as projet_nom',
            'projets.dltsuperviseur',
            'projets.id'
        ]);

        return DataTables::of($query)
            ->addColumn('action', function($row) {
                return '
                    <div class="btn-group">
                        <button class="btn btn-sm btn-outline-primary"><i class="fas fa-edit"></i></button>
                        <button class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                    </div>';
            })
            ->rawColumns(['action'])
            ->make(true);
    }

    public function create(): View
    {
        $foreigns = Site::all();
        return view($this->templatePath.'.create', [
            'titre' => "Ajouter un Projet/Service", 
            'link' => $this->link, 
            'foreigns' => $foreigns
        ]);
    }

    public function store(Request $request)
    {
        // 1. Validation de base
        $request->validate([
            'plannings' => 'required|array',
            'week' => 'required'
        ]);

        $data = $request->input('plannings');
        $authUserId = auth()->id();
        $today = now()->startOfDay();

        // 2. Récupération du périmètre du Top Manager connecté
        // On récupère les IDs des projets auxquels le Top Manager appartient
        $topManager = Agent::where('work_email', auth()->user()->email)->first();
        
        if (!$topManager) {
            return back()->with('error', "Action non autorisée : Profil agent introuvable.");
        }

        $allowedProjetIds = $topManager->projets->pluck('id')->toArray();

        try {
            DB::beginTransaction();

            foreach ($data as $agentId => $jours) {
                
                // --- RÈGLE 1 : VÉRIFICATION DU PÉRIMÈTRE PROJET ---
                $targetAgent = Agent::with('projets')->find($agentId);
                
                if (!$targetAgent) continue;

                // On vérifie si l'agent cible partage au moins un projet avec le Top Manager
                $targetAgentProjets = $targetAgent->projets->pluck('id')->toArray();
                $hasAccess = !empty(array_intersect($allowedProjetIds, $targetAgentProjets));

                if (!$hasAccess) {
                    // Si le Top Manager tente de modifier un agent hors de son périmètre
                    continue; 
                }

                foreach ($jours as $date => $heures) {
                    
                    // --- RÈGLE 2 : PROTECTION DU PASSÉ ---
                    $datePlanning = \Carbon\Carbon::parse($date)->startOfDay();
                    
                    // On ne peut créer/modifier que pour aujourd'hui ou le futur
                    if ($datePlanning->isBefore($today)) {
                        continue; // On ignore les dates passées
                    }

                    if (!empty($heures['entree']) || !empty($heures['sortie'])) {
                        Planning::updateOrCreate(
                            [
                                'agent_id' => $agentId,
                                'jour'     => $date,
                            ],
                            [
                                'entree'   => $heures['entree'],
                                'sortie'   => $heures['sortie'],
                                'semaine'  => $request->input('week'),
                                'user_id'  => $authUserId, // ID de celui qui crée
                            ]
                        );
                    }
                }
            }

            DB::commit();
            return back()->with('success', 'Le planning a été mis à jour (les dates passées et hors périmètre ont été ignorées).');

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error("Erreur planning : " . $e->getMessage());
            return back()->with('error', 'Une erreur est survenue.');
        }
    }

    public function edit(Projet $projet): View
    {
        $foreigns = Site::all();
        return view($this->templatePath.'.edit', [
            'titre' => "Modifier Projet : " . $projet->designation, 
            'item' => $projet, 
            'link' => $this->link, 
            'foreigns' => $foreigns
        ]);
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $item = Projet::findOrFail($id);

        try {
            $item->update($request->only('designation', 'site_id', 'dltsuperviseur', 'msa_id'));
            return redirect()->route('projet.index')->with('success', 'Projet mis à jour.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function destroy($id): RedirectResponse
    {
        $item = Projet::findOrFail($id);
        
        if ($item->agents()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer : des agents sont liés à ce projet.');
        }

        $item->delete();
        return redirect()->route('projet.index')->with('success', "Projet supprimé avec succès.");
    }

   
    /**
         * Moteur AJAX pour DataTable
         */
        public function ajax()
        {
            $query = DB::table('projets')
                ->leftJoin('sites', 'projets.site_id', '=', 'sites.id')
                ->select([
                    'projets.id as projet_id',
                    'projets.msa_id',
                    'projets.designation as projet_nom',
                    'sites.designation as site_nom',
                    'projets.dltsuperviseur',
                ]);

            return DataTables::of($query)
                ->filterColumn('site_nom', function($query, $keyword) {
                    $query->where('sites.designation', 'like', "%{$keyword}%");
                })
                ->editColumn('site_nom', function($row) {
                    return $row->site_nom ?? 'SANS SITE';
                })
                ->addColumn('action', function ($row) {
                    $edit = route('projet.edit', $row->projet_id);
                    $delete = route('projet.destroy', $row->projet_id);
                    $token = csrf_token();

                    return <<<HTML
                        <div class="btn-group shadow-sm">
                            <a href="{$edit}" class="btn btn-sm btn-outline-primary" title="Modifier">
                                <i class="fa fa-edit"></i>
                            </a>
                            <form action="{$delete}" method="POST" class="d-inline" onsubmit="return confirm('Confirmer la suppression ?')">
                                <input type="hidden" name="_token" value="{$token}">
                                <input type="hidden" name="_method" value="DELETE">
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Supprimer">
                                    <i class="fa fa-trash"></i>
                                </button>
                            </form>
                        </div>
    HTML;
                })
                ->rawColumns(['action']) 
                ->setRowId('projet_id') 
                ->make(true);
        }
}