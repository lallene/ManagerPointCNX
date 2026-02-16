@extends('layouts.app')

@section('content')
<style>
    /* Style spécifique au planning */
    .table-planning {
        border-collapse: separate;
        border-spacing: 0;
    }

    .table-planning thead th {
        position: sticky;
        top: 0;
        z-index: 100;
        background-color: #174650; /* Cohérent avec vos autres headers */
        color: white;
        border: 1px solid #dee2e6;
    }

    .bg-planning {
        background-color: #198754 !important; /* Vert succès pour le travail */
        color: white;
        transition: all 0.2s ease;
        cursor: pointer;
    }

    .bg-planning:hover {
        background-color: #157347 !important;
        transform: scale(1.02);
    }

    .heure-col {
        background-color: #f8f9fa;
        font-weight: bold;
        text-align: right;
        padding-right: 15px !important;
        color: #174650;
        width: 100px;
    }

    .agent-col {
        min-width: 150px;
        font-size: 0.9rem;
    }

    .styled-title {
        font-family: 'Segoe UI', sans-serif;
        font-size: 2.2rem;
        color: #1d4750;
        font-weight: 700;
        text-align: center;
        margin-top: 50px;
        margin-bottom: 25px;
    }

    .day-selector .btn {
        min-width: 80px;
        border-radius: 8px;
        transition: 0.3s;
    }
</style>

<div class="container-fluid ">
<div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2> 📅 Planning Journalier - du groupe {{ $groupeId }}</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Planning Journalier - Groupe {{ $groupeId }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Sélecteur de jour --}}
    <div class="day-selector d-flex justify-content-center gap-2 mb-4 flex-wrap">
        @foreach ($joursSemaine as $jour)
            <a href="{{ route('planning.group.journee', ['date' => $jour['date']]) }}" 
               class="btn btn-sm {{ $selectedDay == $jour['date'] ? 'btn-primary shadow' : 'btn-outline-info' }}">
                <span class="text-uppercase font-weight-bold">{{ ucfirst($jour['label']) }}</span><br>
                <small>{{ \Carbon\Carbon::parse($jour['date'])->format('d/m') }}</small>
            </a>
        @endforeach
    </div>

    {{-- Grille planning --}}
    <div class="card shadow border-0">
        <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 700px;">
                <table class="table table-bordered table-planning mb-0">
                    <thead>
                        <tr>
                            <th class="heure-col">Heure</th>
                            @foreach ($agents as $agent)
                                <th class="agent-col text-center">
                                    <i class="fa fa-user-circle"></i><br>
                                    {{ $agent->nom }}
                                </th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @for ($h = 6; $h <= 21; $h++)
                            <tr>
                                <th class="heure-col">{{ sprintf('%02d', $h) }}:00</th>
                                @foreach ($agents as $agent)
                                    @php
                                        $key = $agent->id . '-' . $selectedDay;
                                        $planningList = $plannings->get($key);
                                        $planning = $planningList ? $planningList->first() : null;
                                        $inSlot = false;
                                        $label = "";

                                        if ($planning) {
                                            $debut = (int)\Carbon\Carbon::parse($planning->entree)->format('H');
                                            $fin = (int)\Carbon\Carbon::parse($planning->sortie)->format('H');
                                            $inSlot = $h >= $debut && $h < $fin;
                                            // Affiche le titre uniquement sur la première heure du créneau
                                            if ($h == $debut) $label = $planning->activite ?? 'POSTE';
                                        }
                                    @endphp
                                    <td class="{{ $inSlot ? 'bg-planning text-center align-middle' : '' }}"
                                        title="{{ $inSlot ? 'Travail : '.$agent->nom : 'Libre' }}">
                                        @if($inSlot && $label)
                                            <small class="font-weight-bold">{{ $label }}</small>
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @endfor
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection