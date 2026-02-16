@extends('layouts.app')

@section('content')
    <style>
        /* Optimisation container */
        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 1400px !important;
            }
        }

        /* Style de la grille */
        .table-graph {
            border-spacing: 0;
            border-collapse: separate;
        }

        .table-graph th,
        .table-graph td {
            border: 1px solid #dee2e6;
            padding: 10px 5px;
        }

        .agent-col {
            position: sticky;
            left: 0;
            background: #fff !important;
            z-index: 10;
            width: 200px;
            text-align: left !important;
            font-weight: bold;
            border-right: 3px solid #0d6efd !important;
        }

        /* Cellule de planning */
        .slot-active {
            background-color: #198754 !important;
            /* Vert */
            color: white;
            border-color: #157347 !important;
        }

        .slot-none {
            background-color: #f8f9fa;
            opacity: 0.6;
        }

        .hour-header {
            background-color: #e9ecef;
            font-weight: bold;
            font-size: 0.8rem;
            min-width: 45px;
        }

        /* Navigation jours */
        .day-nav .btn {
            min-width: 100px;
            transition: all 0.2s;
        }

        .day-nav .btn.active {
            background-color: #0d6efd;
            color: white;
            border-color: #0d6efd;
            box-shadow: 0 4px 8px rgba(13, 110, 253, 0.3);
        }
    </style>

    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>👥 Planning Groupe : <span class="text-primary">{{ $managerName }}</span></h2>
                    <p class="text-muted">Vue détaillée de la journée du
                        <strong>{{ \Carbon\Carbon::parse($selectedDay)->isoFormat('LL') }}</strong></p>
                </div>
            </div>
        </div>

        {{-- Sélecteur de Jour (Navigation horizontale) --}}
        <div class="d-flex justify-content-center gap-2 mb-4 day-nav">
            @foreach ($joursSemaine as $jour)
                <a href="{{ route('planning.journee.graphique', ['date' => $jour['date']]) }}"
                    class="btn btn-sm {{ $jour['active'] ? 'active' : 'btn-outline-secondary' }} shadow-sm">
                    <span class="text-capitalize">{{ $jour['label'] }}</span><br>
                    <small>{{ \Carbon\Carbon::parse($jour['date'])->format('d/m') }}</small>
                </a>
            @endforeach
        </div>

        <div class="card shadow border-0">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-graph mb-0 text-center">
                        <thead>
                            <tr>
                                <th class="agent-col bg-light">Agents</th>
                                @for ($h = 7; $h <= 21; $h++)
                                    <th class="hour-header">{{ $h }}h</th>
                                @endfor
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($agents as $agent)
                                @php
                                    // On récupère les données formatées envoyées par le contrôleur
                                    $plan = $dataPlanning[$agent->id] ?? ['in' => null, 'out' => null];

                                    // Extraction de l'heure (ex: "08" de "08:30") pour le calcul des colonnes
$startH = $plan['in'] ? (int) explode(':', $plan['in'])[0] : null;
$endH = $plan['out'] ? (int) explode(':', $plan['out'])[0] : null;
                                @endphp
                                <tr>
                                    <td class="agent-col">
                                        <div class="ps-2 text-start">
                                            {{ $agent->prenom }} {{ $agent->nom }}
                                            <div class="small text-muted fw-normal" style="font-size: 0.7rem;">
                                                {{ $agent->fonction }}</div>
                                        </div>
                                    </td>

                                    {{-- On boucle de 7h à 21h pour dessiner la grille --}}
                                    @for ($h = 7; $h <= 21; $h++)
                                        @php
                                            // On vérifie si l'heure actuelle de la colonne est comprise dans la plage de l'agent
                                            $isActive = $startH !== null && $h >= $startH && $h < $endH;
                                        @endphp
                                        <td class="{{ $isActive ? 'slot-active' : 'slot-none' }}">
                                            {{-- On peut laisser vide ou mettre un petit indicateur --}}
                                        </td>
                                    @endfor
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        {{-- Légende --}}
        <div class="d-flex justify-content-center gap-4 mt-4">
            <div class="d-flex align-items-center gap-2">
                <div style="width: 20px; height: 20px; background: #198754; border-radius: 4px;"></div>
                <span class="small fw-bold">En poste</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <div style="width: 20px; height: 20px; background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 4px;">
                </div>
                <span class="small fw-bold">Repos / Absent</span>
            </div>
        </div>
    </div>
@endsection
