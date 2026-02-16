@extends('layouts.app')

@section('content')
    <style>
        /* Harmonisation Signature ManagerPoint */
        .column_title {
            background: var(--white);
            padding: 1.5rem;
            margin: -1rem -1rem 2rem -1rem;
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .column_title h2 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--nav-bg);
            /* Ton bleu foncé */
            margin: 0;
            text-transform: uppercase;
            display: flex;
            align-items: center;
        }

        .column_title h2::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--accent);
            /* Ton vert action */
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
        }

        /* Tableaux Style Audit */
        #usersTable {
            border-collapse: separate !important;
            border-spacing: 0;
            width: 100% !important;
        }

        #usersTable thead th {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            padding: 12px;
            border: none !important;
        }

        #usersTable tbody td {
            padding: 12px;
            vertical-align: middle;
            font-size: 0.85rem;
            color: #334155;
            border-bottom: 1px solid #f1f5f9;
        }

        /* Badge de rôle personnalisé */
        .badge-role {
            padding: 4px 8px;
            border-radius: 4px;
            font-weight: 600;
            font-size: 0.7rem;
            text-transform: uppercase;
        }

        /* Style des boutons DataTables */
        .dt-buttons {
            margin-bottom: 1.5rem;
        }

        .dt-buttons .btn {
            font-weight: 600;
            font-size: 0.75rem;
            border-radius: 6px !important;
            margin-right: 5px;
            border: 1px solid #e2e8f0;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête de page --}}
        <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>👥 Gestion des Utilisateurs</h2>
                    <div class="breadcrumb-custom d-none d-md-block">
                        <small class="text-muted">ManagerPoint / Administration / Utilisateurs</small>
                    </div>
                </div>
            </div>
        </div>

        {{-- Carte du tableau --}}
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="usersTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Nom & Prénom</th>
                                <th>Email</th>
                                <th>Fonction</th>
                                <th>Rôle</th>
                                <th>Projet</th>
                                <th>Site</th>
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
    {{-- Utilisation de Bootstrap 5 pour DataTables pour la cohérence visuelle --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#usersTable')) {
                $('#usersTable').DataTable().destroy();
            }

            $('#usersTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('users.ajax') }}",
                columns: [{
                        data: 'user_id',
                        name: 'user_id'
                    },
                    {
                        data: 'name',
                        name: 'name',
                        render: function(data) {
                            return '<span class="fw-bold text-dark">' + data + '</span>';
                        }
                    },
                    {
                        data: 'work_email',
                        name: 'work_email'
                    },
                    {
                        data: 'fonction',
                        name: 'agents.fonction'
                    },
                    {
                        data: 'role',
                        name: 'role',
                        render: function(data) {
                            var badgeClass = data === 'Admin' ?
                                'bg-light text-danger border-danger' :
                                'bg-light text-primary border-primary';
                            return '<span class="badge border ' + badgeClass + '">' + data +
                                '</span>';
                        }
                    },
                    {
                        data: 'projet',
                        name: 'projet'
                    },
                    {
                        data: 'site',
                        name: 'site'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                dom: '<"d-flex justify-content-between align-items-center mb-4"lBf>rtip',
                buttons: [{
                        extend: 'excel',
                        className: 'btn btn-outline-success btn-sm',
                        text: '<i class="far fa-file-excel me-1"></i> Excel'
                    },
                    {
                        extend: 'pdf',
                        className: 'btn btn-outline-danger btn-sm',
                        text: '<i class="far fa-file-pdf me-1"></i> PDF'
                    }
                ],
                pageLength: 50,
                language: {
                    "search": "Rechercher :",
                    "lengthMenu": "Afficher _MENU_",
                    "info": "Affichage de _START_ à _END_ sur _TOTAL_ utilisateurs",
                    "paginate": {
                        "next": "Suivant",
                        "previous": "Précédent"
                    },
                    "processing": "<div class='spinner-border text-primary' role='status'></div>"
                }
            });
        });
    </script>
@endpush
