@extends('layouts.app')

@section('content')
    <meta charset="UTF-8">
    <meta http-equiv="Content-Language" content="fr">

    <style>
        :root {
            --primary-blue: #0d47a1;
            --success-green: #198754;
            --bg-light: #f4f7f6;
        }

        body {
            background-color: var(--bg-light);
        }

        /* Container fluid élargi pour plus de confort */
        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 1600px;
            }
        }

        /* Header Titre */
        .column_title {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 6px solid var(--primary-blue);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        /* Cartes Stats & Import */
        .card-custom {
            border-radius: 12px;
            transition: transform 0.2s;
            border: none;
        }

        /* Tableau - Look Professionnel */
        #siteTable {
            border-radius: 12px;
            overflow: hidden;
            border: none;
        }

        #siteTable thead th {
            background-color: #1e293b;
            /* Bleu nuit pro */
            color: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 15px;
            border: none;
        }

        #siteTable tbody tr {
            transition: all 0.2s ease;
        }

        #siteTable tbody tr:hover {
            background-color: #f0f7ff !important;
            /* Bleu très léger au survol */
            transform: scale(1.001);
        }

        /* Style des groupes de sites (Plus visible) */
        .site-group-header {
            background: linear-gradient(90deg, #e2e8f0 0%, #f8fafc 100%) !important;
            font-weight: 800;
            color: #334155;
            border-bottom: 2px solid #cbd5e1 !important;
        }

        .site-group-header td {
            padding: 12px 15px !important;
            font-size: 0.9rem;
        }

        /* Badge MSA ID (Design monospacé type "Code") */
        .badge-msa {
            font-family: 'JetBrains Mono', 'Fira Code', monospace;
            font-size: 0.9rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #2563eb;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        /* Nom du Projet (Plus gros et plus sombre) */
        .projet-name {
            font-size: 1.05rem;
            color: #0f172a;
            font-weight: 700;
        }

        /* Stats numériques */
        .stat-number {
            font-size: 1.8rem;
            line-height: 1;
            background: linear-gradient(45deg, #0d47a1, #42a5f5);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
    </style>

    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="column_title rounded shadow-sm">
            <div>
                <h2 class="fw-bold text-primary mb-0"><i class="fas fa-project-diagram me-2"></i> Gestion des Projets</h2>
                <p class="text-muted mb-0 small">Administration du périmètre et des structures</p>
            </div>
            <div class="breadcrumb-custom d-none d-md-block">
                <span class="badge bg-light text-secondary border">ManagerPoint / Référentiel</span>
            </div>
        </div>

        {{-- Bloc Importation & Actions (Comme sur Planning) --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    {{-- Zone Importation --}}
                    <div class="col-md-7 border-end">
                        <form action="{{ route('projet.import') }}" method="POST" enctype="multipart/form-data">
                            @csrf
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label class="form-label small fw-bold mb-0 text-uppercase">
                                    <i class="fas fa-file-excel me-1 text-success"></i> Importer Référentiel Projets
                                </label>
                                <a href="{{ asset('templates/masque_import_projets.xlsx') }}"
                                    class="text-success small fw-bold text-decoration-none">
                                    <i class="fas fa-download"></i> Télécharger le masque
                                </a>
                            </div>
                            <div class="input-group input-group-sm">
                                <input type="file" name="file" class="form-control" required>
                                <button type="submit" class="btn btn-success px-4">
                                    <i class="fas fa-upload me-1"></i> Charger
                                </button>
                            </div>
                        </form>
                    </div>

                    {{-- Zone Info --}}
                    <div class="col-md-5 text-center">
                        <label class="form-label small fw-bold mb-2 text-uppercase text-secondary">Statistiques
                            rapides</label>
                        <div class="d-flex justify-content-center gap-4">
                            <div><span class="d-block h5 mb-0 fw-bold text-primary" id="totalProjets">--</span><small
                                    class="text-muted">Projets</small></div>
                            <div class="border-start ps-4">
                                <span class="d-block h5 mb-0 fw-bold text-success" id="totalSites">--</span><small
                                    class="text-muted">Sites</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Tableau des Projets --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="siteTable" class="table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Site (Groupe)</th> {{-- Colonne invisible servant au regroupement --}}
                                <th style="width: 150px;">MSA ID</th>
                                <th>Désignation du Projet</th>
                                <th>DLT Superviseur</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            const groupColumn = 0;
            const table = $('#siteTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('projet.ajax') }}",
                    error: function(xhr) {
                        console.error("Erreur Backend Projet:", xhr.responseText);
                        $('#siteTable_processing').hide();
                        // Notification plus discrète que l'alert
                        console.error("Erreur serveur lors du chargement des projets.");
                    }
                },
                columns: [{
                        data: 'site_nom',
                        name: 'sites.designation',
                        visible: false
                    },
                    {
                        data: 'msa_id',
                        name: 'projets.msa_id',
                        render: data => data ? `<span class="badge-msa">${data}</span>` :
                            '<span class="text-muted">-</span>'
                    },
                    {
                        data: 'projet_nom',
                        name: 'projets.designation',
                        render: data => `<span class="projet-name">${data}</span>`
                    },
                    {
                        data: 'dltsuperviseur',
                        name: 'projets.dltsuperviseur',
                        render: data => data || '<span class="text-muted small">Non défini</span>'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                order: [
                    [groupColumn, 'asc']
                ],
                dom: '<"row align-items-center mb-3"<"col-md-4"B><"col-md-4 text-center"l><"col-md-4"f>>rtip',
                buttons: [{
                    extend: 'excel',
                    className: 'btn btn-outline-success btn-sm border-2',
                    text: '<i class="far fa-file-excel me-2"></i>Exporter le Référentiel'
                }],
                pageLength: 50,
                language: {
                    url: "https://cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
                },

                drawCallback: function(settings) {
                    const api = this.api();
                    const rows = api.rows({
                        page: 'current'
                    }).nodes();
                    let last = null;
                    let subtotal = 0;

                    // Optimisation du regroupement
                    api.column(groupColumn, {
                        page: 'current'
                    }).data().each(function(group, i) {
                        if (last !== group) {
                            // Calcul du nombre de projets dans ce groupe spécifique pour le badge
                            const count = api.column(groupColumn).data().filter(v => v ===
                                group).length;

                            $(rows).eq(i).before(
                                `<tr class="site-group-header">
                                <td colspan="4" class="py-3">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <i class="fas fa-building me-2 text-primary"></i>
                                            SITE : <span class="fw-bolder">${group ? group.toUpperCase() : 'SANS SITE'}</span>
                                        </div>
                                        <span class="badge rounded-pill bg-primary shadow-sm">${count} Projets</span>
                                    </div>
                                </td>
                            </tr>`
                            );
                            last = group;
                        }
                    });

                    // Mise à jour des compteurs globaux (Header)
                    const totalRecords = api.page.info().recordsTotal;
                    $('#totalProjets').fadeOut(200, function() {
                        $(this).text(totalRecords).fadeIn(200);
                    });

                    const uniqueSites = [...new Set(api.column(groupColumn).data().toArray())].filter(
                        n => n);
                    $('#totalSites').fadeOut(200, function() {
                        $(this).text(uniqueSites.length).fadeIn(200);
                    });
                }
            });
        });
    </script>
@endpush
