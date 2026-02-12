@extends('layouts.app')
<meta charset="UTF-8">
<meta http-equiv="Content-Language" content="fr">

<style>
    @media (min-width: 1400px) {
        .container, .container-lg, .container-md, .container-sm, .container-xl, .container-xxl {
            max-width: 1392px !important;
        }
    }

    .table th, .table td {
        padding: 0.5rem;
        vertical-align: middle;
        text-align: center;
    }

    .bg-planning {
        background-color: #198754 !important;
        color: white;
        font-weight: bold;
        transition: background-color 0.3s;
        border-radius: 4px;
    }

    .bg-planning:hover {
        background-color: #157347 !important;
    }

    .legend {
        display: flex;
        justify-content: center;
        align-items: center;
        gap: 1rem;
        margin-top: 1rem;
    }

    .legend span {
        display: inline-block;
        width: 20px;
        height: 20px;
        border-radius: 4px;
    }

    .legend .planifie {
        background-color: #198754;
    }

    .legend .libre {
        background-color: #f8f9fa;
        border: 1px solid #ccc;
    }

    .heure-col {
        background-color: #f8f9fa;
        font-weight: bold;
        font-size: 1rem;
        text-align: right;
        padding-right: 10px;
        color: #333;
        min-width: 80px;
    }

    .jour-col {
        min-width: 120px;
        max-width: 120px;
    }

    .styled-title {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        font-size: 2.5rem;
        color: #0d6efd; /* bleu bootstrap */
        font-weight: 700;
        text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2);
        letter-spacing: 1px;
        user-select: none; /* empêche la sélection accidentelle */
        margin-top: 40px;
    }
</style>

@section('content')
<div class="container-fluid">
<div class="row">
        <div class="col-md-12">
            <div class="column_title">
                <h2>🗓️ Planning Hebdomadaire de {{ Auth::user()->name }}</h2>
                <div class="breadcrumb-custom d-none d-md-block">
                    <span>ManagerPoint</span> / <span>Planning Hebdomadaire de {{ Auth::user()->name }}</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Sélecteur de semaine --}}
    <div class="d-flex flex-wrap justify-content-center mb-4">
        @foreach ($semaines as $semaine)
            <form method="GET" action="{{ route('planning.journee.graphique') }}" class="me-2 mb-2">
                <input type="hidden" name="week" value="{{ $semaine['numero'] }}">
                <button type="submit"
                        class="btn btn-sm {{ $selectedWeek == $semaine['numero'] ? 'btn-primary' : 'btn-outline-info' }} fw-bold shadow-sm px-3 py-2">
                    S{{ $semaine['numero'] }}<br>
                    <small>{{ $semaine['debut'] }} - {{ $semaine['fin'] }}</small>
                </button>
            </form>
        @endforeach
    </div>

    {{-- Grille graphique --}}
    <div class="table-responsive shadow rounded">
        <table class="table table-bordered text-center align-middle">
            <thead class="table-info sticky-top" style="top: 0;">
                <tr>
                    <th class="heure-col">Heures</th>
                    @foreach ($dates as $date)
                        <th class="jour-col">
                            {{ ucfirst(\Carbon\Carbon::parse($date)->locale('fr_FR')->isoFormat('dddd')) }}
                            <br>
                            <small>{{ \Carbon\Carbon::parse($date)->format('d/m') }}</small>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @for ($h = 6; $h <= 21; $h++)
                    @php
                        $heureRef = \Carbon\Carbon::createFromTime($h, 0, 0)->format('H:i');
                    @endphp
                    <tr>
                        <th class="heure-col">{{ $heureRef }}</th>
                        @foreach ($dates as $date)
                            @php
                                $dayKey = \Carbon\Carbon::parse($date)->format('Y-m-d');
                                $planningsDuJour = $plannings->get($dayKey);
                                $inSlot = false;

                                if ($planningsDuJour) {
                                    foreach ($planningsDuJour as $p) {
                                        $debut = \Carbon\Carbon::parse($p->heure_debut)->format('H');
                                        $fin = \Carbon\Carbon::parse($p->heure_fin)->format('H');
                                        if ($h >= $debut && $h < $fin) {
                                            $inSlot = true;
                                            break;
                                        }
                                    }
                                }
                            @endphp
                            <td class="jour-col {{ $inSlot ? 'bg-planning' : '' }}">
                                {{-- contenu vide, juste fond coloré si planifié --}}
                            </td>
                        @endforeach
                    </tr>
                @endfor
            </tbody>
        </table>
    </div>

    {{-- Légende --}}
    <div class="legend">
        <div><span class="planifie"></span> Planifié</div>
        <div><span class="libre"></span> Libre</div>
    </div>
</div>
@endsection
