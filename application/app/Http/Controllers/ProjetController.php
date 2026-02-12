<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Models\Projet;
use Illuminate\Http\Request;
use App\Imports\ProjetsImport;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\DataTables;


class ProjetController extends Controller
{
    private $templatePath = 'configuration.projet';
    private $link = 'projet';

    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Ressources Humaines|IT');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        return view('configuration.projet.liste', ['titre' => 'Liste des Projets/Services']);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $foreigns = Site::all();
        return view($this->templatePath.'.create', ['titre' => "Ajouter un Projet/Service", 'link' => $this->link, 'foreigns' => $foreigns]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Projet::create(
            [
                'designation' => $request->input('designation'),
                'site_id' => $request->input('site_id'),
                'dltsuperviseur' => $request->input('dltsuperviseur')
            ]
        );

        return redirect()->route('projet.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Projet  $projet
     * @return \Illuminate\Http\Response
     */
    public function show(Projet $projet)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Projet  $projet
     * @return \Illuminate\Http\Response
     */
    public function edit(Projet $projet)
    {
        $item = $projet;
        $foreigns = Site::all();

        return view($this->templatePath.'.edit', ['titre' => "Modifier Projet".$item->designation, 'item' => $item, 'link' => $this->link, 'foreigns' => $foreigns]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Projet  $projet
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $item = Projet::find($id);

        $item->designation = $request->input('designation');
        $item->site_id = $request->input('site_id');
        $item->dltsuperviseur = $request->input('dltsuperviseur');


        try{
            $item->save();
        }catch (\Exception $e){
            echo'e';
        }
        return redirect()->route('projet.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Projet  $projet
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $item = Projet::find($id);

        $item->delete();

        return redirect()->route('projet.index')
            ->with('success', "Projet Supprimé avec succes");
    }

    public function import (Request $req){

        Excel::import(new ProjetsImport, $req->file('projet_file'),

     );

         return back();
     }


    public function ajax()
{
    // Construction de la requête avec jointure pour obtenir le nom du site
    $query = DB::table('projets')
        ->join('sites', 'projets.site_id', '=', 'sites.id')
        ->select([
            'projets.id as projet_id',      // Utilisé pour les routes edit/destroy
            'projets.msa_id',
            'projets.designation',
            'sites.designation as site_nom',        // On récupère le NOM du site pour l'affichage
            'projets.dltsuperviseur',
        ]);

    return DataTables::of($query)
        // Correction de l'erreur SQL : On lie l'alias 'site_nom' à la colonne physique 'sites.nom'
        ->filterColumn('site_nom', function($query, $keyword) {
            $query->where('sites.designation', 'like', "%{$keyword}%");
        })
        // Correction pour MSA ID (évite les conflits de noms de colonnes)
        ->filterColumn('msa_id', function($query, $keyword) {
            $query->where('projets.msa_id', 'like', "%{$keyword}%");
        })
        ->addColumn('action', function ($row) {
            $edit = route('projet.edit', $row->projet_id);
            $delete = route('projet.destroy', $row->projet_id);
            $token = csrf_token();

            return <<<HTML
                <div class="btn-group">
                    <a href="{$edit}" class="btn btn-sm btn-primary" title="Modifier">
                        <i class="fa fa-edit"></i>
                    </a>
                    <form action="{$delete}" method="POST" style="display:inline-block;" onsubmit="return confirm('Supprimer ce projet ?')">
                        <input type="hidden" name="_token" value="{$token}">
                        <input type="hidden" name="_method" value="DELETE">
                        <button type="submit" class="btn btn-sm btn-danger" title="Supprimer">
                            <i class="fa fa-trash"></i>
                        </button>
                    </form>
                </div>
HTML;
        })
        ->rawColumns(['action'])
        ->make(true);
}

}
