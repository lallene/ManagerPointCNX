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

        .week-selector-container {
    display: flex;
    overflow-x: auto;
    gap: 4px;
    padding-bottom: 10px;
    scrollbar-width: thin; /* Pour Firefox */
}

/* Personnalisation de la scrollbar pour Chrome/Safari */
.week-selector-container::-webkit-scrollbar {
    height: 6px;
}
.week-selector-container::-webkit-scrollbar-thumb {
    background: #007bff;
    border-radius: 10px;
}
.week-selector-container .btn-outline-primary {
    flex: 0 0 auto; /* Empêche les boutons de rétrécir */
    min-width: 60px;
}
    </style>

    <div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="page-title mb-0">🗓️ SUIVI DES POINTAGES</h2>
            <small class="text-muted">ManagerPoint / Consultation Hebdomadaire</small>
        </div>
        
        {{-- Zone d'Export Spécifique --}}
        <div class="export-zone d-flex align-items-end gap-2 shadow-sm">
            <div>
                <label class="form-label small fw-bold mb-1">EXPORT DU :</label>
                <input type="date" id="export_debut" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('monday this week')) }}">
            </div>
            <div>
                <label class="form-label small fw-bold mb-1">AU :</label>
                <input type="date" id="export_fin" class="form-control form-control-sm" value="{{ date('Y-m-d', strtotime('sunday this week')) }}">
            </div>
            <button type="button" id="btn-export-excel" class="btn btn-success fw-bold btn-sm">
                <i class="fas fa-file-excel me-1"></i> EXCEL
            </button>
        </div>
    </div>

    {{-- Filtres d'affichage (Semaines) --}}
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
                <div class="col-md-8">
                    <label class="form-label fw-bold small text-muted d-block text-center">AFFICHAGE SEMAINE</label>
                    <div class="week-selector-container shadow-sm p-2 bg-white rounded">
                        @foreach ($semaines as $sem)
                            <input type="radio" class="btn-check btn-week" name="week"
                                id="week-{{ $sem['numero'] }}" value="{{ $sem['numero'] }}"
                                {{ $selectedWeek == $sem['numero'] ? 'checked' : '' }}>
                            <label class="btn btn-outline-primary btn-sm" for="week-{{ $sem['numero'] }}">
                                <strong>S{{ $sem['numero'] }}</strong><br>
                                <small style="font-size: 0.6rem;">{{ $sem['debut'] }}</small>
                            </label>
                        @endforeach
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Recherche Rapide --}}
    <div class="row mb-3">
        <div class="col-md-4">
            <div class="input-group shadow-sm">
                <span class="input-group-text bg-white border-end-0"><i class="fas fa-search text-primary"></i></span>
                <input type="text" id="quick-search" class="form-control border-start-0 ps-0" placeholder="Rechercher un agent...">
            </div>
        </div>
    </div>

    <div id="loader" class="text-center py-5" style="display:none;">
        <div class="spinner-border text-primary" role="status"></div>
        <p class="mt-2 fw-bold">Chargement de la semaine...</p>
    </div>

    <div id="table-container"></div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {

        // Fonction pour centrer la semaine sélectionnée
        function centerActiveWeek() {
            const activeWeek = document.querySelector('input[name="week"]:checked');
            if (activeWeek) {
                activeWeek.nextElementSibling.scrollIntoView({ 
                    behavior: 'smooth', 
                    inline: 'center', 
                    block: 'nearest' 
                });
            }
        }

        // Initialisation
        centerActiveWeek();
        loadData();

        // Événements d'affichage
        $('.btn-week').on('change', function() {
            centerActiveWeek();
            
            // OPTIONNEL : Aligner les dates d'export sur la semaine choisie
            // Cela aide l'utilisateur s'il veut exporter précisément ce qu'il regarde
            /*
            let labelText = $(this).next('label').find('small').text(); // format "dd/mm"
            // Logique pour transformer dd/mm en Y-m-d si nécessaire
            */
            
            loadData();
        });

        $('#site_id, #projet_id').on('change', loadData);

        function loadData() {
            const $container = $('#table-container');
            $('#loader').show();
            $container.empty();

            const params = {
                site_id: $('#site_id').val(),
                projet_id: $('#projet_id').val(),
                week: $('input[name="week"]:checked').val()
            };

            $.get("{{ route('pointage.api.data') }}", params, function(data) {
                $('#loader').hide();
                renderTables(data);
            }).fail(function() {
                $('#loader').hide();
                $container.html('<div class="alert alert-danger text-center">Erreur de connexion aux données.</div>');
            });
        }

        function renderTables(data) {
            if (!data.resultat?.length) {
                $('#table-container').html('<div class="alert alert-info text-center">Aucune donnée pour cette semaine.</div>');
                return;
            }

            let html = '';
            data.resultat.forEach(proj => {
                let dateHeaders = data.dates.map(d => {
                    let label = new Date(d).toLocaleDateString('fr-FR', { weekday: 'short', day: '2-digit' });
                    return `<th colspan="3" class="border-start text-center">${label}</th>`;
                }).join('');

                let subHeaders = data.dates.map(() => `<th class="small border-start text-center">Prévu</th><th class="small text-center">IN</th><th class="small text-center">OUT</th>`).join('');

                let rows = proj.superviseurs.map(agent => {
                    let cells = data.dates.map(date => {
                        let s = agent.stats[date] || {};
                        let p = s.p_in ? `<span class="badge-p">${s.p_in}-${s.p_out}</span>` : '-';
                        let i = s.a_in ? `<span class="badge-status badge-in">${s.a_in}</span>` : (s.p_in ? '<span class="badge-status badge-abs">ABS</span>' : '-');
                        let o = s.a_out ? `<span class="badge-status badge-out">${s.a_out}</span>` : '-';
                        return `<td class="border-start text-center">${p}</td><td class="text-center">${i}</td><td class="text-center">${o}</td>`;
                    }).join('');
                    return `<tr><td class="text-start"><b>${agent.nom}</b><br><small class="text-muted">${agent.fonction}</small></td>${cells}</tr>`;
                }).join('');

                html += `
                    <div class="card shadow-sm border-0 mb-4 overflow-hidden">
                        <div class="card-header bg-white py-3"><h6 class="mb-0 text-primary fw-bold"><i class="fas fa-project-diagram me-2"></i>PROJET : ${proj.projet}</h6></div>
                        <div class="table-responsive">
                            <table class="table table-hover table-planning mb-0">
                                <thead>
                                    <tr><th rowspan="2" class="align-middle">Agent</th>${dateHeaders}</tr>
                                    <tr>${subHeaders}</tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                        </div>
                    </div>`;
            });
            $('#table-container').html(html);
        }

        // --- LOGIQUE D'EXPORT ---
        $('#btn-export-excel').on('click', function() {
            const btn = $(this);
            const params = {
                site_id: $('#site_id').val(),
                projet_id: $('#projet_id').val(),
                date_debut: $('#export_debut').val(),
                date_fin: $('#export_fin').val()
            };

            if(!params.date_debut || !params.date_fin) {
                alert("Veuillez sélectionner les dates de début et de fin pour l'export.");
                return;
            }

            // Feedback visuel pendant le téléchargement
            btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-1"></i> Génération...');
            
            const url = `{{ route('pointage.export.excel') }}?${$.param(params)}`;
            window.location.href = url;

            setTimeout(() => {
                btn.prop('disabled', false).html('<i class="fas fa-file-excel me-1"></i> EXCEL');
            }, 2000);
        });

        // Recherche rapide (Debounce pour performance)
        let searchTimer;
        $('#quick-search').on('keyup', function() {
            clearTimeout(searchTimer);
            let val = $(this).val().toLowerCase();
            searchTimer = setTimeout(function() {
                $("#table-container tbody tr").filter(function() {
                    $(this).toggle($(this).text().toLowerCase().indexOf(val) > -1);
                });
            }, 200);
        });
    });
</script>
@endpush