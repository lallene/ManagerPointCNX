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

        /* Style du tableau Audit */
        .table thead th {
            background-color: #f1f5f9 !important;
            color: #475569 !important;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border: none !important;
        }

        .table td {
            vertical-align: middle;
            font-size: 0.9rem;
            color: #334155;
        }

        .role-name {
            font-weight: 700;
            color: var(--nav-bg);
        }

        /* Boutons d'actions compacts */
        .btn-action {
            width: 35px;
            height: 35px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin: 0 2px;
            transition: all 0.2s;
        }

        .btn-permission {
            width: auto;
            padding: 0 12px;
            height: 35px;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête de page --}}
        <div class="column_title">
            <h2><i class="fas fa-user-shield me-2"></i> {{ $titre }}</h2>
            <div class="breadcrumb-custom d-none d-md-block">
                <small class="text-muted">ManagerPoint / Paramètres / Profils & Rôles</small>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small fw-bold text-uppercase">Niveaux d'accès utilisateurs</span>
                    <div>
                        <a href="{{ route('profil.create') }}" class="btn btn-primary btn-sm shadow-sm px-3">
                            <i class="fa fa-plus-circle me-1"></i> Ajouter un Profil
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-danger btn-sm px-3 ms-2">
                            <i class="fa fa-sign-out-alt"></i> Quitter
                        </a>
                    </div>
                </div>
            </div>

            <div class="card-body p-4">
                <div class="table-responsive">
                    <table id="zero_config" class="table table-hover w-100">
                        <thead>
                            <tr>
                                <th style="width: 70px;">#</th>
                                <th>Désignation (Rôle)</th>
                                <th class="text-center">Gestion des Droits</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($roles as $index => $role)
                                <tr>
                                    <td class="text-muted">#{{ $index + 1 }}</td>
                                    <td>
                                        <span class="role-name">{{ $role->name }}</span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ URL::to('profil/permission/' . $role->id) }}"
                                            class="btn btn-outline-warning btn-permission">
                                            <i class="fa fa-lock me-1"></i> Permissions
                                        </a>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <a href="{{ route('profil.edit', $role->id) }}"
                                                class="btn btn-outline-primary btn-action" title="Modifier le nom">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            <form action="{{ route('profil.destroy', $role->id) }}" method="POST"
                                                onsubmit="return confirm('Supprimer ce profil ?');" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-outline-danger btn-action"
                                                    title="Supprimer">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    {{-- Intégration DataTables Bootstrap 5 --}}
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            if ($.fn.DataTable.isDataTable('#zero_config')) {
                $('#zero_config').DataTable().destroy();
            }

            $('#zero_config').DataTable({
                "pageLength": 25,
                "order": [
                    [0, "asc"]
                ],
                "dom": '<"d-flex justify-content-between align-items-center mb-3"lf>rtip',
                "language": {
                    "search": "Rechercher :",
                    "lengthMenu": "Afficher _MENU_",
                    "info": "Affichage de _START_ à _END_ sur _TOTAL_ profils",
                    "paginate": {
                        "next": "Suivant",
                        "previous": "Précédent"
                    },
                    "emptyTable": "Aucun profil enregistré."
                }
            });
        });
    </script>
@endpush
