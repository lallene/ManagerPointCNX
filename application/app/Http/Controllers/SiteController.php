<?php

namespace App\Http\Controllers;

use App\Models\Site;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log;

class SiteController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        // Extension du rôle à IT pour la maintenance
        $this->middleware('role:Ressources Humaines|IT');
    }

    public function index(): View
    {
        $sites = Site::all();
        return view('configuration.site.liste', [
            'titre' => "Liste des Sites", 
            'sites' => $sites
        ]);
    }

    public function create(): View
    {
        return view('configuration.site.create', ['titre' => "Ajouter un Site"]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'designation' => 'required|string|max:255|unique:sites,designation',
            'responsable' => 'nullable|string|max:255',
            'contact'     => 'nullable|string'
        ]);

        Site::create($request->only('designation', 'responsable', 'contact'));

        return redirect()->route('site.index')->with('success', 'Site ajouté avec succès.');
    }

    public function edit(Site $site): View
    {
        return view('configuration.site.edit', [
            'titre' => "Modifier " . $site->designation, 
            'site' => $site
        ]);
    }

    public function update(Request $request, Site $site): RedirectResponse
    {
        $request->validate([
            'designation' => 'required|string|max:255|unique:sites,designation,' . $site->id,
            'responsable' => 'nullable|string|max:255',
            'contact'     => 'nullable|string'
        ]);

        try {
            $site->update($request->only('designation', 'responsable', 'contact'));
            return redirect()->route('site.index')->with('success', 'Site modifié avec succès.');
        } catch (\Exception $e) {
            Log::error("Erreur update site: " . $e->getMessage());
            return redirect()->back()->with('error', 'Erreur lors de la modification.');
        }
    }

    public function destroy(Site $site): RedirectResponse
    {
        // Sécurité critique : On vérifie si des projets sont rattachés au site
        if ($site->projets()->count() > 0) {
            return redirect()->back()->with('error', 'Impossible de supprimer : ce site contient encore des projets actifs.');
        }

        $site->delete();
        return redirect()->route('site.index')->with('success', 'Site supprimé avec succès.');
    }
}