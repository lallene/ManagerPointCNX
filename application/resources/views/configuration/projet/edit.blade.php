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

        /* Style Formulaire Audit */
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

        .input-group-text {
            background-color: #f8fafc;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            color: #64748b;
        }
    </style>

    <div class="container-fluid">
        {{-- En-tête --}}
        <div class="row column_title">
            <div class="col-md-12">
                <h2><i class="fas fa-project-diagram me-2"></i> {{ $titre }}</h2>
            </div>
        </div>

        <div class="row justify-content-center mt-4">
            <div class="col-md-10">
                <div class="card shadow-sm">
                    <div class="card-body p-5">
                        <form method="post" action="{{ route($link . '.update', $item->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="row g-4">
                                {{-- SELECTION DU SITE --}}
                                <div class="col-md-6">
                                    <label for="site_id" class="form-label">Site de rattachement</label>
                                    <select name="site_id" class="form-select select2" id="site_id">
                                        @foreach ($foreigns as $foreign)
                                            <option value="{{ $foreign->id }}"
                                                {{ $item->site_id == $foreign->id ? 'selected' : '' }}>
                                                {{ $foreign->designation }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                {{-- DESIGNATION --}}
                                <div class="col-md-6">
                                    <label for="designation" class="form-label">Nom du Projet</label>
                                    <input id="designation" type="text" class="form-control" name="designation"
                                        value="{{ $item->designation }}" required>
                                </div>

                                {{-- DLT MANAGER --}}
                                <div class="col-md-12">
                                    <label for="dltsuperviseur" class="form-label">Email DLT MANAGER</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                        <input id="dltsuperviseur" type="email" class="form-control" name="dltsuperviseur"
                                            value="{{ $item->dltsuperviseur }}" required>
                                    </div>
                                </div>

                                {{-- DESCRIPTION --}}
                                <div class="col-md-12">
                                    <label for="description" class="form-label">Description & Notes</label>
                                    <textarea id="description" class="form-control" rows="5" name="description" placeholder="Détails du projet...">{{ $item->description }}</textarea>
                                </div>

                                {{-- BOUTONS D'ACTION --}}
                                <div class="col-12 text-center mt-5 border-top pt-4">
                                    <a href="{{ route('projet.index') }}" class="btn btn-outline-danger px-4 me-2">
                                        <i class="fa fa-times me-1"></i> Annuler
                                    </a>
                                    <button type="submit" class="btn btn-primary px-5 shadow-sm">
                                        <i class="fa fa-save me-1"></i> Enregistrer les modifications
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
