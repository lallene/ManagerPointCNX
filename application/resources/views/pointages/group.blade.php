@extends('layouts.app')

@section('content')
    <style>
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
        }

        .table-planning tbody td:first-child {
            position: sticky !important;
            left: 0;
            z-index: 10;
            background-color: #ffffff !important;
            min-width: 200px;
            border-left: 6px solid #007bff !important;
        }

        .table-planning tbody td {
            background-color: #ffffff !important;
            text-align: center;
            border: 1px solid #dee2e6 !important;
            padding: 8px;
            font-size: 0.8rem;
        }

        .badge-p {
            background-color: #6c757d !important;
            color: white !important;
            padding: 4px;
            border-radius: 4px;
            display: block;
            font-size: 0.7rem;
        }

        .badge-in {
            background-color: #28a745 !important;
            color: white !important;
            padding: 4px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            min-width: 45px;
        }

        .badge-out {
            background-color: #dc3545 !important;
            color: white !important;
            padding: 4px;
            border-radius: 4px;
            font-weight: bold;
            display: inline-block;
            min-width: 45px;
        }

        .badge-abs {
            background-color: #f8d7da !important;
            color: #721c24 !important;
            border: 1px solid #f5c6cb !important;
            padding: 4px;
            border-radius: 4px;
        }

        .loader-container {
            display: none;
            padding: 40px;
            text-align: center;
        }
    </style>
    <div class="container-fluid py-4">
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>🗓️ SUIVI DES POINTAGES HEBDOMADAIRES</h2>
                    <div class="breadcrumb-custom d-none d-md-block">
                        <span>ManagerPoint</span> / <span>🗓️ SUIVI DES POINTAGES HEBDOMADAIRES</span>
                    </div>
                </div>
            </div>
        </div>
        <h2 class="text-center page-title mb-4"></h2>

        <div class="card shadow-sm border-0 mb-4">
            <div class="card-body">
                <form id="filter-form" class="row g-3 align-items-end">
                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted">SITE</label>
                        <select name="site_id" id="site_id" class="form-select shadow-sm border-primary">
                            <option value="">Tous les sites</option>
                            @foreach ($sites as $site)
                                <option value="{{ $site }}">{{ $site }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label fw-bold small text-muted">PROJET</label>
                        <select name="projet_id" id="projet_id" class="form-select shadow-sm border-primary">
                            <option value="">Tous les projets</option>
                        </select>
                    </div>

                    <div class="col-md-5">
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

                    <div class="col-md-3">
                        <button type="button" id="btn-refresh" class="btn btn-primary w-100 fw-bold py-2 shadow">
                            <i class="fas fa-sync-alt me-2"></i> ACTUALISER
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <div id="loader" class="loader-container">
            <div class="spinner-border text-primary" role="status"></div>
            <p class="mt-2 fw-bold">Synchronisation des données...</p>
        </div>

        <div id="table-container"></div>
    </div>
@endsection

@push('scripts')
    {{-- IMPORTANT : On ne réimporte PAS jQuery ou Bootstrap ici --}}
    <script>
        $(document).ready(function() {

            loadData();

            $('#site_id').on('change', function() {
                const site = $(this).val();
                const $projetSelect = $('#projet_id');
                $projetSelect.html('<option value="">Chargement...</option>');

                if (!site) {
                    $projetSelect.html('<option value="">Tous les projets</option>');
                    return;
                }

                $.ajax({
                    url: "{{ route('projets.par.site', ['site' => ':site']) }}".replace(':site',
                        site),
                    type: 'GET',
                    success: function(projets) {
                        let options = '<option value="">Tous les projets</option>';
                        projets.forEach(p => {
                            options +=
                                `<option value="${p.id}">${p.designation}</option>`;
                        });
                        $projetSelect.html(options);
                    }
                });
            });

            $('#btn-refresh').on('click', loadData);
            $('.btn-week, #site_id, #projet_id').on('change', loadData);

            function loadData() {
                $('#loader').show();
                $('#table-container').css('opacity', '0.5');

                const params = {
                    site_id: $('#site_id').val(),
                    projet_id: $('#projet_id').val(),
                    week: $('input[name="week"]:checked').val()
                };

                $.ajax({
                    url: "{{ route('pointages.global') }}",
                    type: "GET",
                    data: params,
                    success: function(data) {
                        $('#loader').hide();
                        $('#table-container').css('opacity', '1');
                        renderTables(data);
                    }
                });
            }

            function renderTables(data) {
                var container = $('#table-container');
                var html = '';

                if (!data.resultat || data.resultat.length === 0) {
                    container.html('<div class="alert alert-warning text-center">Aucun pointage trouvé.</div>');
                    return;
                }

                data.resultat.forEach(function(proj) {
                    html += '<div class="card shadow-sm border-0 mb-5">';
                    html += '  <div class="card-header bg-white py-3">';
                    html += '    <h5 class="mb-0 fw-bold text-primary"><i class="fas fa-folder me-2"></i>' +
                        proj.projet + '</h5>';
                    html += '  </div>';
                    html += '  <div class="table-responsive">';
                    html += '    <table class="table table-planning mb-0">';
                    html += '      <thead>';

                    // Première ligne d'en-tête (Dates)
                    html += '        <tr><th rowspan="2">Agent / Fonction</th>';
                    data.dates.forEach(function(d) {
                        var label = new Date(d).toLocaleDateString('fr-FR', {
                            weekday: 'short',
                            day: '2-digit'
                        });
                        html += '<th colspan="3" class="border-start">' + label + '</th>';
                    });
                    html += '        </tr>';

                    // Deuxième ligne d'en-tête (Labels)
                    html += '        <tr>';
                    data.dates.forEach(function() {
                        html += '<th class="small border-start bg-light text-muted">Prévu</th>';
                        html += '<th class="small bg-light">IN</th>';
                        html += '<th class="small bg-light">OUT</th>';
                    });
                    html += '        </tr></thead><tbody>';

                    // Lignes des agents
                    proj.superviseurs.forEach(function(agent) {
                        html += '<tr>';
                        html += '  <td>';
                        html += '    <div class="text-uppercase small fw-bold">' + agent.nom +
                            '</div>';
                        html += '    <div class="text-muted" style="font-size:0.7rem">' + agent
                            .prenom + '</div>';
                        html +=
                            '    <span class="badge bg-light text-primary border" style="font-size:0.6rem">' +
                            (agent.fonction || '') + '</span>';
                        html += '  </td>';

                        data.dates.forEach(function(date) {
                            var s = (agent.stats && agent.stats[date]) ? agent.stats[date] :
                                null;
                            var p = (s && s.p_in) ? '<span class="badge-p">' + s.p_in +
                                '-' + s.p_out + '</span>' :
                                '<span class="text-muted small">Repos</span>';
                            var i = (s && s.a_in) ? '<span class="badge-in">' + s.a_in +
                                '</span>' : (s && s.p_in ?
                                    '<span class="badge-abs">ABS</span>' : '-');
                            var o = (s && s.a_out) ? '<span class="badge-out">' + s.a_out +
                                '</span>' : '-';

                            html += '<td class="border-start">' + p + '</td>';
                            html += '<td>' + i + '</td>';
                            html += '<td>' + o + '</td>';
                        });
                        html += '</tr>';
                    });

                    html += '      </tbody></table></div></div>';
                });

                container.html(html);
            }
        });
    </script>
@endpush
