@extends('layouts.app')

@section('content')
    <div class="container-fluid">

        <div class="row column_title">
            <div class="col-md-12">
                <div class="page_title">
                    <h2>{{ $titre }}</h2>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="offset-md-3 col-md-6">
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="{{ route('register') }}">
                            @csrf

                            <div class="row mb-3">
                                <label for="name" class="col-md-2 pr-0 col-form-label text-md-end">Nom & Prénoms</label>

                                <div class="col-md-10">
                                    <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autocomplete="name" autofocus>

                                    @error('name')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="row mb-3">
                                <label for="email" class="col-md-2 pr-0 col-form-label text-md-end">Adresse Email</label>

                                <div class="col-md-10">
                                    <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autocomplete="email">

                                    @error('email')
                                        <span class="invalid-feedback" role="alert">
                                            <strong>{{ $message }}</strong>
                                        </span>
                                    @enderror
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="password_first_connection" class="form-label">Mot de passe (première connexion) :</label>
                                <input type="password" id="password_first_connection" name="password_first_connection" class="form-control" required>
                            </div>


                            <div class="row mb-3">
                                <label for="role_id" class="col-md-2 col-form-label text-md-end pr-0">Profil</label>
                                <div class="col-md-10">
                                    <select name="role_id" class="form-control select2" id="role_id">
                                        <?php
                                            foreach ($roles as $role) {
                                                ?>
                                                <option value="{{ $role->id }}">{{ $role->name }}</option>
                                                <?php
                                            }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mt-3">
                                <div class="col-sm-12 text-center">
                                    <a href="{{ route("home") }}" class="btn btn-danger"><i class="fa fa-close"></i> Annuler</a>
                                    <button class="btn btn-primary"><i class="fa fa-save"></i> Ajouter</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
