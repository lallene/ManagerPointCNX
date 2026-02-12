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
                <h2>🗓️ Suivi des Pointages Hebdomadaires</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>🗓️ Suivi des Pointages Hebdomadaires</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Section Filtres --}}
    <div class="card border-0 shadow-sm mb-4">
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
    </div>

    {{-- Navigation Semaines --}}
    <div class="d-flex flex-wrap justify-content-center mb-4 gap-2">
        @foreach ($semaines as $semaine)
            <form method="GET" action="{{ route('pointages.global') }}">
                <input type="hidden" name="week" value="{{ $semaine['numero'] }}">
                <input type="hidden" name="site_id" value="{{ $selectedSiteId }}">
                <input type="hidden" name="projet_id" value="{{ $selectedProjetId }}">
                <button type="submit" class="btn btn-sm {{ $selectedWeek == $semaine['numero'] ? 'btn-primary' : 'btn-outline-secondary' }} shadow-sm">
                    S{{ $semaine['numero'] }}<br>
                    <small style="font-size: 0.65rem;">{{ $semaine['debut'] }} - {{ $semaine['fin'] }}</small>
                </button>
            </form>
        @endforeach
    </div>

    {{-- Rendu des Tableaux --}}
    @foreach ($resultat as $siteData)
        <h4 class="site-title"><i class="bi bi-geo-alt-fill"></i> Site : {{ $siteData['site'] }}</h4>
        @foreach ($siteData['projets'] as $projetData)
            <div class="ms-md-4 mb-4">
                <h5 class="projet-title"><i class="bi bi-folder-fill"></i> Projet : {{ $projetData['projet'] }}</h5>

                @foreach ($projetData['groupes'] as $groupe)
                    <div class="group-card">
                        <div class="mb-3">
                            <span class="manager-label">👤 Responsable :</span> 
                            <span class="superviseur-nom ms-2">{{ $groupe['manager'] }}</span>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-pointage align-middle">
                                <thead>
                                    <tr>
                                        <th rowspan="2">Agent</th>
                                        @foreach ($dates as $date)
                                            <th colspan="3">
                                                {{ ucfirst(\Carbon\Carbon::parse($date)->locale('fr')->isoFormat('dddd')) }}<br>
                                                <small>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</small>
                                            </th>
                                        @endforeach
                                    </tr>
                                    <tr>
                                        @foreach ($dates as $date)
                                            <th class="sub-header">Prévu</th>
                                            <th class="sub-header">In</th>
                                            <th class="sub-header">Out</th>
                                        @endforeach
                                    </tr>
                                </thead>

                                <tbody>
                                    @foreach ($groupe['agents'] as $agent)
                                        <tr>
                                            <td class="ps-2">{{ $agent->nom }} {{ explode(' ', trim($agent->prenom))[0] }}</td>
                                            @foreach ($dates as $date)
                                                @php
                                                    $jourDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                                    $key = $agent->id . '-' . $jourDate;
                                                    $planning = $plannings->get($key)?->first();

                                                    $pts = $planning ? $pointages->where('planning_id', $planning->id) : collect();
                                                    $ptDebut = optional($pts->firstWhere('motif', 'debut'))->heure;
                                                    $ptFin   = optional($pts->firstWhere('motif', 'fin'))->heure;
                                                @endphp

                                                {{-- Prévu --}}
                                                <td>
                                                    @if ($planning)
                                                        <div class="compact-badge bg-planning">
                                                            {{ \Carbon\Carbon::parse($planning->heure_debut)->format('H:i') }}<br>
                                                            {{ \Carbon\Carbon::parse($planning->heure_fin)->format('H:i') }}
                                                        </div>
                                                    @else
                                                        <span class="text-muted small">Repos</span>
                                                    @endif
                                                </td>

                                                {{-- In --}}
                                                <td>
                                                    @if ($ptDebut)
                                                        <div class="compact-badge bg-debut">{{ \Carbon\Carbon::parse($ptDebut)->format('H:i') }}</div>
                                                    @elseif($planning)
                                                        <span class="text-danger small">Manquant</span>
                                                    @else
                                                        -
                                                    @endif
                                                </td>

                                                {{-- Out --}}
                                                <td>
                                                    @if ($ptFin)
                                                        <div class="compact-badge bg-fin">{{ \Carbon\Carbon::parse($ptFin)->format('H:i') }}</div>
                                                    @elseif($planning)
                                                        <span class="text-danger small">Manquant</span>
                                                    @else
                                                        -
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
    @endforeach
</div>
@endsection