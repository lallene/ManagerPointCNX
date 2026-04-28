@extends('layouts.app')

@section('content')
<style>
    /* ... (vos styles existants conservés) ... */
    
    .table-sm th, .table-sm td {
        font-size: 0.75rem; /* Réduction pour fit les 7 jours */
        padding: 4px 2px !important;
    }
    
    .badge-status {
        font-size: 0.65rem;
        padding: 2px 4px;
        display: block;
        margin-top: 2px;
    }

    .bg-light-blue { background-color: #f0f7ff; }
</style>

<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>Liste des Pointages - Semaine {{ $selectedWeek }}</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Liste des Pointages</span>
                </div>
            </div>
        </div>
    </div>

    <form method="GET" action="{{ route('pointages.global') }}" class="mb-4 p-4 rounded shadow-sm bg-light border">
        <div class="row g-3">
            <div class="col-md-3">
                <label for="site_id" class="form-label fw-bold text-primary">Site</label>
                <select name="site_id" id="site_id" class="form-select filtre-select" {{ $filtreFixe ? 'disabled' : '' }}>
                    <option value="">-- Tous les sites --</option>
                    @foreach ($sites as $site)
                        <option value="{{ $site }}" {{ $selectedSiteId == $site ? 'selected' : '' }}>{{ $site }}</option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="projet_id" class="form-label fw-bold text-primary">Projet</label>
                <select name="projet_id" id="projet_id" class="form-select filtre-select" {{ $filtreFixe ? 'disabled' : '' }}>
                    <option value="">-- Tous les projets --</option>
                    @foreach ($projetsList as $projet)
                        <option value="{{ $projet->id }}" {{ $selectedProjetId == $projet->id ? 'selected' : '' }}>
                            {{ $projet->designation }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3">
                <label for="week" class="form-label fw-bold text-primary">Semaine</label>
                <select name="week" id="week" class="form-select filtre-select">
                    @foreach ($semaines as $semaine)
                        <option value="{{ $semaine['numero'] }}" {{ $selectedWeek == $semaine['numero'] ? 'selected' : '' }}>
                            {{ $semaine['label'] }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">
                    <i class="fas fa-filter"></i> Filtrer
                </button>
            </div>
        </div>

        <hr class="my-4">

        <div class="row g-3 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold text-success"><i class="fas fa-calendar-alt"></i> Date Début Export</label>
                <input type="date" name="start_date" id="start_date" class="form-control">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold text-success"><i class="fas fa-calendar-alt"></i> Date Fin Export</label>
                <input type="date" name="end_date" id="end_date" class="form-control">
            </div>
            <div class="col-md-4">
                <button type="button" onclick="downloadData()" class="btn btn-success w-100 fw-bold shadow-sm">
                    <i class="fas fa-file-download"></i> Télécharger
                </button>
            </div>
        </div>
    </form>

    <div class="accordion" id="accordionSites">
        @foreach ($resultat as $siteData)
            <div class="card mb-3 shadow-sm border-0">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-map-marker-alt"></i> Site : Abidjan {{ $siteData['site'] }}</h5>
                </div>
                <div class="card-body">
                    @foreach ($siteData['projets'] as $projetData)
                        <div class="projet-section mb-4">
                            <h5 class="projet-title"><i class="fas fa-project-diagram"></i> {{ $projetData['projet'] }}</h5>

                            @foreach ($projetData['groupes'] as $groupe)
                                <div class="group-card mb-3">
                                    <div class="manager-label mb-2">
                                        <i class="fas fa-user-tie"></i> Manager : {{ $groupe['manager'] }}
                                    </div>
                                    
                                    <div class="table-responsive">
                                        <table class="table table-bordered table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th rowspan="2" class="align-middle">Agent</th>
                                                    @foreach ($dates as $date)
                                                        <th colspan="4" class="text-center bg-light-blue">
                                                            {{ \Carbon\Carbon::parse($date)->translatedFormat('D d/m') }}
                                                        </th>
                                                    @endforeach
                                                </tr>
                                                <tr class="text-center" style="font-size: 0.7rem;">
                                                    @foreach ($dates as $date)
                                                        <th title="Début Planning">D.Pl</th>
                                                        <th title="Début Pointage">D.Pt</th>
                                                        <th title="Fin Planning">F.Pl</th>
                                                        <th title="Fin Pointage">F.Pt</th>
                                                    @endforeach
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($groupe['agents'] as $agent)
                                                    <tr>
                                                        <td class="fw-bold text-nowrap">
                                                            {{ $agent->prenom }} {{ $agent->nom }}
                                                        </td>

                                                        @foreach ($dates as $date)
                                                            @php
                                                                // On cherche les données via l'email pour éviter les requêtes SQL en boucle
                                                                // Idéalement, pré-calculez ceci dans le contrôleur
                                                                $data = $agent->pointages_week[$date] ?? null; 
                                                                
                                                                $hdPl = isset($data['h_dep_pl']) ? \Carbon\Carbon::parse($data['h_dep_pl'])->format('H:i') : '-';
                                                                $hfPl = isset($data['h_fin_pl']) ? \Carbon\Carbon::parse($data['h_fin_pl'])->format('H:i') : '-';
                                                                $hdPt = isset($data['h_dep_pt']) ? \Carbon\Carbon::parse($data['h_dep_pt'])->format('H:i') : '-';
                                                                $hfPt = isset($data['h_fin_pt']) ? \Carbon\Carbon::parse($data['h_fin_pt'])->format('H:i') : '-';

                                                                $isRetard = isset($data['h_dep_pt']) && isset($data['h_dep_pl']) && $data['h_dep_pt'] > $data['h_dep_pl'];
                                                                $isPartiTo = isset($data['h_fin_pt']) && isset($data['h_fin_pl']) && $data['h_fin_pt'] < $data['h_fin_pl'];
                                                            @endphp

                                                            <td class="text-center text-muted">{{ $hdPl }}</td>
                                                            <td class="text-center">
                                                                <span class="{{ $isRetard ? 'text-danger fw-bold' : '' }}">{{ $hdPt }}</span>
                                                                @if($isRetard)<span class="badge bg-danger badge-status">Retard</span>@endif
                                                            </td>
                                                            <td class="text-center text-muted">{{ $hfPl }}</td>
                                                            <td class="text-center">
                                                                <span class="{{ $isPartiTo ? 'text-warning fw-bold' : '' }}">{{ $hfPt }}</span>
                                                                @if($isPartiTo)<span class="badge bg-warning text-dark badge-status">Tôt</span>@endif
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
            </div>
        @endforeach
    </div>
</div>

<script>
    function downloadData() {
        const start = document.getElementById('start_date').value;
        const end = document.getElementById('end_date').value;
        if(!start || !end) {
            alert("Veuillez sélectionner un intervalle de dates.");
            return;
        }
        window.location.href = `{{ route('pointages.export') }}?start_date=${start}&end_date=${end}`;
    }
</script>
@endsection