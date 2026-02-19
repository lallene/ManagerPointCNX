@extends('layouts.app')

@section('content')
    <meta charset="UTF-8">

    {{-- DataTables & Chart.js CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" />

    <style>
        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 1600px;
            }
        }

        .card {
            border-radius: 15px;
            border: none;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            margin-bottom: 25px;
            transition: transform 0.2s;
        }

        .filter-bar {
            background: #fff;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
        }

        /* New KPI Styles */
        .kpi-title {
            font-size: 0.75rem;
            font-weight: 800;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .kpi-value {
            font-size: 1.8rem;
            font-weight: 800;
            color: #1e293b;
        }

        .bg-soft-danger {
            background-color: #fee2e2;
            color: #b91c1c;
        }

        .badge-soft-info {
            background-color: #e0f2fe;
            color: #0369a1;
            border: 1px solid #bae6fd;
            padding: 4px 8px;
            border-radius: 6px;
            font-size: 0.65rem;
        }

        /* Slider Alertes */
        .alert-slider-container {
            overflow: hidden;
            white-space: nowrap;
            background: #f8fafc;
            padding: 10px 0;
            border-radius: 12px;
            margin-bottom: 20px;
        }

        .alert-track {
            display: inline-flex;
            animation: scroll 40s linear infinite;
        }

        @keyframes scroll {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-50%);
            }
        }

        .alert-item {
            display: inline-block;
            min-width: 300px;
            margin-right: 15px;
            background: #dc3545;
            color: white;
            padding: 12px 20px;
            border-radius: 10px;
        }

        /* DataTables Custom */
        #tableAudit thead th {
            background-color: #f8fafc;
            color: #475569;
            font-size: 0.75rem;
            padding: 15px;
            border: none;
        }

        .status-badge {
            padding: 6px 12px;
            font-size: 0.7rem;
            font-weight: 700;
            border-radius: 6px;
        }

        .badge-retard {
            background-color: #fee2e2;
            color: #dc2626;
        }

        .badge-conforme {
            background-color: #dcfce7;
            color: #16a34a;
        }

        .btn-export-img {
            opacity: 0.5;
            transition: 0.3s;
            cursor: pointer;
        }

        .btn-export-img:hover {
            opacity: 1;
            color: #0d6efd;
        }
    </style>

    <div class="container-fluid py-4" style="margin-top: 90px;">

        <div class="row mb-4 animate__animated animate__fadeIn">
            <div class="col-12 d-flex align-items-center justify-content-between">
                <div>
                    <h1 class="fw-bold text-dark mb-0" style="letter-spacing: -1.5px;">
                        <i class="fas fa-chart-line text-primary me-3"></i>Intelligence Center
                    </h1>
                    <p class="text-muted mb-0 ps-5">Analytics & Adhérence RH | <span class="fw-bold text-primary">v2.5
                            BI-Ready</span></p>
                </div>
                <div class="text-end">
                    <button onclick="downloadSection('full-dashboard')" class="btn btn-dark rounded-pill shadow-sm px-4">
                        <i class="fas fa-camera me-2"></i>Snapshot Report
                    </button>
                </div>
            </div>
        </div>

        <div id="full-dashboard">
            {{-- 1. BARRE DE FILTRES --}}
            <div class="filter-bar shadow-sm">
                <form action="{{ route('home') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">SITE</label>
                        <select name="site_id" id="site_select" class="form-select form-select-sm">
                            <option value="">Tous les sites</option>
                            @foreach ($sites as $s)
                                <option value="{{ $s->id }}" {{ $site_id == $s->id ? 'selected' : '' }}>
                                    {{ $s->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">PROJET</label>
                        <select name="projet_id" id="projet_select" class="form-select form-select-sm">
                            <option value="" data-site="">Tous les projets</option>
                            @foreach ($projets_tous ?? \App\Models\Projet::all() as $p)
                                <option value="{{ $p->id }}" data-site="{{ $p->site_id }}"
                                    {{ $projet_id == $p->id ? 'selected' : '' }} class="projet-option">
                                    {{ $p->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">DÉBUT</label>
                        <input type="date" name="debut" value="{{ $debut }}"
                            class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small fw-bold text-muted">FIN</label>
                        <input type="date" name="fin" value="{{ $fin }}"
                            class="form-control form-control-sm shadow-none">
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm w-100 fw-bold"><i
                                class="fas fa-sync-alt me-2"></i>ACTUALISER</button>
                    </div>
                </form>
            </div>

            {{-- 2. KPI ROW --}}
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card h-100 p-4 border-start border-primary border-4">
                        <span class="kpi-title">Adhérence Globale</span>
                        <div class="kpi-value {{ $tauxAdherenceGlobal < 90 ? 'text-warning' : 'text-success' }}">
                            {{ number_format($tauxAdherenceGlobal, 1) }}%
                        </div>
                        <div class="progress mt-2" style="height: 5px;">
                            <div class="progress-bar bg-success" style="width: {{ $tauxAdherenceGlobal }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 p-4 border-start border-danger border-4">
                        <span class="kpi-title">Total Retards</span>
                        <div class="kpi-value text-danger">{{ number_format($minutesRetardGlobal) }} <small
                                class="fs-6">min</small></div>
                        <span class="text-muted small">Impact productivité</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 p-4 border-start border-info border-4">
                        <span class="kpi-title">Heures Planifiées</span>
                        <div class="kpi-value">{{ number_format($minutesPlanifieesGlobal / 60, 1) }} <small
                                class="fs-6">h</small></div>
                        <span class="text-muted small">Volume cible</span>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100 p-4 border-start border-dark border-4">
                        <span class="kpi-title">Effectif Actif</span>
                        <div class="kpi-value">{{ $pointages->unique('agent_id')->count() }}</div>
                        <span class="text-muted small">Agents pointés</span>
                    </div>
                </div>
            </div>

            {{-- 3. CHARTS ROW --}}
            <div class="row mb-4">
                <div class="col-md-8">
                    <div class="card p-4 h-100 shadow-sm">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h6 class="fw-bold m-0 text-muted small text-uppercase">Concentration des Retards / Jour</h6>
                            <i class="fas fa-download btn-export-img" onclick="downloadSection('heatmap-box')"></i>
                        </div>
                        <div id="heatmap-box">
                            <canvas id="chartHeatmap" height="130"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card p-4 h-100 shadow-sm border-0 bg-light">
                        <h6 class="fw-bold text-danger small text-uppercase mb-4"><i class="fas fa-user-clock me-2"></i>Top
                            5 Alertes (Minutes)</h6>
                        <div class="list-group list-group-flush bg-transparent">
                            @foreach ($topRetardataires as $tr)
                                <div
                                    class="list-group-item bg-transparent d-flex justify-content-between align-items-center px-0 border-bottom-0">
                                    <div>
                                        <div class="fw-bold small">{{ $tr['nom'] }}</div>
                                        <small class="text-muted">{{ $tr['count'] }} occurrence(s)</small>
                                    </div>
                                    <span
                                        class="badge bg-soft-danger px-3 py-2 rounded-pill fw-bold">+{{ $tr['total_retard'] }}m</span>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>

            {{-- 4. PERFORMANCE PROJETS --}}
            <div class="card mb-4 shadow-sm" id="perf-projets">
                <div class="card-header bg-white py-3 border-0 d-flex justify-content-between">
                    <h6 class="fw-bold m-0 text-muted small text-uppercase">Performance par Projet</h6>
                </div>
                <div class="card-body">
                    <table id="tableProjets" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Projet</th>
                                <th>Site</th>
                                <th>Couverture</th>
                                <th>Taux Retard</th>
                                <th class="text-center">Alerte</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($projetsStats as $stat)
                                <tr>
                                    <td class="fw-bold">{{ $stat['nom'] }}</td>
                                    <td><span class="badge bg-light text-dark border">{{ $stat['site'] }}</span></td>
                                    <td>
                                        <div class="progress" style="height: 6px; width: 100px;">
                                            <div class="progress-bar bg-primary"
                                                style="width: {{ $stat['taux_planification'] }}%"></div>
                                        </div>
                                    </td>
                                    <td>
                                        <span
                                            class="fw-bold {{ $stat['taux_retard'] > 10 ? 'text-danger' : 'text-success' }}">
                                            {{ round($stat['taux_retard'], 1) }}%
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        @if ($stat['taux_retard'] > 10)
                                            <span class="badge bg-danger">CRITIQUE</span>
                                        @else
                                            <i class="fas fa-check-circle text-success"></i>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- 5. AUDIT TRAIL --}}
            <div class="card shadow-sm" id="audit-trail">
                <div class="card-header bg-white py-3 border-bottom">
                    <h6 class="fw-bold m-0 text-dark small text-uppercase">Journal de Pointage Détailé</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="tableAudit" class="table w-100">
                            <thead>
                                <tr>
                                    <th class="ps-4">Agent / Projets</th>
                                    <th>Date</th>
                                    <th>Prévu</th>
                                    <th>Réalisé</th>
                                    <th class="text-center">Retard</th>
                                    <th class="text-center">Statut</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($pointages as $p)
                                    <tr>
                                        <td class="ps-4">
                                            <div class="fw-bold">{{ $p->agent->user->name ?? 'N/A' }}</div>
                                            <div class="d-flex flex-wrap gap-1">
                                                @foreach ($p->agent->projets as $proj)
                                                    <span class="badge-soft-info">{{ $proj->designation }}</span>
                                                @endforeach
                                            </div>
                                        </td>
                                        <td>{{ \Carbon\Carbon::parse($p->date_pointage)->format('d/m/Y') }}</td>
                                        <td class="text-muted">
                                            {{ $p->planning ? \Carbon\Carbon::parse($p->planning->entree)->format('H:i') : '--:--' }}
                                        </td>
                                        <td class="fw-bold {{ $p->is_late ? 'text-danger' : '' }}">
                                            {{ $p->entree ? \Carbon\Carbon::parse($p->entree)->format('H:i') : '--:--' }}
                                        </td>
                                        <td class="text-center text-danger fw-bold">
                                            {{ $p->is_late ? '+' . $p->ecart_retard : '--' }}</td>
                                        <td class="text-center">
                                            <span
                                                class="status-badge {{ $p->is_late ? 'badge-retard' : 'badge-conforme' }}">
                                                {{ $p->is_late ? 'RETARD' : 'OK' }}
                                            </span>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Scripts --}}
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        $(document).ready(function() {
            const $siteSelect = $('#site_select');
            const $projetSelect = $('#projet_select');
            const $projetOptions = $('.projet-option');

            function filterProjets() {
                const selectedSite = $siteSelect.val();

                // On réinitialise l'affichage des options
                $projetOptions.each(function() {
                    const projectSite = $(this).data('site');

                    if (selectedSite === "" || projectSite == selectedSite) {
                        $(this).show();
                    } else {
                        $(this).hide();
                        // Si le projet actuellement sélectionné est caché, on remet à "Tous"
                        if ($(this).is(':selected')) {
                            $projetSelect.val("");
                        }
                    }
                });
            }

            // On lance le filtre au chargement (si un site est déjà sélectionné)
            filterProjets();

            // On lance le filtre au changement de site
            $siteSelect.on('change', filterProjets);
        });

        $(document).ready(function() {
            // Configuration DataTables
            const commonConfig = {
                language: {
                    search: "Filtrer :",
                    lengthMenu: "_MENU_ lignes",
                    info: "_TOTAL_ entrées"
                },
                pageLength: 25
            };

            $('#tableProjets').DataTable(commonConfig);
            $('#tableAudit').DataTable(Object.assign({}, commonConfig, {
                order: [
                    [1, 'desc']
                ]
            }));

            // Heatmap Chart.js
            const ctxHeat = document.getElementById('chartHeatmap').getContext('2d');
            new Chart(ctxHeat, {
                type: 'bar',
                data: {
                    labels: @json(array_keys($retardsParJour)),
                    datasets: [{
                        label: 'Nb Retards',
                        data: @json(array_values($retardsParJour)),
                        backgroundColor: '#3b82f6',
                        borderRadius: 8,
                        barThickness: 30
                    }]
                },
                options: {
                    responsive: true,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                drawBorder: false,
                                color: '#f1f5f9'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });
        });

        // Screenshot Engine
        function downloadSection(id) {
            html2canvas(document.getElementById(id), {
                scale: 2
            }).then(canvas => {
                const link = document.createElement('a');
                link.download = 'ManagerPoint-Report.png';
                link.href = canvas.toDataURL();
                link.click();
            });
        }
    </script>
@endsection
