<?php

namespace App\Http\Controllers;

use App\Models\Site;
use App\Imports\SitesImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('role:Ressources Humaines');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $sites = Site::all();
        return view('configuration.site.liste', ['titre' => "Liste des Sites", 'sites' => $sites]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('configuration.site.create', ['titre' => "Ajouter un Site"]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        Site::create(
            [
                'designation' => $request->input('designation'),
                'responsable' => $request->input('responsable'),
                'contact' => $request->input('contact')
            ]
        );

        return redirect()->route('site.index')->with('success','Site ajouté avec succès.');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function show(Site $site)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function edit(Site $site)
    {
        return view('configuration.site.edit', ['titre' => "Modifier ".$site->designation, 'site' => $site]);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Site $site)
    {
        $site->designation = $request->input('designation');
        $site->responsable = $request->input('responsable');
        $site->contact = $request->input('contact');

        try{
            $site->save();
        }catch (\Exception $e){
            echo'e';
        }
        return redirect()->route('site.index')->with('success','Site modifié avec succès.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Site  $site
     * @return \Illuminate\Http\Response
     */
    public function destroy(Site $site)
    {
        $site->delete();

        return redirect()->route('site.index')
            ->with('success', 'Site Supprimé avec succes');
    }

}
