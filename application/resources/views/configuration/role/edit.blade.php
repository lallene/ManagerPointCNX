@extends('layouts.app')

@section('content')
    <style>
        /* Signature visuelle ManagerPoint */
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

        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .card {
            border-radius: 12px;
            border: none;
        }
    </style>

    <div class="container-fluid">
        <div class="row column_title">
            <div class="col-md-12">
                <h2><i class="fas fa-user-edit me-2"></i> Modifier le Profil : {{ $role->name }}</h2>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <form method="POST" action="{{ route('profil.update', $role->id) }}">
                            @csrf
                            @method('PUT')

                            <div class="row g-4">
                                {{-- NOM DU PROFIL --}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="name">Désignation du Profil *</label>
                                        <input id="name" type="text"
                                            class="form-control @error('name') is-invalid @enderror" name="name"
                                            value="{{ old('name', $role->name) }}" required
                                            placeholder="Ex: Administrateur, Agent...">
                                        @error('name')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>

                                {{-- GUARD NAME --}}
                                <div class="col-md-12">
                                    <div class="form-group">
                                        <label class="form-label" for="guard_name">Guard Name</label>
                                        <input id="guard_name" type="text"
                                            class="form-control @error('guard_name') is-invalid @enderror" name="guard_name"
                                            value="{{ old('guard_name', $role->guard_name) }}" required>
                                        <small class="text-muted">Généralement 'web'</small>
                                    </div>
                                </div>

                                {{-- ACTIONS --}}
                                <div class="col-12 mt-5 border-top pt-4 text-center">
                                    <a href="{{ route('profil.index') }}" class="btn btn-outline-secondary px-4 me-2">
                                        <i class="fas fa-undo me-1"></i> Retour
                                    </a>
                                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                        <i class="fas fa-save me-1"></i> Enregistrer les modifications
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
