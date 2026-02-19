@extends('layouts.app')

@section('content')
    <meta charset="UTF-8">
    <meta http-equiv="Content-Language" content="fr">

    <style>
        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 1600px;
            }
        }

        /* Structure du Tableau */
        #agentTable {
            border-collapse: separate !important;
            border-spacing: 0 2px;
        }

        #agentTable thead th {
            background-color: #0d47a1;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            padding: 12px 8px;
            border: none;
        }

        #agentTable tbody td {
            background-color: #ffffff;
            vertical-align: middle;
            padding: 8px;
            border: 1px solid #dee2e6;
        }

        /* En-tête de Groupe Projet */
        .project-group-header {
            background-color: #f8f9fa !important;
            font-weight: 800;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-left: 5px solid #0d6efd !important;
        }

        /* Inputs et Badges */
        .heure-input {
            width: 82px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            text-align: center;
            font-size: 0.85rem;
            padding: 2px;
            transition: all 0.2s;
        }

        .heure-input:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        /* Style pour les champs verrouillés (Dates passées) */
        .heure-input[readonly] {
            background-color: #f1f3f5 !important;
            color: #adb5bd;
            cursor: not-allowed;
            border-color: #dee2e6;
        }

        .date-badge {
            font-size: 0.7rem;
            display: block;
            color: #e3f2fd;
            font-weight: 400;
        }

        .btn-week {
            min-width: 110px;
            margin: 4px;
            padding: 8px;
            border-radius: 8px;
            font-size: 0.9rem;
        }

        .filter-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
    </style>

    <div class="container-fluid py-4">

        {{-- Alertes de Session --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if (session('error'))
            <div class="alert alert-danger alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fas fa-exclamation-triangle me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        {{-- Header --}}
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm border-bottom">
                    <div>
                        <h2 class="fw-bold text-primary mb-0">🗓️ Planification par Projet</h2>
                        <p class="text-muted mb-0 small">Vue consolidée pour le pilotage multi-sites</p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary fs-6 px-4 py-2 rounded-pill shadow-sm">Semaine
                            {{ $selectedWeekNum }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtres par Catégories --}}
        <div class="card filter-card mb-3">
            <div class="card-body py-2 text-center">
                <form method="GET" action="{{ route('planification') }}" id="filterForm"
                    class="d-inline-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="week" value="{{ $selectedWeekNum }}">
                    <span class="small fw-bold text-secondary text-uppercase me-2">Filtrer par rôle :</span>
                    @foreach ($categoriesDispo as $cat)
                        @php $isChecked = in_array($cat, (array)$fonctionsChoisies); @endphp
                        <input type="checkbox" class="btn-check" name="fonctions[]" id="cat_{{ Str::slug($cat) }}"
                            value="{{ $cat }}" {{ $isChecked ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="btn btn-sm {{ $isChecked ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3"
                            for="cat_{{ Str::slug($cat) }}">
                            {{ $cat }}
                        </label>
                    @endforeach
                </form>
            </div>
        </div>

        {{-- Sélecteur de Semaines --}}
        <div class="d-flex flex-wrap justify-content-center mb-4">
            @foreach ($semaines as $sem)
                <form method="GET" action="{{ route('planification') }}">
                    @foreach ((array) $fonctionsChoisies as $f)
                        <input type="hidden" name="fonctions[]" value="{{ $f }}">
                    @endforeach
                    <input type="hidden" name="week" value="{{ $sem['num'] }}">
                    <button type="submit"
                        class="btn btn-week {{ $selectedWeekNum == $sem['num'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary' }}">
                        <span class="fw-bold">S{{ $sem['num'] }}</span><br>
                        <small>{{ str_replace("Semaine {$sem['num']} (", '', rtrim($sem['label'], ')')) }}</small>
                    </button>
                </form>
            @endforeach
        </div>

        {{-- Bloc Actions : Importation et Exportation --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    <div class="col-md-7 border-end">
                        <form action="{{ route('plannings.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <input type="hidden" name="week" value="{{ $selectedWeekNum }}">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold mb-0 text-uppercase">
                                    <i class="fas fa-file-excel me-1 text-success"></i> Importer Planning Excel
                                    (S{{ $selectedWeekNum }})
                                </label>
                                <a href="{{ asset('templates/masque_planning_hebdo.xlsx') }}"
                                    class="text-success small fw-bold text-decoration-none">
                                    <i class="fas fa-download"></i> Télécharger le masque
                                </a>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="file" name="file" class="form-control" required>
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fas fa-upload me-1"></i> Charger
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-5 text-center">
                        <label class="form-label small fw-bold mb-2 text-uppercase text-secondary">Exportation
                            rapide</label>
                        <div class="d-flex justify-content-center gap-2">
                            <button type="button" id="exportExcel" class="btn btn-outline-success btn-sm px-3"><i
                                    class="fas fa-file-excel"></i> Excel</button>
                            <button type="button" id="exportJpg" class="btn btn-outline-secondary btn-sm px-3"><i
                                    class="fas fa-image"></i> Image</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tableau de Saisie --}}
        <form method="POST" action="{{ route('planning.store') }}">
            @csrf
            <input type="hidden" name="week" value="{{ $selectedWeekNum }}">
            <div class="card border-0 shadow-sm">
                <div class="table-responsive">
                    <table class="table align-middle" id="agentTable">
                        <thead>
                            <tr>
                                <th style="min-width: 250px;">Collaborateur / Manager</th>
                                @php $joursFr = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']; @endphp
                                @foreach ($joursFr as $idx => $nom)
                                    @php $dateHeader = \Carbon\Carbon::now()->setISODate(2026, $selectedWeekNum)->startOfWeek()->addDays($idx); @endphp
                                    <th>{{ $nom }} <span
                                            class="date-badge">{{ $dateHeader->format('d/m') }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $currentProject = null; @endphp
                            @forelse ($agents as $agent)
                                @if ($currentProject !== $agent->nom_projet)
                                    @php $currentProject = $agent->nom_projet; @endphp
                                    <tr>
                                        <td colspan="8" class="project-group-header py-2 ps-3">
                                            <i class="fas fa-folder-open me-2 text-primary"></i> PROJET :
                                            {{ $currentProject }}
                                        </td>
                                    </tr>
                                @endif

                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $agent->prenom }}
                                            {{ $agent->nom }}</div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge bg-light text-primary border"
                                                style="font-size: 0.6rem;">{{ $agent->fonction }}</span>
                                            <span class="badge bg-light text-muted border" style="font-size: 0.6rem;">M:
                                                {{ $agent->nom_manager }}</span>
                                        </div>
                                    </td>
                                    @foreach (range(0, 6) as $i)
                                        @php
                                            $currentDate = \Carbon\Carbon::now()
                                                ->setISODate(2026, $selectedWeekNum)
                                                ->startOfWeek()
                                                ->addDays($i)
                                                ->format('Y-m-d');
                                            $key = $agent->id . '-' . $currentDate;
                                            $plan = $plannings[$key] ?? null;
                                            $isLocked = \Carbon\Carbon::parse($currentDate)->isBefore(
                                                \Carbon\Carbon::today(),
                                            );
                                        @endphp
                                        <td>
                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                <input type="time"
                                                    name="plannings[{{ $agent->id }}][{{ $currentDate }}][entree]"
                                                    class="heure-input" value="{{ $plan->entree ?? '' }}"
                                                    {{ $isLocked ? 'readonly' : '' }}>
                                                <input type="time"
                                                    name="plannings[{{ $agent->id }}][{{ $currentDate }}][sortie]"
                                                    class="heure-input" value="{{ $plan->sortie ?? '' }}"
                                                    {{ $isLocked ? 'readonly' : '' }}>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">Aucun agent à planifier pour vos projets.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-end mt-4 mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow border-0"
                    style="background: linear-gradient(45deg, #0d47a1, #007bff);">
                    <i class="fas fa-save me-2"></i> Enregistrer la Semaine {{ $selectedWeekNum }}
                </button>
            </div>
        </form>
    </div>

    {{-- JS --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.getElementById('exportExcel').onclick = () => {
                const wb = XLSX.utils.table_to_book(document.getElementById('agentTable'));
                XLSX.writeFile(wb, "Planning_S{{ $selectedWeekNum }}.xlsx");
            };
            document.getElementById('exportJpg').onclick = async () => {
                const container = document.querySelector('.table-responsive');
                const canvas = await html2canvas(container);
                const link = document.createElement('a');
                link.download = 'Planning_S{{ $selectedWeekNum }}.jpg';
                link.href = canvas.toDataURL('image/jpeg');
                link.click();
            };
        });
    </script>
@endsection
