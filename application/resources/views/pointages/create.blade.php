@extends('layouts.app')

@section('content')
    <style>
        body {
            background: url('https://www.transparenttextures.com/patterns/cubes.png'), #f2f6fc;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .container-custom {
            background-color: white;
            border-radius: 12px;
            padding: 40px 30px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            margin-top: 60px;
            margin-bottom: 40px;
        }

        .inline-notification {
            margin-top: 15px;
            margin-bottom: 20px;
        }


        .shift-info {
            background-color: #f8f9fa;
            border-left: 4px solid #0d6efd;
            padding: 12px 20px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 0.9rem;
        }

        .btn-record {
            font-weight: 600;
            border-radius: 8px;
            height: 45px;
            background-color: #2ecc71;
            border: none;
            color: white;
        }

        .btn-record:hover {
            background-color: #27ae60;
        }

        .form-label {
            color: #6c757d;
            font-size: 0.75rem;
            font-weight: 700;
        }
    </style>

    <div class="container container-custom">
        {{-- Entête --}}
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="column_title d-flex align-items-center">
                <div class="me-3" style="width: 5px; height: 40px; background-color: #2ecc71; border-radius: 10px;"></div>
                <h2 class="mb-0">📌 Interface de Pointage</h2>
            </div>
            <div class="text-muted small">ManagerPoint / Pointage Quotidien</div>
        </div>

        {{-- Notifications --}}
        <div class="inline-notification">
            @if (session('success'))
                <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if (session('error') || $errors->any())
                <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') ?: 'Une erreur est survenue.' }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
        </div>

        @if (!$planningDisponible)
            <div class="alert alert-warning border-0 shadow-sm py-4 text-center">
                <i class="fas fa-calendar-times fa-2x mb-3 text-warning"></i>
                <p class="mb-0 fw-bold">Aucun planning prévu pour aujourd'hui. Pointage impossible.</p>
            </div>
        @else
            {{-- Info Agent & Shift --}}
            <div class="shift-info d-flex justify-content-between align-items-center shadow-sm">
                <div class="d-flex align-items-center">
                    <i class="fas fa-user-circle me-2 text-primary fa-lg"></i>
                    <span>Agent : <strong>{{ Auth::user()->name }}</strong> | Prévu :
                        <strong>{{ \Carbon\Carbon::parse($planningDisponible->entree)->format('H:i') }} –
                            {{ \Carbon\Carbon::parse($planningDisponible->sortie)->format('H:i') }}</strong></span>
                </div>
                <span class="badge bg-primary px-3">SEMAINE {{ $currentWeek }}</span>
            </div>

            {{-- Formulaire --}}
            <div class="bg-light p-4 rounded-3 mb-5">
                <form action="{{ route('pointage.store') }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-md-4">
                        <label class="form-label">DATE</label>
                        <input type="text" class="form-control border-0"
                            value="{{ \Carbon\Carbon::parse($currentDate)->translatedFormat('d F Y') }}" readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">HEURE ACTUELLE</label>
                        <input type="text" class="form-control border-0 fw-bold text-primary" value="{{ $currentTime }}"
                            readonly>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label text-uppercase">Action</label>
                        <select name="action" id="action" class="form-select border-primary fw-bold" required>
                            @php
                                $actions = [
                                    'debut' => '▶️ Démarrer mon shift',
                                    'debutpause' => '☕ Partir en pause',
                                    'finpause' => '🔙 Revenir de pause',
                                    'fin' => '🏁 Terminer mon shift',
                                ];
                            @endphp
                            @if ($prochaineAction == 'termine')
                                <option value="" selected disabled>✅ Journée terminée</option>
                            @else
                                @foreach ($actions as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ $val == $prochaineAction ? 'selected' : 'disabled' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-record w-100 shadow-sm"
                            @if ($prochaineAction == 'termine') disabled @endif>
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>

            {{-- Historique --}}
            <div class="d-flex align-items-center mb-3 text-secondary">
                <i class="fas fa-history me-2"></i>
                <h6 class="mb-0 fw-bold text-uppercase" style="letter-spacing: 1px;">Aujourd'hui</h6>
            </div>

            {{-- Changement ici : on utilise $pointageDuJour --}}
            @if (!$pointageDuJour)
                <div class="text-center p-5 border rounded-3 bg-white shadow-sm">
                    <p class="text-muted mb-0">En attente du premier pointage...</p>
                </div>
            @else
                <div class="table-responsive">
                    <table
                        class="table table-hover table-bordered bg-white shadow-sm text-center align-middle rounded-3 overflow-hidden">
                        <thead class="table-light">
                            <tr class="small text-uppercase text-muted">
                                <th>Entrée</th>
                                <th>Début Pause</th>
                                <th>Fin Pause</th>
                                <th>Sortie</th>
                                <th class="table-info text-dark">Total Travail</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td class="fw-bold text-success">
                                    {{ $pointageDuJour->entree ? \Carbon\Carbon::parse($pointageDuJour->entree)->format('H:i') : '--:--' }}
                                </td>
                                <td>{{ $pointageDuJour->pause_debut ? \Carbon\Carbon::parse($pointageDuJour->pause_debut)->format('H:i') : '--:--' }}
                                </td>
                                <td>{{ $pointageDuJour->pause_fin ? \Carbon\Carbon::parse($pointageDuJour->pause_fin)->format('H:i') : '--:--' }}
                                </td>
                                <td class="fw-bold text-danger">
                                    {{ $pointageDuJour->sortie && $pointageDuJour->sortie != '00:00:00' ? \Carbon\Carbon::parse($pointageDuJour->sortie)->format('H:i') : '--:--' }}
                                </td>
                                <td class="table-info fw-bold" style="font-size: 1.1rem;">
                                    {{ $pointageDuJour->temps_formatte }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
@endsection
