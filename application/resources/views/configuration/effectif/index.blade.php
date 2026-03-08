@extends('layouts.app')

@section('content')
    <style>
        /* Signature visuelle ManagerPoint */
        .column_title {
            background: var(--white);
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
            background: var(--accent);
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
        }

        .import-section {
            background-color: #f8fafb;
            border-bottom: 1px solid #edf2f7;
            padding: 1.5rem;
        }

        .label-recherche {
            font-size: 0.85rem;
            font-weight: 700;
            color: #64748b;
            text-transform: uppercase;
            margin-bottom: 8px;
            display: block;
        }

        .site-badge {
            width: 32px;
            height: 32px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #f1f5f9;
            border-radius: 8px;
            font-weight: 800;
            color: #1e293b;
            border: 1px solid #e2e8f0;
            margin: 0 auto;
        }

        .badge-projet {
            background-color: #eaf2ff;
            color: #0d6efd;
            border: 1px solid #d0e2ff;
            font-weight: 600;
            font-size: 0.65rem;
            padding: 0.4em 0.8em;
            text-transform: uppercase;
            border-radius: 4px;
        }

        .fonction-tag {
            font-size: 0.75rem;
            letter-spacing: 0.3px;
            color: #475569;
            font-weight: 500;
            text-transform: uppercase;
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

        #agentTable tbody tr:hover {
            background-color: #f8fafc !important;
        }
    </style>

    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>{{ $titre }}</h2>
                    @if (auth()->user()->hasAnyRole(['RH', 'IT', 'Directeur']))
                        <div class="breadcrumb-custom">
                            <a href="{{ asset('templates/masque_import_agents.xlsx') }}"
                                class="btn btn-template btn-sm px-3 shadow-sm">
                                <i class="fas fa-download me-2"></i> Télécharger le modèle Excel
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            {{-- Section d'importation : Visible uniquement pour le Board --}}
            @if (auth()->user()->hasAnyRole(['RH', 'IT', 'Directeur']))
                <div class="import-section">
                    <form action="{{ route('effectif.import') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row align-items-end g-3">
                            <div class="col-md-5">
                                <label class="label-recherche">
                                    <i class="fas fa-file-excel me-2 text-success"></i>Fichier de données
                                </label>
                                <input class="form-control form-control-sm" type="file" name="agent_file"
                                    accept=".xlsx,.xls,.csv" required>
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-success btn-sm w-100 shadow-sm">
                                    <i class="fas fa-upload me-1"></i> Importer
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @endif

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="agentTable" class="table align-middle w-100">
                        <thead>
                            <tr>
                                <th class="text-center">Site</th>
                                <th>Matricule</th>
                                <th>Agent</th>
                                <th>Fonction</th>
                                <th>Email Professionnel</th>
                                <th>Projet(s)</th>
                                <th>Responsable Direct</th>
                                {{-- Colonne action visible uniquement pour RH ou Admin --}}
                                @if (auth()->user()->hasAnyRole(['RH', 'IT']))
                                    <th class="text-center">Actions</th>
                                @endif
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

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
                // Définition des droits côté JS
                const canEdit = {{ auth()->user()->hasAnyRole(['RH', 'IT'])? 'true': 'false' }};

                let columnsConfig = [{
                        data: 'site',
                        name: 'site',
                        className: 'text-center',
                        render: data => `<div class="site-badge">${data || '?'}</div>`
                    },
                    {
                        data: 'workday_id',
                        name: 'workday_id',
                        render: data => `<span class="fw-bold text-primary">${data}</span>`
                    },
                    {
                        data: 'nom',
                        name: 'nom',
                        render: (data, type, row) => `
                            <div>
                                <div class="fw-bold text-dark">${data.toUpperCase()}</div>
                                <div class="small text-muted">${row.prenom || ''}</div>
                            </div>`
                    },
                    {
                        data: 'fonction',
                        name: 'fonction',
                        render: data =>
                            `<span class="fonction-tag"><i class="fas fa-id-badge me-1 opacity-50"></i>${data}</span>`
                    },
                    {
                        data: 'work_email',
                        name: 'work_email',
                        render: data =>
                            `<span class="small text-muted"><i class="far fa-envelope me-1"></i>${data}</span>`
                    },
                    {
                        data: 'projet',
                        name: 'projet',
                        render: data => {
                            if (!data) return '-';
                            return data.split(',').map(p =>
                                `<span class="badge badge-projet mb-1">${p.trim()}</span>`).join(' ');
                        }
                    },
                    {
                        data: 'manager_nom',
                        name: 'manager_nom',
                        render: data =>
                            `<span class="text-secondary small"><i class="fas fa-user-check me-1 opacity-50"></i>${data || 'DIRECTION'}</span>`
                    }
                ];

                // Ajout de la colonne action si l'utilisateur a les droits
                if (canEdit) {
                    columnsConfig.push({
                        data: null,
                        orderable: false,
                        searchable: false,
                        className: 'text-center',
                        render: function(data, type, row) {
                            return `
                                <div class="btn-group">
                                    <a href="{{ url('effectif') }}/${row.id}/edit" class="btn btn-light btn-sm border" title="Modifier">
                                        <i class="fas fa-edit text-primary"></i>
                                    </a>
                                    <button type="button" class="btn btn-light btn-sm border" 
                                            onclick="confirmDelete('{{ url('effectif') }}/${row.id}', '${row.nom}')" title="Supprimer">
                                        <i class="fas fa-trash text-danger"></i>
                                    </button>
                                </div>`;
                        }
                    });
                }

                $('#agentTable').DataTable({
                    processing: true,
                    serverSide: true,
                    ajax: "{{ route('effectif.ajax') }}",
                    columns: columnsConfig,
                    dom: '<"d-flex justify-content-between align-items-center mb-3"lBf>rtip',
                    buttons: [{
                            extend: 'excel',
                            className: 'btn btn-outline-success btn-sm',
                            text: '<i class="fas fa-file-excel me-1"></i> Excel'
                        },
                        {
                            extend: 'pdf',
                            className: 'btn btn-outline-danger btn-sm',
                            text: '<i class="fas fa-file-pdf me-1"></i> PDF'
                        }
                    ],
                    pageLength: 25,
                    language: {
                        url: "//cdn.datatables.net/plug-ins/1.13.6/i18n/fr-FR.json"
                    }
                });
            });

            function confirmDelete(url, name) {
                if (confirm(`⚠️ Supprimer définitivement l'agent ${name} ?`)) {
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = url;
                    form.innerHTML = `@csrf @method('DELETE')`;
                    document.body.appendChild(form);
                    form.submit();
                }
            }
        </script>
    @endpush
@endsection
