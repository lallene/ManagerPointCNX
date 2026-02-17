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

  
    public function index(): View
    {
        return view('configuration.projet.liste', ['titre' => 'Liste des Projets/Services']);
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

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'designation' => 'required|string|max:255',
            'site_id' => 'required|exists:sites,id'
        ]);

        Projet::create($request->only('designation', 'site_id', 'dltsuperviseur'));

        return redirect()->route('projet.index')->with('success', 'Projet créé avec succès.');
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
            $item->update($request->only('designation', 'site_id', 'dltsuperviseur'));
            return redirect()->route('projet.index')->with('success', 'Projet mis à jour.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Erreur : ' . $e->getMessage());
        }
    }

    public function destroy($id): RedirectResponse
    {
        $item = Projet::findOrFail($id);
        
        // Sécurité : On vérifie si des agents sont liés au projet avant de supprimer
        if ($item->agents()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer : des agents sont liés à ce projet.');
        }

        $item->delete();
        return redirect()->route('projet.index')->with('success', "Projet supprimé avec succès.");
    }

    public function import(Request $req): RedirectResponse
    {
        $req->validate(['projet_file' => 'required|mimes:xlsx,xls,csv']);
        Excel::import(new ProjetsImport, $req->file('projet_file'));
        return back()->with('success', 'Importation réussie.');
    }


    public function ajax()
{
    // Construction de la requête avec Left Join pour ne perdre aucun projet
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
        // 1. Filtrage sur la colonne jointe
        ->filterColumn('site_nom', function($query, $keyword) {
            $query->where('sites.designation', 'like', "%{$keyword}%");
        })

        // 2. Gestion de l'affichage du site (Badge si NULL)
        ->editColumn('site_nom', function($row) {
            return $row->site_nom ?? '<span class="badge bg-danger">Sans site</span>';
        })

        // 3. Génération des boutons d'action
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
        
        // 4. Paramètres DataTables
        ->rawColumns(['action', 'site_nom']) // On autorise le HTML pour ces colonnes
        ->setRowId('projet_id') 
        ->make(true);
}
}