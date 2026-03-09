@extends('layouts.app')

@section('content')
    <meta charset="UTF-8">
    <meta http-equiv="Content-Language" content="fr">

    <style>
        @media (min-width: 1400px) { .container-fluid { max-width: 1600px; } }

        /* Structure du Tableau */
        #agentTable, #previewTable {
            border-collapse: separate !important;
            border-spacing: 0 2px;
        }

        #agentTable thead th, #previewTable thead th {
            background-color: #0d47a1;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            padding: 12px 8px;
            border: none;
        }

        #agentTable tbody td, #previewTable tbody td {
            background-color: #ffffff;
            vertical-align: middle;
            padding: 8px;
            border: 1px solid #dee2e6;
        }

        .project-group-header {
            background-color: #f8f9fa !important;
            font-weight: 800;
            color: #333;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-left: 5px solid #0d6efd !important;
        }

        .heure-input {
            width: 82px;
            border: 1px solid #ced4da;
            border-radius: 4px;
            text-align: center;
            font-size: 0.85rem;
            padding: 2px;
        }

        .heure-input[readonly] {
            background-color: #f1f3f5 !important;
            color: #adb5bd;
            cursor: not-allowed;
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

        .filter-card, .paste-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }

        /* Zone de texte spéciale copier-coller */
        #pastedData {
            font-family: 'Courier New', Courier, monospace;
            font-size: 0.8rem;
            background-color: #fcfcfc;
            border: 2px dashed #dee2e6;
            transition: all 0.3s;
        }

        #pastedData:focus {
            border-color: #0d6efd;
            background-color: #fff;
            box-shadow: none;
        }
    </style>

    <div class="container-fluid py-4">

        {{-- Alertes --}}
        @if (session('success'))
            <div class="alert alert-success alert-dismissible fade show border-0 shadow-sm mb-4" role="alert">
                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
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
                        <span class="badge bg-primary fs-6 px-4 py-2 rounded-pill shadow-sm">Semaine {{ $selectedWeekNum }}</span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Interface Copier-Coller Rapide --}}
        <div class="card paste-card mb-4">
            <div class="card-header bg-white py-3 border-bottom">
                <h5 class="mb-0 text-dark fw-bold small"><i class="fas fa-paste me-2 text-primary"></i>MODE IMPORTATION RAPIDE (EXCEL)</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-5">
                        <label class="form-label small fw-bold text-muted text-uppercase">1. Collez vos données ici</label>
                        <p class="text-muted" style="font-size: 0.75rem;">Colonnes attendues : <strong>ID Agent | Date | Entrée | Sortie | Commentaire</strong></p>
                        <form action="{{ route('plannings.paste-import') }}" method="POST" id="pasteForm">
                            @csrf
                            <input type="hidden" name="week" value="{{ $selectedWeekNum }}">
                            <textarea id="pastedData" name="pasted_data" class="form-control" rows="6" placeholder="Copiez les lignes de votre Excel et collez-les ici..."></textarea>
                            
                            <div class="mt-3">
                                <button type="submit" class="btn btn-success w-100 shadow-sm" id="btnSubmitPaste" disabled>
                                    <i class="fas fa-file-import me-2"></i> Valider l'importation
                                </button>
                            </div>
                        </form>
                    </div>
                    <div class="col-md-7 border-start">
                        <label class="form-label small fw-bold text-muted text-uppercase">2. Aperçu avant validation</label>
                        <div id="previewContainer" class="table-responsive" style="max-height: 250px;">
                            <p class="text-center text-muted py-5 small">L'aperçu de vos données Excel s'affichera ici dès que vous aurez collé le contenu.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtres & Sélecteur de Semaines --}}
        <div class="card filter-card mb-3">
            <div class="card-body py-2 text-center">
                <form method="GET" action="{{ route('planification') }}" id="filterForm" class="d-inline-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="week" value="{{ $selectedWeekNum }}">
                    @foreach ($categoriesDispo as $cat)
                        @php $isChecked = in_array($cat, (array)$fonctionsChoisies); @endphp
                        <input type="checkbox" class="btn-check" name="fonctions[]" id="cat_{{ Str::slug($cat) }}" value="{{ $cat }}" {{ $isChecked ? 'checked' : '' }} onchange="this.form.submit()">
                        <label class="btn btn-sm {{ $isChecked ? 'btn-primary' : 'btn-outline-primary' }} rounded-pill px-3" for="cat_{{ Str::slug($cat) }}">{{ $cat }}</label>
                    @endforeach
                </form>
            </div>
        </div>

        <div class="d-flex flex-wrap justify-content-center mb-4">
            @foreach ($semaines as $sem)
                <form method="GET" action="{{ route('planification') }}">
                    @foreach ((array) $fonctionsChoisies as $f) <input type="hidden" name="fonctions[]" value="{{ $f }}"> @endforeach
                    <input type="hidden" name="week" value="{{ $sem['num'] }}">
                    <button type="submit" class="btn btn-week {{ $selectedWeekNum == $sem['num'] ? 'btn-primary shadow-sm' : 'btn-outline-secondary' }}">
                        <span class="fw-bold">S{{ $sem['num'] }}</span><br>
                        <small>{{ str_replace("Semaine {$sem['num']} (", '', rtrim($sem['label'], ')')) }}</small>
                    </button>
                </form>
            @endforeach
        </div>

        {{-- Tableau de Saisie Principal --}}
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
                                    <th>{{ $nom }} <span class="date-badge">{{ $dateHeader->format('d/m') }}</span></th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                            @php $currentProject = null; @endphp
                            @forelse ($agents as $agent)
                                @if ($currentProject !== $agent->nom_projet)
                                    @php $currentProject = $agent->nom_projet; @endphp
                                    <tr><td colspan="8" class="project-group-header py-2 ps-3"><i class="fas fa-folder-open me-2 text-primary"></i> PROJET : {{ $currentProject }}</td></tr>
                                @endif
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark" style="font-size: 0.9rem;">{{ $agent->prenom }} {{ $agent->nom }}</div>
                                        <div class="d-flex flex-wrap gap-1 mt-1">
                                            <span class="badge bg-light text-primary border" style="font-size: 0.6rem;">{{ $agent->fonction }}</span>
                                            <span class="badge bg-light text-muted border" style="font-size: 0.6rem;">ID: {{ $agent->workday_id }}</span>
                                        </div>
                                    </td>
                                    @foreach (range(0, 6) as $i)
                                        @php
                                            $currentDate = \Carbon\Carbon::now()->setISODate(2026, $selectedWeekNum)->startOfWeek()->addDays($i)->format('Y-m-d');
                                            $key = $agent->id . '-' . $currentDate;
                                            $plan = $plannings[$key] ?? null;
                                            $isLocked = \Carbon\Carbon::parse($currentDate)->isBefore(\Carbon\Carbon::today());
                                        @endphp
                                        <td>
                                            <div class="d-flex flex-column gap-1 align-items-center">
                                                <input type="time" name="plannings[{{ $agent->id }}][{{ $currentDate }}][entree]" class="heure-input" value="{{ $plan->entree ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>
                                                <input type="time" name="plannings[{{ $agent->id }}][{{ $currentDate }}][sortie]" class="heure-input" value="{{ $plan->sortie ?? '' }}" {{ $isLocked ? 'readonly' : '' }}>
                                            </div>
                                        </td>
                                    @endforeach
                                </tr>
                            @empty
                                <tr><td colspan="8" class="text-center py-5">Aucun agent à planifier.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="text-end mt-4 mb-5">
                <button type="button" id="exportExcel" class="btn btn-outline-success px-4 me-2"><i class="fas fa-file-excel me-1"></i> Excel</button>
                <button type="submit" class="btn btn-primary btn-lg px-5 shadow border-0" style="background: linear-gradient(45deg, #0d47a1, #007bff);">
                    <i class="fas fa-save me-2"></i> Enregistrer la Semaine {{ $selectedWeekNum }}
                </button>
            </div>
        </form>
    </div>

    {{-- SCRIPTS --}}
    <script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const pastedData = document.getElementById('pastedData');
            const previewContainer = document.getElementById('previewContainer');
            const btnSubmitPaste = document.getElementById('btnSubmitPaste');

            // --- Logique Copier-Coller ---
            pastedData.addEventListener('input', function() {
                const rawValue = this.value.trim();
                if (!rawValue) {
                    previewContainer.innerHTML = '<p class="text-center text-muted py-5 small">L\'aperçu s\'affichera ici...</p>';
                    btnSubmitPaste.disabled = true;
                    return;
                }

                const lines = rawValue.split("\n");
                let html = '<table class="table table-sm table-striped border" id="previewTable" style="font-size:0.75rem;">';
                html += '<thead><tr><th>ID</th><th>Date</th><th>In</th><th>Out</th><th>Note</th></tr></thead><tbody>';

                lines.forEach(line => {
                    const cols = line.split("\t"); // Séparateur Excel
                    if (cols.length >= 2) {
                        html += `<tr>
                            <td>${cols[0] || ''}</td>
                            <td>${cols[1] || ''}</td>
                            <td>${cols[2] || ''}</td>
                            <td>${cols[3] || ''}</td>
                            <td>${cols[4] || ''}</td>
                        </tr>`;
                    }
                });

                html += '</tbody></table>';
                previewContainer.innerHTML = html;
                btnSubmitPaste.disabled = false;
            });

            // --- Export Excel ---
            document.getElementById('exportExcel').onclick = () => {
                const wb = XLSX.utils.table_to_book(document.getElementById('agentTable'));
                XLSX.writeFile(wb, "Planning_S{{ $selectedWeekNum }}.xlsx");
            };
        });
    </script>
@endsection