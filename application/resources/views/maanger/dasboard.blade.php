@extends('layouts.app')

@section('content')
<div class="container-fluid">
    {{-- Header --}}
    <div class="row mb-3">
        <div class="col-12 d-flex justify-content-between align-items-center">
            <h2 class="fw-bold"><i class="bi bi-speedometer2 text-primary"></i> Dashboard Manager</h2>
            <div class="badge bg-dark p-2">Semaine Actuelle : {{ Carbon\Carbon::now()->isoWeek() }}</div>
        </div>
    </div>

    {{-- 1. Flash Stats Cards --}}
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-primary border-4">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">TOTAL AGENTS</h6>
                    <h3 class="fw-bold">{{ $totalAgents }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-{{ $tauxPresence > 90 ? 'success' : 'warning' }} border-4">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">PRÉSENTS (AUJOURD'HUI)</h6>
                    <h3 class="fw-bold text-{{ $tauxPresence > 90 ? 'success' : 'warning' }}">{{ $presentsCount }} ({{ number_format($tauxPresence, 0) }}%)</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-danger border-4">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">ABSENCES CRITIQUES</h6>
                    <h3 class="fw-bold text-danger">{{ $absencesUrgent }}</h3>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card shadow-sm border-0 border-start border-info border-4">
                <div class="card-body">
                    <h6 class="text-muted small fw-bold">H. SUPP (S{{ $selectedWeek }})</h6>
                    <h3 class="fw-bold text-info">{{ $totalOvertime }}h</h3>
                </div>
            </div>
        </div>
    </div>

    {{-- 2. Graphique & Live Feed --}}
    <div class="row mb-4">
        <div class="col-md-8">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-graph-up me-2"></i>Taux de présence (7j)</div>
                <div class="card-body"><canvas id="trendChart" height="200"></canvas></div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm border-0 h-100">
                <div class="card-header bg-white fw-bold"><i class="bi bi-broadcast text-danger me-2"></i>Live Feed</div>
                <div class="list-group list-group-flush">
                    @foreach($lastPointages as $lp)
                    <div class="list-group-item border-0">
                        <div class="d-flex justify-content-between">
                            <span class="fw-bold small">{{ $lp->prenom }} {{ $lp->nom }}</span>
                            <small class="text-muted">{{ \Carbon\Carbon::parse($lp->created_at)->diffForHumans() }}</small>
                        </div>
                        <span class="badge bg-{{ $lp->commentaires == 'debut' ? 'success' : 'info' }} mt-1">{{ strtoupper($lp->commentaires) }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    {{-- 3. Sélecteur de Semaine --}}
    <div class="text-center mb-4">
        @foreach($semaines as $s)
            <a href="?week={{ $s['numero'] }}" class="btn btn-sm {{ $selectedWeek == $s['numero'] ? 'btn-primary' : 'btn-outline-secondary' }} mx-1">
                S{{ $s['numero'] }}<br><small>{{ $s['debut'] }}</small>
            </a>
        @endforeach
    </div>

    {{-- 4. Tableau DataTables (Déjà existant dans votre code) --}}
    <div class="card shadow-sm border-0">
        <div class="card-body p-0">
            <table id="table-agents" class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr><th>ID</th><th>Nom</th><th>Fonction</th><th>Site</th><th>Projet</th><th>H.Travail</th></tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
$(document).ready(function() {
    // Graphique
    new Chart($('#trendChart'), {
        type: 'line',
        data: {
            labels: {!! json_encode($trendLabels) !!},
            datasets: [{
                label: 'Taux %',
                data: {!! json_encode($trendData) !!},
                borderColor: '#0d6efd',
                tension: 0.3,
                fill: true,
                backgroundColor: 'rgba(13, 110, 253, 0.05)'
            }]
        },
        options: { responsive: true, scales: { y: { min: 0, max: 100 } } }
    });

    // DataTable (URL vers ta route home.data)
    $('#table-agents').DataTable({
        ajax: '{{ route("home.data") }}?week={{ $selectedWeek }}',
        columns: [
            {data: 'workday_id'}, {data: 'nom_prenom'}, {data: 'fonction'},
            {data: 'site'}, {data: 'projet'}, {data: 'nombre_heures_travail'}
        ]
    });
});
</script>
@endpush