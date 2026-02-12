@extends('layouts.app')

@section('content')
<style>
    /* Correction pour écrans larges */
    @media (min-width: 1598px) {
        .container { max-width: 1598px !important; }
    }

    /* Style du titre */
    .styled-title {
        font-family: 'Segoe UI', sans-serif;
        font-size: 2.2rem;
        color: #1d4750;
        font-weight: 700;
        margin-top: 88px;
        margin-bottom: 19px;
        text-align: center;
    }

    /* Personnalisation du tableau */
    #usersTable {
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 0 15px rgba(0,0,0,0.05);
    }

    #usersTable thead th {
        background-color: #1d4750;
        color: white;
        text-align: center;
        vertical-align: middle;
        border: none;
    }

    .dt-buttons .btn {
        margin-bottom: 10px;
        border-radius: 5px !important;
        font-weight: 600;
    }
</style>

<div class="container-fluid">
    <div class="row">
            <div class="col-md-12">
                <div class="column_title">
                    <h2>👥 Gestion des Utilisateurs</h2>
                    <div class="breadcrumb-custom d-none d-md-block">
                        <span>ManagerPoint</span> / <span> Gestion des Utilisateurs</span>
                    </div>
                </div>
            </div>
    </div>
    <div class="card border-0 shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table id="usersTable" class="table table-striped table-hover align-middle w-100">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nom</th>
                            <th>Email</th>
                            <th>Fonction</th>
                            <th>Rôle</th>
                            <th>Projet</th>
                            <th>Site</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    {{-- DataTables CSS --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css"/>
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.dataTables.min.css"/>

    {{-- DataTables JS & Extensions --}}
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>

    <script>
    $(document).ready(function() {
        // Nettoyage avant initialisation
        if ($.fn.DataTable.isDataTable('#usersTable')) {
            $('#usersTable').DataTable().destroy();
        }

        $('#usersTable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('users.ajax') }}",
            columns: [
                { data: 'user_id',     name: 'user_id' },
                { data: 'name',        name: 'name' },
                { data: 'email',       name: 'email' },
                { data: 'fonction',    name: 'agents.fonction' },
                { data: 'role',        name: 'role' },
                { data: 'projet',      name: 'projet' },
                { data: 'site',        name: 'site' },
                {
                    data: 'action',
                    name: 'action',
                    orderable: false,
                    searchable: false,
                    className: 'text-center'
                }
            ],
            dom: 'lBfrtip',
            buttons: [
                { extend: 'copy',  text: 'Copier', className: 'btn btn-secondary btn-sm' },
                { extend: 'excel', text: 'Excel',  className: 'btn btn-success btn-sm' },
                { extend: 'pdf',   text: 'PDF',    className: 'btn btn-danger btn-sm' },
                { extend: 'print', text: 'Imprimer', className: 'btn btn-info btn-sm' }
            ],
            pageLength: 50,
            lengthMenu: [[50, 100, 200, -1], ['50', '100', '200', 'Tous']],
            
            // TRADUCTION LOCALE DIRECTE
            language: {
                "sEmptyTable":     "Aucune donnée disponible",
                "sInfo":           "Affichage de _START_ à _END_ sur _TOTAL_ utilisateurs",
                "sInfoEmpty":      "Affichage de 0 à 0 sur 0 utilisateur",
                "sInfoFiltered":   "(filtré de _MAX_ au total)",
                "sLengthMenu":     "Afficher _MENU_ éléments",
                "sLoadingRecords": "Chargement...",
                "sProcessing":     "Traitement en cours...",
                "sSearch":         "Rechercher :",
                "sZeroRecords":    "Aucun résultat trouvé",
                "oPaginate": {
                    "sFirst":    "Premier",
                    "sLast":     "Dernier",
                    "sNext":     "Suivant",
                    "sPrevious": "Précédent"
                }
            }
        });
    });
    </script>
@endpush