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

        /* Tableaux Style Audit */
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

        /* Style technique pour les permissions */
        .permission-slug {
            font-family: 'Monaco', 'Consolas', monospace;
            background-color: #f8fafc;
            color: #475569;
            padding: 4px 8px;
            border-radius: 4px;
            border: 1px solid #e2e8f0;
            font-size: 0.85rem;
        }

        /* Action buttons */
        .btn-action {
            width: 32px;
            height: 32px;
            padding: 0;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 6px;
            margin: 0 2px;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête de page --}}
        <div class="column_title">
            <h2><i class="fas fa-shield-alt me-2"></i> {{ $titre }}</h2>
            <div class="breadcrumb-custom d-none d-md-block">
                <small class="text-muted">ManagerPoint / Paramètres / Permissions</small>
            </div>
        </div>

        <div class="card shadow-sm border-0">
            <div class="card-header bg-white py-3 border-bottom">
                <div class="d-flex justify-content-between align-items-center">
                    <span class="text-muted small fw-bold text-uppercase">Liste des accès système</span>
                    <div>
                        <a href="{{ route('permission.create') }}" class="btn btn-primary btn-sm shadow-sm px-3">
                            <i class="fa fa-plus-circle me-1"></i> Ajouter une Permission
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-danger btn-sm px-3">
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
                                <th style="width: 80px;">ID</th>
                                <th>Désignation (Nom technique)</th>
                                <th class="text-center" style="width: 120px;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($permissions as $index => $permission)
                                <tr>
                                    <td class="text-muted fw-bold">#{{ $index + 1 }}</td>
                                    <td>
                                        <span class="permission-slug">{{ $permission->name }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex justify-content-center">
                                            <a href="{{ route('permission.edit', $permission->id) }}"
                                                class="btn btn-outline-primary btn-action" title="Modifier">
                                                <i class="fa fa-edit"></i>
                                            </a>

                                            <form action="{{ route('permission.destroy', $permission->id) }}" method="POST"
                                                onsubmit="return confirm('Supprimer définitivement cette permission ?');"
                                                class="d-inline">
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
    {{-- On utilise le standard Bootstrap 5 pour DataTables --}}
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
                    "info": "Affichage de _START_ à _END_ sur _TOTAL_ permissions",
                    "paginate": {
                        "next": "Suivant",
                        "previous": "Précédent"
                    },
                    "emptyTable": "Aucune permission enregistrée."
                }
            });
        });
    </script>
@endpush
