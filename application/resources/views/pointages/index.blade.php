@extends('layouts.app')
<meta charset="UTF-8">
<meta http-equiv="Content-Language" content="fr">
<style>
    @media (min-width: 1400px) {
        .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
            max-width: 1392px !important;
        }
    }

    /* Style global tableau */
    .table {
        border-collapse: separate !important;
        border-spacing: 0 8px; /* espace vertical entre lignes */
    }

    /* En-tête tableau */
    .table thead th {
        background-color: #cfe2ff; /* bleu clair bootstrap */
        color: #084298; /* bleu foncé */
        font-weight: 700;
        vertical-align: middle;
        text-align: center;
        border-top-left-radius: 8px;
        border-top-right-radius: 8px;
        padding: 12px 10px;
        user-select: none;
    }

    /* Colonnes Agent */
    tbody td:first-child {
        font-weight: 600;
        color: #0d6efd;
        min-width: 160px;
        vertical-align: middle;
    }

    /* Cellules planning */
    tbody td {
        background-color: #f8f9fa;
        vertical-align: middle;
        min-width: 140px;
        padding: 8px 6px;
        transition: background-color 0.3s ease;
        border-radius: 6px;
    }

    /* Hover sur lignes */
    tbody tr:hover td {
        background-color: #e7f1ff;
        cursor: default;
    }

    /* Badge planning */
    .badge.bg-success {
        background-color: #198754 !important;
        color: white;
        font-weight: 600;
        font-size: 0.9rem;
        padding: 6px 12px;
        border-radius: 12px;
        display: inline-block;
        box-shadow: 0 2px 6px rgb(25 135 84 / 0.4);
        user-select: none;
    }

    /* Texte cellule vide */
    .text-muted {
        font-style: italic;
        font-size: 1.1rem;
        color: #adb5bd !important;
        user-select: none;
    }

    /* Boutons sélection semaine */
    .btn-sm {
        min-width: 75px;
        padding: 8px 10px;
        font-weight: 700;
        font-size: 0.9rem;
        user-select: none;
        border-radius: 6px;
        transition: background-color 0.25s ease;
    }
    .btn-sm:hover {
        filter: brightness(0.9);
    }

    /* Titre principal */
    .styled-title {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 2.5rem;
        color: #0d6efd;
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.15);
        letter-spacing: 1px;
        user-select: none;
        margin-top: 60px;
        margin-bottom: 0px;
        text-align: center;
    }
    .filtre-container {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 1rem;
    margin: 20px 0;
    flex-wrap: wrap;
}

.filtre-select {
    padding: 10px 15px;
    font-size: 16px;
    border-radius: 8px;
    border: 1px solid #ccc;
    background-color: #fff;
    color: #333;
    min-width: 180px;
    transition: all 0.2s ease-in-out;
}

.filtre-select:hover,
.filtre-select:focus {
    border-color: #007bff;
    box-shadow: 0 0 5px rgba(0, 123, 255, 0.5);
    outline: none;
}
 .site-title {
        color: #0d6efd;
        font-size: 1.8rem;
        font-weight: bold;
        border-left: 6px solid #0d6efd;
        padding-left: 15px;
        margin-bottom: 1.5rem;
        background-color: #e9f2ff;
        padding-top: 8px;
        padding-bottom: 8px;
        border-radius: 5px;
    }

    .projet-title {
        color: #495057;
        font-size: 1.4rem;
        font-weight: 600;
        border-left: 5px solid #6c757d;
        padding-left: 12px;
        margin-bottom: 1rem;
        background-color: #f1f3f5;
        padding-top: 6px;
        padding-bottom: 6px;
        border-radius: 4px;
    }

    .group-card {
        background: linear-gradient(to right, #f8f9fa, #ffffff);
        padding: 1rem 1.5rem;
        border: 1px solid #dee2e6;
        border-left: 4px solid #198754;
        border-radius: 8px;
        box-shadow: 0 3px 6px rgba(0,0,0,0.07);
        margin-bottom: 1.25rem;
        transition: all 0.2s ease-in-out;
    }

    .group-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 12px rgba(0,0,0,0.10);
    }

    .manager-label {
        color: #198754;
        font-size: 1.1rem;
        font-weight: bold;
    }

    .superviseur-list {
        margin-left: 1.5rem;
        margin-top: 0.5rem;
    }

    .superviseur-item {
        list-style-type: "👥 ";
        margin-bottom: 0.4rem;
        font-size: 0.95rem;
        color: #343a40;
    }
    .superviseur-nom {
    font-size: 1.4rem;
    font-weight: bold;
    color: #000;
}
.cell-horaire {
    font-size: 0.85rem;
    line-height: 1.1;
    padding: 4px 3px;
    min-width: 40px;
    max-width: 50px;
    white-space: nowrap;
}

.filtre-select {
    padding: 10px 12px;
    border-radius: 8px;
    font-size: 1rem;
    background-color: #fff;
    border: 1px solid #ced4da;
    box-shadow: inset 0 1px 2px rgba(0,0,0,0.05);
    transition: border-color 0.2s;
}

