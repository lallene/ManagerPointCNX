@extends('layouts.app')

@section('link')
    <style>
        /* Optimisation pour les grands écrans */
        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 98% !important;
            }
        }

        /* Style du Loader */
        #loader {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            z-index: 9999;
            background: rgba(255, 255, 255, 0.9);
            padding: 20px;
            border-radius: 15px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        /* Style des Tableaux */
        .table-planning thead th {
            background-color: #f8f9fa;
            color: #334155;
            font-weight: 700;
            text-align: center;
            vertical-align: middle;
            border-bottom: 2px solid #e2e8f0;
            padding: 12px;
            font-size: 0.8rem;
        }

        .table-planning tbody td:first-child {
            font-weight: 600;
            background-color: #ffffff;
            border-left: 5px solid var(--accent, #198754);
            min-width: 220px;
        }

        .badge-horaire {
            background-color: var(--accent, #198754) !important;
            color: white;
            font-weight: 600;
            padding: 5px 10px;
            border-radius: 6px;
            display: inline-block;
            font-size: 0.75rem;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .repos-text {
            color: #cbd5e1;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }

        .site-title {
            color: #1e293b;
            font-size: 1.3rem;
            font-weight: 800;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
        }

        .site-title i {
            color: var(--accent, #198754);
            margin-right: 12px;
        }

        .group-card {
            background: white;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
            margin-bottom: 2.5rem;
        }
    </style>
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="column_title">
                    <h2><i class="bi bi-calendar3 me-2"></i> Planning Managers</h2>
                    <div class="breadcrumb-custom d-none d-md-block">
                        <small class="text-muted">ManagerPoint / Vue Globale</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Filtres --}}
        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body p-3">
                <form id="filter-form" class="row g-3 align-items-end">
                    {{-- Filtre SITE --}}
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">SITE</label>
                        <select name="site_id" id="site_id" class="form-select">
                            @if ($sites->count() > 1)
                                <option value="">Tous mes sites</option>
                            @endif
                            @foreach ($sites as $site)
                                <option value="{{ $site }}" {{ $selectedSiteId == $site ? 'selected' : '' }}>
                                    {{ $site }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Filtre PROJET --}}
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">PROJET</label>
                        <select name="projet_id" id="projet_id" class="form-select">
                            @if ($projetsList->count() > 1)
                                <option value="">Tous mes projets</option>
                            @endif
                            @foreach ($projetsList as $projet)
                                <option value="{{ $projet->id }}"
                                    {{ $selectedProjetId == $projet->id ? 'selected' : '' }}>
                                    {{ $projet->designation }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted">RECHERCHER UN MANAGER</label>
                        <div class="input-group">
                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                            <input type="text" id="search-manager" class="form-select border-start-0"
                                placeholder="Nom ou prénom...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-bold small text-muted d-block text-center">SEMAINE</label>
                        <div class="btn-group w-100">
                            @foreach ($semaines as $sem)
                                <input type="radio" class="btn-check filter-trigger" name="week"
                                    id="week-{{ $sem['numero'] }}" value="{{ $sem['valeur'] }}"
                                    {{ $selectedWeek == $sem['valeur'] ? 'checked' : '' }}>
                                <label class="btn btn-outline-primary py-2" for="week-{{ $sem['numero'] }}">
                                    <span class="fw-bold">S{{ $sem['numero'] }}</span><br>
                                    <small style="font-size: 0.65rem;">{{ $sem['debut'] }} - {{ $sem['fin'] }}</small>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div id="loader">
            <div class="spinner-border text-primary" role="status"></div>
            <span class="ms-2 fw-bold">Chargement...</span>
        </div>

        <div id="planning-container">
            {{-- Le contenu sera injecté par AJAX --}}
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(document).ready(function() {
            // Chargement initial
            loadPlanning();

            // Événements de changement sur les filtres
            $('.filter-trigger, #site_id, #projet_id').on('change', function() {
                loadPlanning();
            });

            function loadPlanning() {
                $('#loader').show();
                $('#planning-container').animate({
                    opacity: 0.4
                }, 200);

                let formData = {
                    week: $('input[name="week"]:checked').val(),
                    site_id: $('#site_id').val(),
                    projet_id: $('#projet_id').val()
                };

                console.log("Données envoyées :", formData);

                $.ajax({
                    url: "{{ route('getPlanningData') }}",
                    type: "GET",
                    data: formData,
                    dataType: "json",
                    success: function(data) {
                        renderTable(data);
                    },
                    error: function(xhr) {
                        console.error(xhr.responseText);
                        $('#planning-container').html(
                            '<div class="alert alert-danger">Erreur lors du chargement des données. Vérifiez la console.</div>'
                        );
                    },
                    complete: function() {
                        $('#loader').hide();
                        $('#planning-container').animate({
                            opacity: 1
                        }, 200);
                    }
                });
            }

            function renderTable(data) {
                var container = $('#planning-container');
                var html = '';

                if (!data.resultat || data.resultat.length === 0) {
                    container.html(
                        '<div class="card shadow-sm border-0"><div class="card-body text-center py-5"><i class="bi bi-calendar-x fs-1 text-muted"></i><p class="mt-3">Aucun planning trouvé pour cette sélection.</p></div></div>'
                    );
                    return;
                }

                data.resultat.forEach(function(siteData) {
                    html += '<div class="mb-5">';
                    html += '<h4 class="site-title"><i class="bi bi-geo-alt-fill"></i> Site : ' + siteData
                        .site + ' <small class="text-muted">(' + siteData.projet + ')</small></h4>';

                    siteData.groupes.forEach(function(groupe) {
                        html += '<div class="group-card shadow-sm">';
                        html +=
                            '<div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">';
                        html +=
                            '<span class="fw-bold text-secondary"><i class="bi bi-person-badge me-1"></i> Responsable : <span class="text-primary">' +
                            (groupe.manager || 'Direction') + '</span></span>';
                        html +=
                            '<span class="badge rounded-pill bg-light text-primary border border-primary">' +
                            groupe.agents.length + ' Manager(s)</span>';
                        html += '</div>';

                        html += '<div class="table-responsive">';
                        html += '<table class="table table-planning align-middle table-hover">';
                        html += '<thead><tr><th>Agent / Fonction</th>';

                        data.dates.forEach(function(date) {
                            var d = new Date(date);
                            var day = d.toLocaleDateString('fr-FR', {
                                weekday: 'short'
                            });
                            var num = d.toLocaleDateString('fr-FR', {
                                day: '2-digit',
                                month: '2-digit'
                            });
                            html += '<th class="text-capitalize">' + day +
                                '<br><small class="text-muted">' + num + '</small></th>';
                        });

                        html += '</tr></thead><tbody>';

                        groupe.agents.forEach(function(agent) {
                            html += '<tr>';
                            html += '<td><div class="text-dark fw-bold">' + agent.nom +
                                ' ' + agent.prenom + '</div>';
                            html +=
                                '<div class="text-muted text-uppercase" style="font-size:0.65rem;">' +
                                (agent.fonction || 'MANAGER') + '</div></td>';

                            data.dates.forEach(function(date) {
                                var p = agent.planning[date];
                                if (p && p.in && p.out) {
                                    html +=
                                        '<td class="text-center"><span class="badge-horaire">' +
                                        p.in + ' - ' + p.out + '</span></td>';
                                } else {
                                    html +=
                                        '<td class="text-center"><span class="repos-text">Repos</span></td>';
                                }
                            });
                            html += '</tr>';
                        });

                        html += '</tbody></table></div></div>';
                    });
                    html += '</div>';
                });

                container.html(html);
            }
            // Filtre de recherche dynamique
            // Filtre de recherche dynamique (Lead Dev Version)
            $(document).on('keyup', '#search-manager', function() {
                var value = $(this).val().toLowerCase();

                // 1. On filtre les lignes d'agents (Nom / Prénom / Fonction)
                $("#planning-container tbody tr").each(function() {
                    var textToSearch = $(this).find("td:first").text().toLowerCase();
                    $(this).toggle(textToSearch.indexOf(value) > -1);
                });

                // 2. On filtre les cartes de groupes (Managers)
                $('.group-card').each(function() {
                    var hasVisibleRows = $(this).find('tbody tr:visible').length > 0;
                    $(this).toggle(hasVisibleRows);
                });

                // 3. On filtre les sections de SITES (pour éviter les titres vides)
                $('#planning-container > div.mb-5').each(function() {
                    // On vérifie s'il reste au moins une carte de groupe visible dans ce site
                    var hasVisibleGroups = $(this).find('.group-card:visible').length > 0;

                    if (hasVisibleGroups || value === "") {
                        $(this).show(); // On utilise show/hide pour la performance
                    } else {
                        $(this).hide();
                    }
                });
            });
        });
    </script>
@endpush
