@extends('layouts.app')

@section('content')
    <style>
        /* Arrière-plan avec design entreprise */
       body {
    background: url('https://www.transparenttextures.com/patterns/cubes.png'), #f2f6fc;
    background-size: cover;
    background-repeat: repeat;
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.container {
    background-color: rgba(255, 255, 255, 0.95);
    padding: 40px;
    border-radius: 12px;
    box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
}

/* Nouveau style de titre */
h3.page-title {
    font-size: 2.5rem;
    font-weight: 700;
    color: #004085;
    text-align: center;
    position: relative;
    margin-bottom: 2rem;
}
h3.page-title::after {
    content: "";
    display: block;
    width: 60px;
    height: 4px;
    background-color: #007bff;
    margin: 10px auto 0;
    border-radius: 2px;
}


        /* Conteneur principal */
        .container-custom {
            background-color: white;
            border-radius: 12px;
            padding: 40px 30px;
            box-shadow: 0 0 10px rgba(0,0,0,0.08);
            margin-top: 100px;
        }

        /* Titres */
        h2, h3 {
            font-family: 'Roboto', sans-serif;
            font-weight: 700;
            color: #0056b3;
            text-align: center;
        }

        h3 {
            font-size: 2rem;
            margin-bottom: 2rem;
        }

        h2 {
            font-size: 1.5rem;
            margin-top: 3rem;
        }

        /* Formulaire */
        .form-group label {
            font-weight: 600;
            font-size: 0.95rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            font-size: 0.95rem;
        }

        button.btn-success {
            font-weight: 600;
            padding: 10px 22px;
            font-size: 0.95rem;
            border-radius: 8px;
            background-color: #198754;
            border: none;
            transition: all 0.2s ease-in-out;
        }

        button.btn-success:hover {
            background-color: #146c43;
        }

        /* Tableau */
        table.table-bordered th,
        table.table-bordered td {
            text-align: center;
            vertical-align: middle;
            font-size: 0.9rem;
        }

        table.table-bordered th {
            background-color: #f0f2f5;
            font-weight: 600;
        }

        table.table-bordered tbody tr:nth-child(even) {
            background-color: #f9fafb;
        }

        table.table-bordered tbody tr:hover {
            background-color: #e9f5ff;
        }

        /* Alertes */
        .alert-danger ul {
            margin-bottom: 0;
        }

        .alert-danger li {
            font-size: 0.9rem;
        }

        /* Responsive */
        @media screen and (max-width: 768px) {
            .form-group {
                width: 100% !important;
            }
        }
    </style>

    {{-- Google Font --}}
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
<div class="container container-custom">
    @if ($planningDisponible->isEmpty())
           <div style="
        background-color: #fff3cd;
        color: #856404;
        border: 1px solid #ffeeba;
        padding: 15px;
        border-radius: 8px;
        margin: 20px 0;
        font-size: 16px;
    ">
        <strong>Attention :</strong> Vous n'avez pas été planifié pour aujourd'hui.
    </div>
    @else


        <h3 class="mb-4">📌 Interface de Pointage — Employé {{ Auth::user()->name }}</h3>

        <form action="{{ route('pointages.store') }}" method="POST" class="d-flex align-items-center gap-3 flex-wrap">
            @csrf

            {{-- Semaine --}}
            <div class="form-group flex-grow-1" style="min-width: 120px;">
                <label for="semaine" class="form-label">Semaine</label>
                <input type="number" name="semaine" id="semaine" class="form-control"
                    value="{{ $currentWeek }}" readonly>
            </div>

            {{-- Date --}}
            <div class="form-group flex-grow-1" style="min-width: 150px;">
                <label for="date" class="form-label">Date</label>
                <input type="date" name="date" id="date" class="form-control"
                    value="{{ $currentDate }}" readonly>
            </div>

            {{-- Heure --}}
            <div class="form-group flex-grow-1" style="min-width: 120px;">
                <label for="heure" class="form-label">Heure</label>
                <input type="time" name="heure" id="heure" class="form-control"
                    value="{{ $currentTime }}" readonly>
            </div>

            {{-- Action --}}
            <div class="form-group flex-grow-1" style="min-width: 180px;">
                <label for="action" class="form-label">Action</label>
                <select name="action" id="action" class="form-select" required>
                    <option value="" disabled selected>-- Choisir une action --</option>
                    @php
                        $actions = [
                            'debut' => 'Début de shift',
                            'debutpause' => 'Début Pause',
                            'finpause' => 'Fin Pause',
                            'fin' => 'Fin de shift',
                        ];
                    @endphp
                    @foreach ($actions as $val => $label)
                        <option value="{{ $val }}" {{ $val !== $prochaineAction ? 'disabled' : '' }}>
                            {{ $label }}
                        </option>
                    @endforeach
                </select>
            </div>

            {{-- Bouton submit --}}
            <div class="form-group align-self-end">
                <button type="submit" class="btn btn-success" @if (!$planningDisponible) disabled @endif>
                    Enregistrer
                </button>
            </div>
        </form>

        {{-- Historique des pointages du jour --}}
        <h2>Historique du jour ({{ \Carbon\Carbon::parse($currentDate)->translatedFormat('d F Y') }})</h2>

        @if($pointagesDuJour->isEmpty())
            <p class="text-muted text-center">Aucun pointage enregistré aujourd'hui.</p>
        @else
            <table class="table table-sm table-bordered mt-3">
                <thead>
                    <tr>
                        <th>Heure</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pointagesDuJour as $pointage)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($pointage->heure)->format('H:i') }}</td>
                            <td>{{ ucfirst($pointage->motif) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Affichage des erreurs --}}
        @if ($errors->any())
            <div class="alert alert-danger mt-4">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

    </div>
@endif

@endsection
