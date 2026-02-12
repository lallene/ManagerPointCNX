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
</style>


@section('content')
<div class="container-fluid">
<h2 class="mb-4 text-center styled-title"></h2>
<div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>🗓️ Planning Hebdomadaire - Groupe d'agents</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Planning Hebdomadaire - Groupe d'agents</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Grille de sélection des semaines --}}
    <div class="d-flex flex-wrap justify-content-center mb-4">
        @foreach ($semaines as $semaine)
            <form method="GET" action="{{ route('planning.group') }}" class="me-2 mb-2">
                <input type="hidden" name="week" value="{{ $semaine['numero'] }}">
                <button type="submit" class="btn btn-sm {{ $selectedWeek == $semaine['numero'] ? 'btn-primary' : 'btn-outline-info' }} fw-bold shadow-sm">
                    S{{ $semaine['numero'] }}<br>
                    <small>{{ $semaine['debut'] }} - {{ $semaine['fin'] }}</small>
                </button>
            </form>
        @endforeach
    </div>

    {{-- Tableau de planning --}}
    <div class="table-responsive">
        <table class="table table-bordered table-sm align-middle table-hover">
            <thead class="table-info text-center">
                <tr>
                    <th>Agent</th>
                    @foreach ($dates as $date)
                        @php
                            $jour = \Carbon\Carbon::parse($date)->locale('fr_FR')->isoFormat('dddd');
                        @endphp
                        <th>
                            {{ ucfirst($jour) }}<br>
                            <small>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($agents as $agent)
                    <tr>
                        <td>{{ $agent->nom }} {{ $agent->prenom }}</td>
                        @foreach ($dates as $date)
                            @php
                                $jourDate = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                $key = $agent->id . '-' . $jourDate;
                                $planning = $plannings->get($key);
                                $planning = $planning ? $planning->first() : null;
                            @endphp
                            <td class="text-center" style="min-width: 140px;">
                                @if ($planning)

                                    <span class="badge bg-success">🕓 {{\Carbon\Carbon::parse($planning->heure_debut)->format('H:i')}} - {{ \Carbon\Carbon::parse($planning->heure_fin)->format('H:i') }}</span>
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
