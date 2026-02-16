@extends('layouts.app')

@section('content')
    <style>
        .page-title {
            color: #333 !important;
            font-weight: 800 !important;
        }

        /* En-tête du tableau */
        .table-planning thead th {
            background-color: #d1e3ff !important;
            color: #004085 !important;
            font-weight: 700 !important;
            text-align: center;
            vertical-align: middle;
            font-size: 0.85rem;
            border: 1px solid #dee2e6 !important;
        }

        /* Colonne Agent Fixe (Sticky) */
        .table-planning tbody td:first-child {
            position: sticky !important;
            left: 0;
            z-index: 10;
            background-color: #ffffff !important;
            min-width: 220px;
            border-left: 6px solid #007bff !important;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }

        .table-planning tbody td {
            background-color: #ffffff !important;
            text-align: center;
            border: 1px solid #dee2e6 !important;
            padding: 8px;
            font-size: 0.8rem;
            vertical-align: middle;
        }

        /* Badges de Pointage */
        .badge-in {
            background-color: #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-out {
            background-color: #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
        }

        .badge-p {
            background-color: #6c757d;
            color: white;
            padding: 3px 6px;
            border-radius: 4px;
            font-size: 0.75rem;
            display: block;
            margin-bottom: 2px;
        }

        .badge-abs {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            padding: 4px;
            border-radius: 4px;
            font-weight: bold;
        }

        .bg-soft-primary {
            background-color: #e7f1ff;
            color: #0d6efd;
            padding: 2px 5px;
            border-radius: 3px;
        }

        .loader-container {
            display: none;
            padding: 50px;
            text-align: center;
        }
    </style>

    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>🗓️ SUIVI DES POINTAGES HEBDOMADAIRES</h2>
                    <div class="breadcrumb-custom d-none d-md-block">
                        <span>ManagerPoint</span> / <span>🗓️ POINTAGES</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form id="filter-form" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted">SITE</label>
                        <select name="site_id" id="site_id" class="form-select shadow-sm">
                            <option value="">Tous les sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site }}">{{ $site }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted">PROJET</label>
                        <select name="projet_id" id="projet_id" class="form-select shadow-sm">
                            <option value="">Tous les projets</option>
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
                    <span class="input-group-text bg-white border-end-0">
                        <i class="fas fa-search text-primary"></i>
                    </span>
                    <input type="text" id="quick-search" class="form-control border-start-0 ps-0"
                        placeholder="Filtrer un agent ou une fonction...">
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
            // Chargement initial
            loadData();

            // 1. Recherche rapide (temps réel)
            $('#quick-search').on('input', function() {
                const value = $(this).val().toLowerCase();
                $("#table-container table tbody tr").each(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
                });
                $('.card').each(function() {
                    $(this).toggle($(this).find('tbody tr:visible').length > 0);
                });
            });

            // 2. Dépendance Site -> Projet
            $('#site_id').on('change', function() {
                const siteId = $(this).val();
                const $projetSelect = $('#projet_id');
                if (!siteId) {
                    $projetSelect.html('<option value="">Tous les projets</option>');
                    return;
                }
                $projetSelect.html('<option value="">Chargement...</option>');
                $.get("{{ route('api.projets.by.site') }}", {
                    site_id: siteId
                }, function(data) {
                    let options = '<option value="">Tous les projets</option>';
                    data.forEach(p => options +=
                        `<option value="${p.id}">${p.designation}</option>`);
                    $projetSelect.html(options);
                });
            });

            // 3. Événements de rafraîchissement
            $('#btn-refresh').on('click', loadData);
            $('.btn-week, #site_id, #projet_id').on('change', loadData);

            // 4. Fonction de chargement AJAX
            function loadData() {
                $('#loader').show();
                $('#table-container').css('opacity', '0.5');

                const params = {
                    site_id: $('#site_id').val(),
                    projet_id: $('#projet_id').val(),
                    week: $('input[name="week"]:checked').val()
                };

                $.get("{{ route('pointages.global') }}", params, function(data) {
                    $('#loader').hide();
                    $('#table-container').css('opacity', '1');
                    renderTables(data);
                    // Réapplique le filtre si nécessaire
                    $('#quick-search').trigger('input');
                }).fail(function() {
                    $('#loader').hide();
                    $('#table-container').html(
                        '<div class="alert alert-danger text-center">Erreur lors de la récupération des données.</div>'
                    );
                });
            }

            // 5. Moteur de rendu des tableaux (Correction SyntaxError)
            function renderTables(data) {
                const container = $('#table-container');
                if (!data.resultat || data.resultat.length === 0) {
                    container.html(
                        '<div class="alert alert-warning text-center">Aucune donnée trouvée pour cette période.</div>'
                    );
                    return;
                }

                let finalHtml = '';
                data.resultat.forEach(proj => {
                    // Construction headers dates
                    let dateHeaders = '';
                    let subHeaders = '';
                    data.dates.forEach(d => {
                        const label = new Date(d).toLocaleDateString('fr-FR', {
                            weekday: 'short',
                            day: '2-digit'
                        });
                        dateHeaders +=
                            `<th colspan="3" class="text-center border-start table-secondary">${label}</th>`;
                        subHeaders += `
                            <th class="text-center small border-start bg-light text-muted">Prévu</th>
                            <th class="text-center small bg-light text-success">IN</th>
                            <th class="text-center small bg-light text-danger">OUT</th>`;
                    });

                    let rows = '';
                    proj.superviseurs.forEach(agent => {
                        let cells = '';
                        data.dates.forEach(date => {
                            const s = (agent.stats && agent.stats[date]) ? agent.stats[
                                date] : null;
                            const p = (s && s.p_in) ?
                                `<span class="badge-p">${s.p_in}-${s.p_out}</span>` :
                                '<span class="text-muted small">Repos</span>';
                            const i = (s && s.a_in) ?
                                `<span class="badge-in">${s.a_in}</span>` : (s && s.p_in ?
                                    '<span class="badge-abs">ABS</span>' : '-');
                            const o = (s && s.a_out) ?
                                `<span class="badge-out">${s.a_out}</span>` : '-';
                            cells +=
                                `<td class="text-center border-start">${p}</td><td class="text-center">${i}</td><td class="text-center">${o}</td>`;
                        });

                        rows += `
                            <tr>
                                <td class="fw-bold">
                                    <div class="text-uppercase small">${agent.nom}</div>
                                    <span class="bg-soft-primary small" style="font-size:0.65rem">${agent.fonction || 'MANAGER'}</span>
                                </td>
                                ${cells}
                            </tr>`;
                    });

                    finalHtml += `
                        <div class="card shadow-sm border-0 mb-5">
                            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                                <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-folder me-2"></i>${proj.projet}</h5>
                                <span class="badge bg-primary rounded-pill">${proj.superviseurs.length} Collaborateurs</span>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-planning mb-0">
                                    <thead>
                                        <tr><th rowspan="2" class="align-middle">Agent / Fonction</th>${dateHeaders}</tr>
                                        <tr>${subHeaders}</tr>
                                    </thead>
                                    <tbody>${rows}</tbody>
                                </table>
                            </div>
                        </div>`;
                });

                container.html(finalHtml);
            }
        });
    </script>
@endpush
