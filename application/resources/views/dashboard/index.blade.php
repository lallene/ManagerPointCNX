@extends('layouts.app')

@section('content')
<meta charset="UTF-8">

<link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
<link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

<style>
    @media (min-width: 1400px) { .container-fluid { max-width: 1600px; } }
    .card { border-radius: 15px; border: none; box-shadow: 0 4px 12px rgba(0,0,0,0.05); margin-bottom: 25px; }
    .filter-bar { background: #fff; padding: 20px; border-radius: 15px; margin-bottom: 25px; }
    
    /* Slider Alertes */
    .alert-slider-container { overflow: hidden; white-space: nowrap; background: #f8fafc; padding: 10px 0; border-radius: 12px; margin-bottom: 20px; }
    .alert-track { display: inline-flex; animation: scroll 40s linear infinite; }
    .alert-track:hover { animation-play-state: paused; }
    @keyframes scroll { 0% { transform: translateX(0); } 100% { transform: translateX(-50%); } }
    .alert-item { display: inline-block; min-width: 300px; margin-right: 15px; background: #dc3545; color: white; padding: 12px 20px; border-radius: 10px; box-shadow: 0 4px 6px rgba(220, 53, 69, 0.2); }

    /* Style Audit Table */
    #tableAudit { border-collapse: separate !important; border-spacing: 0 8px !important; }
    #tableAudit thead th { background-color: #f1f5f9 !important; color: #475569 !important; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.05em; padding: 15px; border: none; }
    #tableAudit tbody tr { box-shadow: 0 2px 4px rgba(0,0,0,0.02); transition: transform 0.2s; }
    #tableAudit tbody tr:hover { transform: scale(1.002); background-color: #f8fafc !important; }
    #tableAudit tbody td { background-color: #ffffff; padding: 12px 15px; vertical-align: middle; border-top: 1px solid #f1f5f9 !important; border-bottom: 1px solid #f1f5f9 !important; }
    #tableAudit tbody td:first-child { border-left: 1px solid #f1f5f9 !important; border-radius: 10px 0 0 10px; }
    #tableAudit tbody td:last-child { border-right: 1px solid #f1f5f9 !important; border-radius: 0 10px 10px 0; }

    .status-badge { padding: 6px 12px; font-size: 0.7rem; font-weight: 700; border-radius: 6px; }
    .badge-retard { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-conforme { background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    
    .btn-export-img { opacity: 0.6; transition: 0.3s; cursor: pointer; }
    .btn-export-img:hover { opacity: 1; color: #0d6efd; }
</style>

<div class="container-fluid py-4" style="margin-top: 90px;">
    
    <div class="row mb-4 animate__animated animate__fadeIn">
        <div class="col-12 d-flex align-items-center justify-content-between">
            <div>
                <h1 class="fw-bold text-dark mb-0" style="letter-spacing: -1px;">
                    <i class="fas fa-rocket text-primary me-3"></i>Command Center
                </h1>
                <p class="text-muted mb-0 ps-5">Pilotage & Performance RH | <span class="fw-bold text-primary">v2.0</span></p>
            </div>
            <div class="text-end d-flex gap-2">
                <button onclick="downloadSection('full-dashboard')" class="btn btn-sm btn-dark rounded-pill shadow-sm">
                    <i class="fas fa-camera me-1"></i> Capture Dashboard
                </button>
                <span class="badge bg-soft-primary text-primary px-3 py-2 rounded-pill">
                    <i class="fas fa-sync fa-spin me-2"></i>Live Data Sync
                </span>
            </div>
        </div>
    </div>

    <div id="full-dashboard">
        {{-- FILTRES --}}
        <div class="filter-bar shadow-sm">
            <form action="{{ route('home') }}" method="GET" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">SITE</label>
                    <select name="site_id" class="form-select form-select-sm">
                        <option value="">Tous les sites</option>
                        @foreach($sites as $s)
                            <option value="{{ $s->id }}" {{ request('site_id') == $s->id ? 'selected' : '' }}>{{ $s->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">PROJET</label>
                    <select name="projet_id" class="form-select form-select-sm">
                        <option value="">Tous les projets</option>
                        @foreach($projets as $p)
                            <option value="{{ $p->id }}" {{ request('projet_id') == $p->id ? 'selected' : '' }}>{{ $p->nom }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">DÉBUT</label>
                    <input type="date" name="debut" value="{{ $debut }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold text-muted">FIN</label>
                    <input type="date" name="fin" value="{{ $fin }}" class="form-control form-control-sm">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100"><i class="fas fa-sync-alt me-2"></i>Actualiser</button>
                </div>
            </form>
        </div>

        {{-- IMPACT TEMPOREL (IMAGE) --}}
        <div class="card border-0 shadow-sm" id="kpi-section">
            <div class="card-body p-4">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="text-muted fw-bold small text-uppercase mb-0">Impact Temporel & Productivité</h6>
                    <i class="fas fa-download btn-export-img" onclick="downloadSection('kpi-section')"></i>
                </div>
                <div class="row align-items-center">
                    <div class="col-md-5 text-center border-end">
                        <span class="text-muted small d-block">Total Planifié</span>
                        <h2 class="fw-bold">{{ number_format($minutesPlanifieesGlobal / 60, 1) }}h</h2>
                    </div>
                    <div class="col-md-5 text-center">
                        <span class="text-muted small d-block text-danger">Perte (Retards)</span>
                        <h2 class="fw-bold text-danger">{{ number_format($minutesRetardGlobal / 60, 1) }}h</h2>
                    </div>
                    <div class="col-md-2 text-center">
                        @php $ratioPerte = $minutesPlanifieesGlobal > 0 ? ($minutesRetardGlobal / $minutesPlanifieesGlobal) * 100 : 0; @endphp
                        <div class="p-2 bg-light rounded-circle fw-bold text-{{ $ratioPerte > 5 ? 'danger' : 'success' }} shadow-sm" style="width: 70px; height: 70px; line-height: 55px; margin: auto;">
                            {{ round($ratioPerte, 1) }}%
                        </div>
                    </div>
                </div>
                <div class="progress mt-4" style="height: 12px; border-radius: 10px;">
                    <div class="progress-bar {{ $ratioPerte > 5 ? 'bg-danger' : 'bg-warning' }} progress-bar-striped" style="width: {{ $ratioPerte }}%"></div>
                </div>
            </div>
        </div>

        {{-- SLIDER ALERTES --}}
        <div class="alert-slider-container">
            <div class="alert-track">
                @foreach($projetsStats->where('taux_retard', '>', 10) as $stat)
                    <div class="alert-item"><strong>{{ $stat['nom'] }}</strong>: {{ round($stat['taux_retard'], 1) }}% de retards</div>
                @endforeach
                @foreach($projetsStats->where('taux_retard', '>', 10) as $stat)
                    <div class="alert-item"><strong>{{ $stat['nom'] }}</strong>: {{ round($stat['taux_retard'], 1) }}% de retards</div>
                @endforeach
            </div>
        </div>

        {{-- PERFORMANCE PAR PROJET (CSV + IMAGE) --}}
        <div class="card mb-4" id="performance-section">
            <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-uppercase small text-muted">Performance par Projet</h6>
                <i class="fas fa-image btn-export-img" onclick="downloadSection('performance-section')" title="Télécharger Image"></i>
            </div>
            <div class="card-body">
                <table id="tableProjets" class="table table-hover w-100">
                    <thead>
                        <tr>
                            <th>Projet</th>
                            <th>Site</th>
                            <th>Couverture</th>
                            <th>Taux Retard</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($projetsStats as $stat)
                        <tr>
                            <td class="fw-bold">{{ $stat['nom'] }}</td>
                            <td><span class="badge bg-light text-dark border">{{ $stat['site'] }}</span></td>
                            <td>
                                <div class="progress" style="height: 6px; width: 80px;">
                                    <div class="progress-bar bg-success" style="width: {{ $stat['taux_planification'] }}%"></div>
                                </div>
                            </td>
                            <td><span class="badge {{ $stat['taux_retard'] > 10 ? 'bg-danger' : 'bg-success' }}">{{ round($stat['taux_retard'], 1) }}%</span></td>
                            <td class="text-center">@if($stat['taux_retard'] > 10) <i class="fas fa-chart-line text-danger"></i> @else <i class="fas fa-check-circle text-success"></i> @endif</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- GRAPHIQUE (IMAGE) --}}
        <div class="card mb-4 p-4" id="chart-section">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="text-muted small fw-bold text-uppercase mb-0">ÉCART PRÉVU VS RÉALISÉ</h6>
                <i class="fas fa-download btn-export-img" onclick="downloadSection('chart-section')"></i>
            </div>
            <div style="height: 300px;"><canvas id="prodChart"></canvas></div>
        </div>

        {{-- AUDIT TRAIL (CSV + IMAGE) --}}
        <div class="card border-0 shadow-sm" id="audit-section">
            <div class="card-header bg-white py-3 border-bottom d-flex justify-content-between align-items-center">
                <h6 class="fw-bold m-0 text-dark text-uppercase small">Journal de Pointage (Audit Trail)</h6>
                <i class="fas fa-image btn-export-img" onclick="downloadSection('audit-section')"></i>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="tableAudit" class="table w-100">
                        <thead>
                            <tr>
                                <th class="ps-4">Agent / Projet</th>
                                <th>Date</th>
                                <th>Prévu</th>
                                <th>Réalisé</th>
                                <th class="text-center">Écart</th>
                                <th class="text-center">Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($pointages as $p)
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $p->agent->user->name ?? 'N/A' }}</div>
                                    <div class="text-muted small">{{ $p->agent->projet->designation ?? 'N/A' }}</div>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($p->date_pointage)->format('d/m/Y') }}</td>
                                <td class="text-muted">{{ $p->planning ? \Carbon\Carbon::parse($p->planning->entree)->format('H:i') : '--:--' }}</td>
                                <td class="fw-bold {{ $p->is_late ? 'text-danger' : '' }}">{{ \Carbon\Carbon::parse($p->entree)->format('H:i') }}</td>
                                <td class="text-center text-danger fw-bold">{{ $p->is_late ? '+'.$p->ecart_retard : '--' }}</td>
                                <td class="text-center">
                                    <span class="status-badge {{ $p->is_late ? 'badge-retard' : 'badge-conforme' }}">{{ $p->is_late ? 'Retard' : 'OK' }}</span>
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

{{-- SCRIPTS --}}
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
$(document).ready(function() {
    const commonConfig = {
        dom: 'Bfrtip',
        buttons: [{
            extend: 'csvHtml5',
            text: '<i class="fas fa-file-csv me-1"></i> Télécharger CSV',
            className: 'btn btn-success btn-sm mb-3 rounded-pill'
        }],
        language: { url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json" }
    };

    // Application aux deux tableaux
    $('#tableProjets').DataTable(Object.assign({}, commonConfig, { pageLength: 5 }));
    $('#tableAudit').DataTable(Object.assign({}, commonConfig, { order: [[1, 'desc']] }));

    // Chart
    const ctx = document.getElementById('prodChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {!! json_encode($labelsGraph) !!},
            datasets: [
                { label: 'Prévu', data: {!! json_encode($dataPrevu) !!}, borderColor: '#cbd5e1', borderDash: [5,5], fill: false },
                { label: 'Réalisé', data: {!! json_encode($dataRealise) !!}, borderColor: '#0d6efd', backgroundColor: 'rgba(13,110,253,0.1)', fill: true }
            ]
        },
        options: { responsive: true, maintainAspectRatio: false }
    });
});

function downloadSection(id) {
    $('.dt-buttons, .btn-export-img, .btn-dark').css('visibility', 'hidden');
    html2canvas(document.getElementById(id), { scale: 2, backgroundColor: "#ffffff" }).then(canvas => {
        const link = document.createElement('a');
        link.download = id + '.png';
        link.href = canvas.toDataURL("image/png");
        link.click();
        $('.dt-buttons, .btn-export-img, .btn-dark').css('visibility', 'visible');
    });
}
</script>
@endsection