.filtre-select:focus {
    border-color: #0d6efd;
    outline: none;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

</style>


@section('content')
<div class="container">
     <div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>Liste des Pointages - Semaine {{ $selectedWeek }}</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Liste des Pointages - Semaine {{ $selectedWeek }}</span>
                </div>
            </div>
        </div>
    </div>

<form method="GET" action="{{ route('pointages.global') }}" class="mb-4 p-4 rounded shadow-sm bg-light border">
    <div class="row g-3">
        <div class="col-md-3">
            <label for="site_id" class="form-label fw-bold text-primary">Site</label>
            <select name="site_id" id="site_id" class="form-control filtre-select" {{ $filtreFixe ? 'disabled' : '' }}>
                <option value="">-- Tous les sites --</option>
                @foreach($sites as $site)
                    <option value="{{ $site }}" {{ $selectedSiteId == $site ? 'selected' : '' }}>{{ $site }}</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="projet_id" class="form-label fw-bold text-primary">Projet</label>
            <select name="projet_id" id="projet_id" class="form-control filtre-select" {{ $filtreFixe ? 'disabled' : '' }}>
                <option value="">-- Tous les projets --</option>
                @foreach($projetsList as $projet)
                    <option value="{{ $projet->id }}" {{ $selectedProjetId == $projet->id ? 'selected' : '' }}>
                        {{ $projet->designation }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3">
            <label for="week" class="form-label fw-bold text-primary">Semaine</label>
            <select name="week" id="week" class="form-control filtre-select">
                @foreach($semaines as $semaine)
                    <option value="{{ $semaine['numero'] }}" {{ $selectedWeek == $semaine['numero'] ? 'selected' : '' }}>
                        {{ $semaine['label'] }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="col-md-3 d-flex align-items-end">
            <button type="submit" class="btn btn-primary w-100 fw-bold shadow-sm">🔍 Filtrer</button>
        </div>
    </div>
</form>


    <div class="accordion" id="accordionSites">
        @foreach($resultat as $siteData)
            <div class="card mb-2">
                <div class="card-header">
                    <h5 class="mb-0"> Site : Abidjan {{ $siteData['site'] }}</h5>
                </div>
                <div class="card-body">
                    @foreach($siteData['projets'] as $projetData)
                        <h5 class="text-primary">Projet : {{ $projetData['projet'] }}</h5>

                        @foreach($projetData['groupes'] as $groupe)
                            <div class="border p-2 mb-3">
                                <strong>Manager : </strong> {{ $groupe['manager'] }}<br>
                                <div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead>
            <tr>
                <th rowspan="2">Agent</th>
                @foreach($dates as $date)
                    <th colspan="4" class="text-center">{{ \Illuminate\Support\Carbon::parse($date)->format('D d/m') }}</th>
                @endforeach
            </tr>
            <tr>
                @foreach($dates as $date)
                    <th>D pl.</th>
                    <th>D ptg</th>
                    <th>F pl.</th>
                    <th>F ptg</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($groupe['agents'] as $agent)
                @php
                    // Trouver l'utilisateur correspondant (une fois par agent)
                    $user = \App\Models\User::where('email', $agent->email)->first();
                @endphp
                <tr>
                    <td>{{ $agent->prenom }} {{ $agent->nom }}</td>

                    @foreach($dates as $date)
                        @php
                            $pointage = $user && isset($pointagesParAgentDate[$user->id][$date])
                                ? $pointagesParAgentDate[$user->id][$date]
                                : null;



                            $heureDebutPl = $pointage['heure_debut_planning'] ?? null;
                            $heureFinPl = $pointage['heure_fin_planning'] ?? null;
                            $heureDebutPt = $pointage['heure_arrivee'] ?? null;
                            $heureFinPt = $pointage['heure_depart'] ?? null;

                            $formatHeure = function($h) {
                            return $h ? \Illuminate\Support\Carbon::parse($h)->format('H:i') : '-';
                        };

                            $hdPl = $formatHeure($heureDebutPl);
                            $hfPl = $formatHeure($heureFinPl);
                            $hdPt = $formatHeure($heureDebutPt);
                            $hfPt = $formatHeure($heureFinPt);

                            $retard = $heureDebutPt && $heureDebutPl && $heureDebutPt > $heureDebutPl;
                            $partiTot = $heureFinPt && $heureFinPl && $heureFinPt < $heureFinPl;
                        @endphp

                        <td class="text-center">{{ $hdPl }}</td>

                        <td class="text-center">
                            {{ $hdPt }}
                            @if($retard)
                                <span class="badge bg-danger ms-1" title="Retard">Retard</span>
                            @endif
                        </td>

                        <td class="text-center">{{ $hfPl }}</td>

                        <td class="text-center">
                            {{ $hfPt }}
                            @if($partiTot)
                                <span class="badge bg-warning text-dark ms-1" title="Parti tôt">Parti tôt</span>
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
                    @endforeach
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
