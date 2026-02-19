@extends('layouts.app')

@section('content')
    <style>
        /* Styles conservés et optimisés */
        .page-title {
            color: #333 !important;
            font-weight: 800 !important;
        }

        .table-planning thead th {
            background-color: #d1e3ff !important;
            color: #004085 !important;
            font-weight: 700 !important;
            text-align: center;
            vertical-align: middle;
            font-size: 0.85rem;
            border: 1px solid #dee2e6 !important;
        }

        .table-planning tbody td:first-child {
            position: sticky !important;
            left: 0;
            z-index: 10;
            background-color: #ffffff !important;
            min-width: 220px;
            border-left: 6px solid #007bff !important;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .badge-in {
            background-color: #28a745;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.75rem;
        }

        .badge-out {
            background-color: #dc3545;
            color: white;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.75rem;
        }

        .badge-p {
            background-color: #6c757d;
            color: white;
            padding: 2px 4px;
            border-radius: 4px;
            font-size: 0.7rem;
            display: block;
        }

        .badge-abs {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 2px 4px;
            border-radius: 4px;
            font-weight: bold;
            font-size: 0.7rem;
        }

        .loader-container {
            display: none;
            padding: 50px;
            text-align: center;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="column_title">
            <h2>🗓️ SUIVI DES POINTAGES HEBDOMADAIRES</h2>
            <div class="breadcrumb-custom d-none d-md-block">
                <span>ManagerPoint</span> / <span>🗓️ POINTAGES</span>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form id="filter-form" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted">SITE</label>
                        <select id="site_id" class="form-select shadow-sm">
                            <option value="">Tous les sites</option>
                            @foreach ($sites as $siteId)
                                <option value="{{ $siteId }}">Site {{ $siteId }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted">PROJET</label>
                        <select id="projet_id" class="form-select shadow-sm">
                            <option value="">Tous les projets</option>
                            @foreach ($projetsList as $p)
                                <option value="{{ $p->id }}">{{ $p->designation }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small text-muted d-block text-center">SEMAINES</label>
                        <div class="btn-group w-100 shadow-sm">
                            @foreach ($semaines as $sem)
                                <input type="radio" class="btn-check btn-week" name="week"
                                    id="week-{{ $sem['numero'] }}" value="{{ $sem['numero'] }}"
                                    {{ $selectedWeek == $sem['numero'] ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary" for="week-{{ $sem['numero'] }}">
                                    <strong>S{{ $sem['numero'] }}</strong><br>
                                    <small style="font-size: 0.6rem;">{{ $sem['debut'] }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-md-2">
                        <button type="button" id="btn-refresh" class="btn btn-primary w-100 fw-bold py-2">
                            <i class="fas fa-sync-alt me-2"></i> ACTUALISER
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div class="row mb-3">
            <div class="col-md-4">
                <div class="input-group shadow-sm">
                    <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-primary"></i></span>
                    <input type="text" id="quick-search" class="form-control border-start-0 ps-0"
                        placeholder="Rechercher un agent...">
                </div>
            </div>
        </div>

        <div id="loader" class="loader-container">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 fw-bold">Synchronisation en cours...</p>
        </div>

        <div id="table-container"></div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            loadData(); // Premier chargement

            // Événements
            $('#btn-refresh').on('click', loadData);
            $('.btn-week, #site_id, #projet_id').on('change', loadData);

            // Recherche Rapide
            $('#quick-search').on('keyup', function() {
                let val = $(this).val().toLowerCase();
                $("#table-container table tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
                });
            });

            function loadData() {
                $('#loader').show();

                const params = {
                    site_id: $('#site_id').val(),
                    projet_id: $('#projet_id').val(),
                    week: $('input[name="week"]:checked').val()
                };

                // Utilise EXACTEMENT ce nom de route
                $.get("{{ route('pointage.api.data') }}", params, function(data) {
                    $('#loader').hide();
                    renderTables(data);
                }).fail(function(xhr) {
                    $('#loader').hide();
                    alert("Erreur " + xhr.status + " : Route introuvable ou crash serveur.");
                });
            }

            function renderTables(data) {
                const container = $('#table-container');
                if (!data.resultat || data.resultat.length === 0) {
                    container.html('<div class="alert alert-info text-center">Aucune donnée disponible.</div>');
                    return;
                }

                let html = '';
                data.resultat.forEach(proj => {
                    let dateHeaders = '';
                    let subHeaders = '';

                    data.dates.forEach(d => {
                        let dateObj = new Date(d);
                        let label = dateObj.toLocaleDateString('fr-FR', {
                            weekday: 'short',
                            day: '2-digit'
                        });
                        dateHeaders += `<th colspan="3" class="border-start">${label}</th>`;
                        subHeaders +=
                            `<th class="small border-start">Prévu</th><th class="small">IN</th><th class="small">OUT</th>`;
                    });

                    let rows = '';
                    proj.superviseurs.forEach(agent => {
                        let cells = '';
                        data.dates.forEach(date => {
                            let s = agent.stats[date] || {};
                            let p = s.p_in ?
                                `<span class="badge-p">${s.p_in}-${s.p_out}</span>` :
                                '<small>-</small>';
                            let i = s.a_in ? `<span class="badge-in">${s.a_in}</span>` : (s
                                .p_in ? '<span class="badge-abs">ABS</span>' : '-');
                            let o = s.a_out ? `<span class="badge-out">${s.a_out}</span>` :
                                '-';
                            cells +=
                                `<td class="border-start">${p}</td><td>${i}</td><td>${o}</td>`;
                        });
                        rows +=
                            `<tr><td class="text-start"><b>${agent.nom}</b><br><small>${agent.fonction}</small></td>${cells}</tr>`;
                    });

                    html += `
                        <div class="card shadow-sm mb-4 border-0">
                            <div class="card-header bg-white"><h5 class="mb-0 text-primary">Projet : ${proj.projet}</h5></div>
                            <div class="table-responsive">
                                <table class="table table-planning mb-0">
                                    <thead><tr><th rowspan="2" class="align-middle">Agent</th>${dateHeaders}</tr><tr>${subHeaders}</tr></thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>
                        </div>`;
                });
                container.html(html);
            }
        });
    </script>
@endpush
