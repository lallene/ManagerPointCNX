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

        /* Design du Tableau */
        #agentTable {
            border-collapse: separate !important;
            border-spacing: 0 5px;
        }

        #agentTable thead th {
            background-color: #e3f2fd;
            color: #0d47a1;
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
            transition: all 0.2s;
        }

        #agentTable tbody tr:hover td {
            background-color: #f1f8ff;
        }

        /* Style des Inputs Time */
        .heure-input {
            width: 82px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            text-align: center;
            font-size: 0.85rem;
            padding: 2px;
        }

        .text-success.small:hover {
            text-decoration: underline !important;
            color: #157347 !important;
        }

        .heure-input:focus {
            border-color: #0d6efd;
            outline: none;
            box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.15);
        }

        /* Badges et Semaines */
        .date-badge {
            font-size: 0.7rem;
            display: block;
            color: #6c757d;
            font-weight: 400;
        }

        .btn-week {
            min-width: 100px;
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

        {{-- Header : Titre et Infos --}}
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>🗓️ Planification Hebdomadaire</h2>
                    <div class="breadcrumb-custom d-none d-md-block">
                        <span>ManagerPoint</span> / <span>Projet :
                            <strong>{{ $agents->first()->nom_projet ?? 'N/A' }}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12">
                <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
                    <div>
                        <h2 class="fw-bold text-primary mb-0"></h2>
                        <p class="text-muted mb-0 small"></strong></p>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-primary fs-6 px-3 py-2">Semaine {{ $selectedWeekNum }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtre par Catégories (Mapping) --}}
        <div class="card filter-card mb-3">
            <div class="card-body py-3">
                <h6 class="fw-bold mb-3 text-secondary small text-uppercase"><i class="fas fa-filter me-2"></i>Filtrer par
                    catégorie</h6>
                <form method="GET" action="{{ route('planification') }}" id="filterForm"
                    class="d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="week" value="{{ $selectedWeekNum }}">

                    @foreach ($categoriesDispo as $cat)
                        @php $isChecked = in_array($cat, (array)request('fonctions', $fonctionsChoisies)); @endphp
                        <input type="checkbox" class="btn-check" name="fonctions[]" id="cat_{{ Str::slug($cat) }}"
                            value="{{ $cat }}" {{ $isChecked ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="btn btn-sm {{ $isChecked ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3"
                            for="cat_{{ Str::slug($cat) }}">
                            {{ $cat }}
                        </label>
                    @endforeach

                    @if (request('fonctions'))
                        <a href="{{ route('planification', ['week' => $selectedWeekNum]) }}"
                            class="btn btn-sm btn-link text-danger ms-auto">Effacer les filtres</a>
                    @endif
                </form>
            </div>
        </div>

        {{-- Sélecteur de Semaines (avec conservation des filtres) --}}
        <div class="d-flex flex-wrap justify-content-center mb-4">
            @foreach ($semaines as $sem)
                <form method="GET" action="{{ route('planification') }}">
                    @foreach ((array) request('fonctions', $fonctionsChoisies) as $f)
                        <input type="hidden" name="fonctions[]" value="{{ $f }}">
                    @endforeach
                    <input type="hidden" name="week" value="{{ $sem['numero'] }}">
                    <button type="submit"
                        class="btn btn-week {{ $selectedWeekNum == $sem['numero'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary' }}">
                        <span class="fw-bold">S{{ $sem['numero'] }}</span><br>
                        <small>{{ $sem['debut'] }} - {{ $sem['fin'] }}</small>
                    </button>
                </form>
            @endforeach
        </div>

        {{-- Actions Export/Import --}}
        {{-- Actions Export/Import --}}
        <div class="row g-3 mb-4">
            <div class="col-md-7">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <form action="{{ route('plannings.import') }}" method="POST" enctype="multipart/form-data"
                        class="row g-2 align-items-end">
                        @csrf
                        <div class="col-8">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-bold mb-0">IMPORTER PLANNING (EXCEL)</label>
                                {{-- Nouveau bouton Masque --}}
                                <a href="{{ asset('templates/masque_planning_hebdo.xlsx') }}"
                                    class="text-success small fw-bold text-decoration-none">
                                    <i class="fas fa-file-download"></i> Télécharger le masque
                                </a>
                            </div>
                            <input type="file" name="file" class="form-control form-control-sm" required>
                            <input type="hidden" name="week" value="{{ $selectedWeekNum }}">
                        </div>
                        <div class="col-4">
                            <button type="submit" class="btn btn-success btn-sm w-100">📥 Charger</button>
                        </div>
                    </form>
                </div>
            </div>
            <div class="col-md-5 text-center">
                <div class="card border-0 shadow-sm p-3 h-100">
                    <label class="form-label small fw-bold mb-2">EXPORTATION</label>
                    <div class="d-flex justify-content-center gap-2">
                        <button id="exportPdf" class="btn btn-outline-danger btn-sm">PDF</button>
                        <button id="exportExcel" class="btn btn-outline-success btn-sm">Excel</button>
                        <button id="exportJpg" class="btn btn-outline-secondary btn-sm">Image</button>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tableau de Saisie --}}
        <form method="POST" action="{{ route('planning.store') }}">
            @csrf
            <input type="hidden" name="week" value="{{ $selectedWeekNum }}">

            <div class="card border-0 shadow-sm">
                <div class="table-responsive p-1">
                    <table class="table align-middle" id="agentTable">
                        <thead>
                            <tr>
                                <th style="min-width: 220px;">Agent / Fonction</th>
                                @php $joursFr = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche']; @endphp
                                @foreach ($joursFr as $idx => $nom)
                                    @php $dateHeader = \Carbon\Carbon::now()->setISODate(date('Y'), $selectedWeekNum)->startOfWeek()->addDays($idx); @endphp
                                    <th>{{ $nom }}<span
                                            class="date-badge">{{ $dateHeader->format('d/m') }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($agents as $agent)
                                <tr>
                                    <td class="ps-3">
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $agent->nom }}
                                            {{ $agent->prenom }}</div>
                                        <span class="badge bg-light text-primary border"
                                            style="font-size: 0.65rem;">{{ $agent->fonction }}</span>
                                    </td>
                                    @foreach (range(0, 6) as $i)
                                        @php
                                            $currentDate = \Carbon\Carbon::now()
                                                ->setISODate(date('Y'), $selectedWeekNum)
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
                                                    class="heure-input heure-debut {{ $isLocked ? 'bg-light text-muted' : '' }}"
                                                    value="{{ $plan->entree ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>

                                                <input type="time"
                                                    name="plannings[{{ $agent->id }}][{{ $currentDate }}][sortie]"
                                                    class="heure-input heure-fin {{ $isLocked ? 'bg-light text-muted' : '' }}"
                                                    value="{{ $plan->sortie ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5 text-muted">Aucun agent trouvé dans ces
                                        catégories.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-end mt-4 mb-5">
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow border-0"
                    style="background: linear-gradient(45deg, #0d6efd, #004085);">
                    <i class="fas fa-save me-2"></i> Enregistrer le planning S{{ $selectedWeekNum }}
                </button>
            </div>
        </form>
    </div>

    {{-- Scripts d'export --}}
    <script src="https://cdn.jsdelivr.net/npm/html2canvas@1.4.1/dist/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/jspdf@2.5.1/dist/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-calcul + 8h lors de la saisie
            document.querySelectorAll('.heure-debut').forEach(input => {
                input.addEventListener('change', function() {
                    if (!this.value) return;
                    const [h, m] = this.value.split(':').map(Number);
                    const fin =
                        `${((h + 8) % 24).toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
                    this.closest('.d-flex').querySelector('.heure-fin').value = fin;
                });
            });

            // Export Excel
            document.getElementById('exportExcel').addEventListener('click', () => {
                const wb = XLSX.utils.table_to_book(document.querySelector('#agentTable'), {
                    sheet: "S{{ $selectedWeekNum }}"
                });
                XLSX.writeFile(wb, "Planning_S{{ $selectedWeekNum }}.xlsx");
            });

            // Export Image
            document.getElementById('exportJpg').addEventListener('click', async () => {
                const canvas = await html2canvas(document.querySelector('.table-responsive'), {
                    scale: 2
                });
                const link = document.createElement('a');
                link.download = 'Planning_S{{ $selectedWeekNum }}.jpg';
                link.href = canvas.toDataURL('image/jpeg', 0.9);
                link.click();
            });
        });
    </script>

@endsection
