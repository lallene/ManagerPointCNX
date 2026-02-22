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

        @media (min-width: 1400px) {
            .container-fluid {
                max-width: 1600px;
            }
        }

        .column_title {
            background: white;
            padding: 1.5rem;
            border-radius: 12px;
            border-left: 6px solid var(--primary-blue);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.05);
            margin-bottom: 2rem;
        }

        .card-custom {
            border-radius: 12px;
            transition: transform 0.2s;
            border: none;
        }

        #siteTable {
            border-radius: 12px;
            overflow: hidden;
            border: none;
        }

        #siteTable thead th {
            background-color: #1e293b;
            color: #f8fafc;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.05em;
            padding: 15px;
            border: none;
        }

        #siteTable tbody tr:hover {
            background-color: #f0f7ff !important;
        }

        .site-group-header {
            background: linear-gradient(90deg, #e2e8f0 0%, #f8fafc 100%) !important;
            font-weight: 800;
            color: #334155;
            border-bottom: 2px solid #cbd5e1 !important;
        }

        .badge-msa {
            font-family: 'JetBrains Mono', monospace;
            font-size: 0.9rem;
            font-weight: 600;
            background: #f1f5f9;
            color: #2563eb;
            padding: 6px 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }

        .projet-name {
            font-size: 1.05rem;
            color: #0f172a;
            font-weight: 700;
        }
    </style>

    <div class="container-fluid py-4">
        {{-- Header --}}
        <div class="column_title rounded shadow-sm d-flex justify-content-between align-items-center">
            <div>
                <h2 class="fw-bold text-primary mb-0"><i class="fas fa-project-diagram me-2"></i> Gestion des Projets</h2>
                <p class="text-muted mb-0 small">Administration du périmètre et des structures</p>
            </div>
            <div class="breadcrumb-custom d-none d-md-block">
                <span class="badge bg-light text-secondary border">ManagerPoint / Référentiel</span>
            </div>
        </div>

        {{-- Bloc Importation (RH uniquement) & Stats --}}
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <div class="row align-items-center">
                    @if (auth()->user()->hasRole('RH'))
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
                        <div class="col-md-5 text-center">
                        @else
                            <div class="col-md-12 text-center">
                    @endif
                    <label class="form-label small fw-bold mb-2 text-uppercase text-secondary">Statistiques rapides</label>
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

    {{-- Tableau --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body p-4">
            <div class="table-responsive">
                <table id="siteTable" class="table align-middle w-100">
                    <thead>
                        <tr>
                            <th>Site (Groupe)</th>
                            <th style="width: 150px;">MSA ID</th>
                            <th>Désignation du Projet</th>
                            <th>DLT Superviseur</th>
                            @if (auth()->user()->hasRole('RH'))
                                <th class="text-center">Action</th>
                            @endif
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
            const isRH = {{ auth()->user()->hasRole('RH') ? 'true' : 'false' }};
            const groupColumn = 0;

            // 1. Définition dynamique des colonnes
            let columnsDef = [{
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
                }
            ];

            if (isRH) {
                columnsDef.push({
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                });
            }

            // 2. Initialisation DataTables
            const table = $('#siteTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('projet.ajax') }}",
                    error: function(xhr) {
                        console.error("Erreur Backend:", xhr.responseText);
                    }
                },
                columns: columnsDef,
                order: [
                    [groupColumn, 'asc']
                ],
                dom: '<"row align-items-center mb-3"<"col-md-4"B><"col-md-4 text-center"l><"col-md-4"f>>rtip',
                buttons: [{
                    extend: 'excel',
                    className: 'btn btn-outline-success btn-sm border-2',
                    text: '<i class="far fa-file-excel me-2"></i>Exporter'
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

                    // Ajustement du colspan : 4 colonnes visibles si RH (MSA, Nom, DLT, Action), sinon 3
                    const dynamicColspan = isRH ? 4 : 3;

                    api.column(groupColumn, {
                        page: 'current'
                    }).data().each(function(group, i) {
                        if (last !== group) {
                            const count = api.column(groupColumn).data().filter(v => v ===
                                group).length;
                            $(rows).eq(i).before(
                                `<tr class="site-group-header">
                                    <td colspan="${dynamicColspan}" class="py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div><i class="fas fa-building me-2 text-primary"></i>SITE : <span class="fw-bolder">${group ? group.toUpperCase() : 'SANS SITE'}</span></div>
                                            <span class="badge rounded-pill bg-primary shadow-sm">${count} Projets</span>
                                        </div>
                                    </td>
                                </tr>`
                            );
                            last = group;
                        }
                    });

                    // Update Stats
                    const info = api.page.info();
                    $('#totalProjets').text(info.recordsTotal);
                    const uniqueSites = [...new Set(api.column(groupColumn).data().toArray())].filter(
                        n => n);
                    $('#totalSites').text(uniqueSites.length);
                }
            });
        });
    </script>
@endpush
