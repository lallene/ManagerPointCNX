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

        /* Style spécifique pour le bouton modèle */
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
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>{{ $titre }}</h2>
                    <div class="breadcrumb-custom">
                        {{-- Bouton pour télécharger le masque Excel --}}
                        <a href="{{ asset('templates/masque_import_agents.xlsx') }}"
                            class="btn btn-template btn-sm px-3 shadow-sm">
                            <i class="fas fa-download me-2"></i> Télécharger le modèle Excel
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            <div class="import-section">
                <form action="{{ route('import_agent') }}" method="POST" enctype="multipart/form-data">
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
                        <div class="col-md-5 text-md-end">
                            <a href="{{ route('agents.create') }}" class="btn btn-primary btn-sm shadow-sm px-4">
                                <i class="fa fa-plus-circle me-1"></i> Ajout Manuel
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <div class="card-body p-4">
                {{-- Table DataTables identique à ton code précédent --}}
                <div class="table-responsive">
                    <table id="agentTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>Site</th>
                                <th>Matricule</th>
                                <th>Nom & Prénom</th>
                                <th>Fonction</th>
                                <th>Email</th>
                                <th>Projet</th>
                                <th>Responsable</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Utilisation de Bootstrap 5 pour DataTables pour la cohérence --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#agentTable')) {
                $('#agentTable').DataTable().destroy();
            }

            $('#agentTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: {
                    url: "{{ route('agents.ajax') }}",
                    type: "GET",
                    error: function(xhr, error, thrown) {
                        console.error("Erreur DataTables:", xhr.responseText);
                    }
                },
                columns: [{
                        data: 'site',
                        name: 'projets.site_id'
                    },
                    {
                        data: 'workday_id',
                        name: 'agents.workday_id'
                    },
                    {
                        data: null,
                        name: 'agents.nom',
                        render: function(data, type, row) {
                            if (!row.nom) return '-';
                            const firstPrenom = row.prenom ? row.prenom.split(' ')[0] : '';
                            return `<strong class="text-dark">${row.nom}</strong> ${firstPrenom}`;
                        }
                    },
                    {
                        data: 'fonction',
                        name: 'agents.fonction'
                    },
                    {
                        data: 'work_email',
                        name: 'agents.work_email'
                    },
                    {
                        data: 'projet',
                        name: 'projets.designation'
                    },
                    {
                        data: 'manager_nom',
                        name: 'agents.manager'
                    }
                ],
                dom: '<"d-flex justify-content-between align-items-center mb-3"lBf>rtip',
                buttons: [{
                        extend: 'excel',
                        className: 'btn btn-outline-success btn-sm',
                        text: 'Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-outline-danger btn-sm',
                        text: 'PDF'
                    }
                ],
                pageLength: 50,
                language: {
                    "search": "Rechercher :",
                    "processing": "Chargement des données...",
                    "emptyTable": "Aucun agent trouvé",
                    "paginate": {
                        "next": "Suivant",
                        "previous": "Précédent"
                    }
                }
            });
        });
    </script>
@endpush
