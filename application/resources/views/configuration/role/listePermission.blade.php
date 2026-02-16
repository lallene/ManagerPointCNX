@extends('layouts.app')

@section('content')
    <style>
        .column_title {
            background: var(--white);
            padding: 1.5rem;
            margin: -1rem -1rem 2rem -1rem;
            border-bottom: 1px solid #e9ecef;
        }

        .column_title h2::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--accent);
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
            vertical-align: middle;
        }

        .permission-badge {
            background-color: #f1f5f9;
            color: #475569;
            border: 1px solid #e2e8f0;
            padding: 5px 12px;
            border-radius: 6px;
            font-family: monospace;
            font-size: 0.85rem;
            margin: 4px;
            display: inline-block;
        }
    </style>

    <div class="container-fluid">
        <div class="row column_title">
            <div class="col-md-12 d-flex justify-content-between align-items-center">
                <h2>{{ $titre }}</h2>
                <a href="{{ route('profil.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fa fa-arrow-left"></i> Retour aux profils
                </a>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-muted small fw-bold text-uppercase">Droits activés pour ce profil</h5>
                            <a href="{{ URL::to('profil/permission/add/' . $role->id) }}" class="btn btn-primary btn-sm">
                                <i class="fa fa-plus"></i> Gérer les permissions
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        @if ($permissions->count() > 0)
                            <div class="d-flex flex-wrap">
                                @foreach ($permissions as $permission)
                                    <div class="permission-badge">
                                        <i class="fa fa-check-circle text-success me-1"></i> {{ $permission->name }}
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="text-center py-5">
                                <i class="fa fa-shield-alt fa-3x text-light mb-3"></i>
                                <p class="text-muted">Aucune permission n'est attribuée à ce profil pour le moment.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
