@extends('layouts.app')

@section('content')
    <meta charset="UTF-8">
    <meta http-equiv="Content-Language" content="fr">

    <style>
        :root {
            --primary-blue: #0d47a1;
            --success-green: #198754;
            --bg-light: #f4f7f6;
            --accent-dark: #1e293b;
        }

        /* Style global et espacements */
        .column_title {
            background: white;
            padding: 1.5rem;
            margin: -1rem -1rem 2rem -1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .column_title h2::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--primary-blue);
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
            vertical-align: middle;
        }

        /* Section Importation Premium */
        .import-section {
            background-color: #f8fafb;
            border-bottom: 1px solid #edf2f7;
            padding: 1.5rem;
        }

        .label-recherche {
            font-size: 0.8rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        /* Badges et Tags */
        .badge-msa {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 0.85rem;
            font-weight: 700;
            background: #f1f5f9;
            color: #2563eb;
            padding: 5px 10px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
            display: inline-block;
        }

        .projet-name {
            font-size: 0.95rem;
            color: #0f172a;
            font-weight: 700;
        }

        /* Header de groupe Site */
        .site-group-header {
            background: linear-gradient(90deg, #f1f5f9 0%, #ffffff 100%) !important;
            font-weight: 800;
            color: #334155;
            border-bottom: 1px solid #cbd5e1 !important;
        }

        .site-icon-badge {
            width: 24px;
            height: 24px;
            background: var(--primary-blue);
            color: white;
            border-radius: 4px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.7rem;
            margin-right: 10px;
        }

        /* Table custom */
        #siteTable thead th {
            background-color: var(--accent-dark);
            color: white;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 15px;
            border: none;
        }

        #siteTable tbody tr:hover {
            background-color: #f8fafc !important;
        }

        .btn-template {
            color: #198754;
            background-color: #eafaf1;
            border: 1px dashed #198754;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.3s;
        }

        .btn-template:hover {
            background-color: #198754;
            color: white;
        }
    </style>

    <div class="container-fluid">
        {{-- Header avec signature visuelle --}}
        <div class="column_title shadow-sm">
            <h2 class="mb-0">{{ $titre ?? 'Gestion du Référentiel' }}</h2>
            <div class="breadcrumb-custom">
                <a href="{{ asset('templates/masque_import_projets.xlsx') }}" class="btn btn-template btn-sm px-3 shadow-sm">
                    <i class="fas fa-download me-2"></i> Télécharger le modèle Excel
                </a>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            {{-- Zone Importation (RH uniquement) --}}
            <div class="import-section">
                <div class="row align-items-center g-3">
                    @if (auth()->user()->hasRole('RH'))
                        <div class="col-md-7 border-end">
                            <form action="{{ route('projet.import') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                <label class="label-recherche">
                                    <i class="fas fa-upload me-2 text-success"></i>Mise à jour du Référentiel
                                </label>
                                <div class="input-group">
                                    <input type="file" name="file" class="form-control form-control-sm" required>
                                    <button type="submit" class="btn btn-success btn-sm px-4 shadow-sm">
                                        <i class="fas fa-check-circle me-1"></i> Importer
                                    </button>
                                </div>
                            </form>
                        </div>
                    @endif

                    <div class="col-md-5">
                        <div class="d-flex justify-content-around text-center">
                            <div>
                                <span class="d-block h4 mb-0 fw-bold text-primary" id="totalProjets">--</span>
                                <small class="text-muted fw-bold">PROJETS</small>
                            </div>
                            <div class="border-start ps-4">
                                <span class="d-block h4 mb-0 fw-bold text-secondary" id="totalSites">--</span>
                                <small class="text-muted fw-bold">SITES</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="siteTable" class="table align-middle w-100">
                        <thead>
                            <tr>
                                <th>Site Groupe</th> {{-- Caché mais utilisé pour le groupement --}}
                                <th style="width: 140px;">MSA ID</th>
                                <th>Désignation du Projet</th>
                                <th>DLT Superviseur</th>
                                @if (auth()->user()->hasRole('RH'))
                                    <th class="text-center">Actions</th>
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

            let columnsDef = [{
                    data: 'site_nom',
                    name: 'sites.designation',
                    visible: false // Colonne de groupement
                },
                {
                    data: 'msa_id',
                    name: 'projets.msa_id',
                    render: data => data ? `<span class="badge-msa">${data}</span>` :
                        '<span class="text-muted small">N/A</span>'
                },
                {
                    data: 'projet_nom',
                    name: 'projets.designation',
                    render: (data, type, row) => `
                        <div>
                            <div class="projet-name">${data}</div>
                        </div>`
                },
                {
                    data: 'dltsuperviseur',
                    name: 'projets.dltsuperviseur',
                    render: data => data ?
                        `<span class="text-dark small"><i class="fas fa-user-circle me-1 text-secondary"></i>${data}</span>` :
                        '<span class="text-muted italic small">Non assigné</span>'
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

            const table = $('#siteTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('projet.ajax') }}",
                columns: columnsDef,
                order: [
                    [groupColumn, 'asc']
                ],
                dom: '<"d-flex justify-content-between align-items-center mb-3"lBf>rtip',
                buttons: [{
                    extend: 'excel',
                    className: 'btn btn-outline-success btn-sm border-2',
                    text: '<i class="far fa-file-excel me-2"></i>Exporter'
                }],
                pageLength: 50,
                language: {
                    url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
                },
                drawCallback: function(settings) {
                    const api = this.api();
                    const rows = api.rows({
                        page: 'current'
                    }).nodes();
                    let last = null;
                    const dynamicColspan = isRH ? 4 : 3;

                    api.column(groupColumn, {
                        page: 'current'
                    }).data().each(function(group, i) {
                        if (last !== group) {
                            $(rows).eq(i).before(
                                `<tr class="site-group-header">
                                    <td colspan="${dynamicColspan}" class="py-3">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <span class="site-icon-badge"><i class="fas fa-building"></i></span>
                                                SITE : <span class="fw-bolder text-primary">${group ? group.toUpperCase() : 'NON RATTACHÉ'}</span>
                                            </div>
                                            <span class="badge rounded-pill bg-light text-primary border border-primary shadow-sm px-3">
                                                Structure active
                                            </span>
                                        </div>
                                    </td>
                                </tr>`
                            );
                            last = group;
                        }
                    });

                    // Mise à jour des compteurs Stats
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
