@extends('layouts.app')

@section('content')
<style>
    /* Correction pour forcer le mode standard et éviter les bugs de marges */
    @media (min-width: 1400px) {
        .container {
            max-width: 1420px !important;
        }
    }

    /* Style global tableau pour un rendu aéré */
    .table-planning {
        border-collapse: separate !important;
        border-spacing: 0 8px;
    }

    .table-planning thead th {
        background-color: #cfe2ff;
        color: #084298;
        font-weight: 700;
        vertical-align: middle;
        text-align: center;
        border: none;
        padding: 12px 10px;
    }

    /* Colonne Agent stylisée */
    .table-planning tbody td:first-child {
        font-weight: 600;
        color: #0d6efd;
        min-width: 180px;
        background-color: #fff;
        border-left: 5px solid #0d6efd;
    }

    /* Cellules des horaires */
    .table-planning tbody td {
        background-color: #f8f9fa;
        vertical-align: middle;
        min-width: 150px;
        padding: 10px;
        border: 1px solid #dee2e6;
        transition: all 0.2s ease;
    }

    .table-planning tbody tr:hover td {
        background-color: #eef5ff;
    }

    /* Badge des horaires */
    .badge-horaire {
        background-color: #198754 !important;
        color: white;
        font-weight: 600;
        padding: 6px 10px;
        border-radius: 8px;
        display: block;
        box-shadow: 0 2px 4px rgba(25, 135, 84, 0.2);
    }

    /* Styles des titres de regroupement */
    .site-title {
        color: #0d6efd;
        font-size: 1.7rem;
        font-weight: bold;
        border-left: 6px solid #0d6efd;
        padding: 10px 15px;
        margin-bottom: 1.5rem;
        background-color: #e9f2ff;
        border-radius: 5px;
    }

    .group-card {
        background: white;
        padding: 1.5rem;
        border: 1px solid #dee2e6;
        border-left: 4px solid #198754;
        border-radius: 10px;
        box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        margin-bottom: 2rem;
    }

    .filtre-select {
        padding: 8px 12px;
        border-radius: 6px;
        border: 1px solid #ced4da;
        min-width: 200px;
    }
</style>

<div class="container-fluid">
     <div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>🗓️ Planning Hebdomadaire</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Vue groupée par Site et Projet</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Section Filtres --}}
    <div class="card shadow-sm mb-4 border-0">
        <div class="card-body bg-light rounded">
            @if (!$filtreFixe)
                <form method="GET" class="d-flex flex-wrap justify-content-center gap-3">
                    <div class="d-flex align-items-center">
                        <label class="me-2 fw-bold">Site :</label>
                        <select name="site_id" onchange="this.form.submit()" class="filtre-select">
                            <option value="">Tous les sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site }}" {{ $selectedSiteId == $site ? 'selected' : '' }}>{{ $site }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="d-flex align-items-center">
                        <label class="me-2 fw-bold">Projet :</label>
                        <select name="projet_id" onchange="this.form.submit()" class="filtre-select">
                            <option value="">Tous les projets</option>
                            @foreach ($projetsList as $projet)
                                <option value="{{ $projet->id }}" {{ $selectedProjetId == $projet->id ? 'selected' : '' }}>{{ $projet->designation }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            @else
                <div class="text-center">
                    <span class="badge bg-primary px-3 py-2">Site : {{ $selectedSiteId }}</span>
                    <span class="badge bg-secondary px-3 py-2 ms-2">Projet : {{ optional($projetsList->firstWhere('id', $selectedProjetId))->designation ?? 'N/A' }}</span>
                </div>
            @endif
        </div>
    </div>

    {{-- Navigation des semaines --}}
    <div class="d-flex flex-wrap justify-content-center mb-5 gap-2">
        @foreach ($semaines as $semaine)
            <form method="GET" action="{{ route('planning.group') }}">
                <input type="hidden" name="week" value="{{ $semaine['numero'] }}">
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                <input type="hidden" name="projet_id" value="{{ $selectedProjetId }}">
                <button type="submit" class="btn btn-sm {{ $selectedWeek == $semaine['numero'] ? 'btn-primary' : 'btn-outline-primary' }} px-3 py-2 shadow-sm">
                    <strong>Semaine {{ $semaine['numero'] }}</strong><br>
                    <small>{{ $semaine['debut'] }} - {{ $semaine['fin'] }}</small>
                </button>
            </form>
        @endforeach
    </div>

    {{-- Données groupées --}}
    @foreach ($resultat as $siteData)
        <div class="mb-5">
            <h4 class="site-title"><i class="bi bi-geo-alt-fill"></i> Site : {{ $siteData['site'] }}</h4>
            
            @foreach ($siteData['projets'] as $projetData)
                <div class="ms-md-4 mb-4">
                    <h5 class="projet-title"><i class="bi bi-folder-fill"></i> {{ $projetData['projet'] }}</h5>

                    @foreach ($projetData['groupes'] as $groupe)
                        <div class="group-card">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <span class="manager-label">👤 Responsable : <span class="superviseur-nom">{{ $groupe['manager'] }}</span></span>
                                <span class="badge bg-info text-dark">{{ count($groupe['agents']) }} Agent(s)</span>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-planning align-middle">
                                    <thead>
                                        <tr>
                                            <th>Agent</th>
                                            @foreach ($dates as $date)
                                                <th>
                                                    {{ ucfirst(\Carbon\Carbon::parse($date)->locale('fr')->isoFormat('dddd')) }}<br>
                                                    <small class="text-muted">{{ \Carbon\Carbon::parse($date)->format('d/m') }}</small>
                                                </th>
                                            @endforeach
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($groupe['agents'] as $agent)
                                            <tr>
                                                <td>{{ $agent->nom }} {{ $agent->prenom }}</td>
                                                @foreach ($dates as $date)
                                                    @php
                                                        $jourDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                                        $key = $agent->id . '-' . $jourDate;
                                                        $planning = $plannings->get($key);
                                                        $planning = $planning ? $planning->first() : null;
                                                    @endphp
                                                    <td class="text-center">
                                                        @if ($planning)
                                                            <span class="badge-horaire">
                                                                {{ \Carbon\Carbon::parse($planning->heure_debut)->format('H:i') }} - {{ \Carbon\Carbon::parse($planning->heure_fin)->format('H:i') }}
                                                            </span>
                                                        @else
                                                            <span class="text-muted" style="opacity: 0.4">Repos</span>
                                                        @endif
                                                    </td>
                                                @endforeach
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endforeach
        </div>
    @endforeach
</div>
@endsection