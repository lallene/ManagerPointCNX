@extends('layouts.app')

@section('content')
    <style>
        /* Harmonisation avec le style global du dashboard */
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
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
        }

        /* Style de la zone d'importation (Header de carte) */
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
            letter-spacing: 0.5px;
            margin-bottom: 8px;
            display: block;
        }

        /* Tableaux pro */
        .table thead th {
            background-color: #f1f5f9;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-top: none !important;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
        }

        /* Style des boutons DataTables pour correspondre au dashboard */
        .dt-buttons .btn {
            font-weight: 600;
            font-size: 0.8rem;
            border-radius: 6px;
            margin-right: 5px;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête de page --}}
        <div class="column_title">
            <h2>Gestion des Projets</h2>
            <div class="breadcrumb-custom d-none d-md-block">
                <small class="text-muted">ManagerPoint / Gestion des Projets</small>
            </div>
        </div>

        <div class="card shadow-sm border-0 mb-5">
            {{-- Section Import et Action --}}

            {{-- Section Tableau --}}
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="siteTable" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th>MSA ID</th>
                                <th>Désignation</th>
                                <th>Site</th>
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
    {{-- On utilise les CDN recommandés par Lead Dev pour éviter les erreurs CORS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap5.min.css">

    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>

    <script>
        $(document).ready(function() {
            // Nettoyage préalable pour éviter les erreurs de ré-initialisation
            if ($.fn.DataTable.isDataTable('#siteTable')) {
                $('#siteTable').DataTable().destroy();
            }

            $('#siteTable').DataTable({
                processing: true,
                serverSide: true,
                ajax: "{{ route('projets.ajax') }}",
                columns: [{
                        data: 'msa_id',
                        name: 'projets.msa_id'
                    },
                    {
                        data: 'designation',
                        name: 'projets.designation'
                    },
                    {
                        data: 'site_nom',
                        name: 'site_nom'
                    },
                    {
                        data: 'dltsuperviseur',
                        name: 'projets.dltsuperviseur'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                        className: 'text-center'
                    }
                ],
                dom: '<"d-flex justify-content-between align-items-center mb-3"lBf>rtip',
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
                lengthMenu: [
                    [10, 50, 100, -1],
                    [10, 50, 100, "Tous"]
                ],
                language: {
                    "emptyTable": "Aucune donnée disponible",
                    "info": "Affichage de _START_ à _END_ sur _TOTAL_ projets",
                    "infoEmpty": "Affichage de 0 à 0 sur 0 projet",
                    "loadingRecords": "Chargement...",
                    "processing": "Traitement en cours...",
                    "search": "Rechercher :",
                    "zeroRecords": "Aucun projet trouvé",
                    "paginate": {
                        "next": "Suivant",
                        "previous": "Précédent"
                    }
                }
            });
        });
    </script>
@endpush
