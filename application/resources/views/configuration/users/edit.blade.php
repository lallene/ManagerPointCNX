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

        /* Style du Formulaire Audit */
        .form-label {
            font-weight: 700;
            font-size: 0.85rem;
            color: #475569;
            text-transform: uppercase;
            margin-bottom: 0.5rem;
        }

        .form-control,
        .form-select {
            border-radius: 8px;
            border: 1.5px solid #e2e8f0;
            padding: 0.65rem 1rem;
            transition: all 0.2s ease;
        }

        .form-control:focus {
            border-color: var(--accent);
            box-shadow: 0 0 0 3px rgba(25, 135, 84, 0.1);
        }

        .card {
            border: none;
            border-radius: 12px;
        }

        .input-icon-group {
            position: relative;
        }

        .input-icon-group i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête de page --}}
        <div class="row column_title">
            <div class="col-md-12">
                <h2>{{ $titre }} : {{ $user->name }}</h2>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-md-8">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <form action="{{ route('users.update', $user->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="row g-4">
                                {{-- IDENTITÉ --}}
                                <div class="col-md-6">
                                    <label for="name" class="form-label">Nom Complet</label>
                                    <div class="input-icon-group">
                                        <input type="text" name="name" id="name"
                                            class="form-control @error('name') is-invalid @enderror"
                                            value="{{ old('name', $user->name) }}" required>
                                        <i class="fas fa-user"></i>
                                    </div>
                                    @error('name')
                                        <div class="small text-danger mt-1">{{ $message }}</div>
                                    @enderror
                                </div>

                                {{-- EMAIL PROFESSIONNEL --}}
                                <div class="col-md-6">
                                    <label for="work_email" class="form-label">Email Professionnel</label>
                                    <div class="input-icon-group">
                                        <input type="email" name="work_email" id="work_email"
                                            class="form-control @error('work_email') is-invalid @enderror"
                                            value="{{ old('work_email', $user->work_email) }}" required>
                                        <i class="fas fa-envelope"></i>
                                    </div>
                                </div>

                                <hr class="my-4 opacity-25">

                                {{-- RÔLE & ACCÈS --}}
                                <div class="col-md-6">
                                    <label for="role_id" class="form-label">Niveau d'Accès (Profil)</label>
                                    <select name="role_id" id="role_id" class="form-select select2" required>
                                        @foreach ($roles as $role)
                                            <option value="{{ $role->id }}"
                                                {{ $user->roles->contains($role->id) ? 'selected' : '' }}>
                                                {{ $role->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- SÉCURITÉ --}}
                                <div class="col-md-6">
                                    <label for="password_first_connection" class="form-label">Modifier le Mot de
                                        Passe</label>
                                    <div class="input-icon-group">
                                        <input type="password" name="password_first_connection"
                                            id="password_first_connection" class="form-control"
                                            placeholder="Laisser vide pour inchangé">
                                        <i class="fas fa-key"></i>
                                    </div>
                                    <small class="text-muted small">Optionnel : réinitialise l'accès de
                                        l'utilisateur.</small>
                                </div>

                                {{-- ACTIONS --}}
                                <div class="col-12 text-center mt-5 border-top pt-4">
                                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary px-4 me-2">
                                        <i class="fas fa-undo me-1"></i> Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                        <i class="fas fa-user-check me-1"></i> Enregistrer les modifications
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
