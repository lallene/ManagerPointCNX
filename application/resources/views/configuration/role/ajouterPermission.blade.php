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
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <form method="post" class="row" action="{{ URL::to('profil/grantPermission/'.$role->id) }}" enctype="multipart/form-data">
                            @csrf

                            <?php
                                foreach ($permissions as $permission) {
                                    ?>
                                    <div class="col-md-3">
                                        <div class="form-group row">
                                            <div class="offset-sm-1 col-sm-11">
                                                <div class="custom-control custom-checkbox mr-sm-2">
                                                    <input type="checkbox" name="role_{{ $permission->id }}" class="custom-control-input" id="role_{{ $permission->id }}" {{ $permission->Checked }}>
                                                    <label class="custom-control-label" for="role_{{ $permission->id }}">{{ $permission->name }}</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php
                                }
                            ?>

                            <div class="col-sm-12 text-center">
                                <a href="{{ route("home") }}" class="btn btn-danger"><i class="fa fa-close"></i> Annuler</a>
                                <button class="btn btn-primary"><i class="fa fa-save"></i> Ajouter</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop