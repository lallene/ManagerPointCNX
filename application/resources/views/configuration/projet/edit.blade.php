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
                        <form method="post" class="row" action="{{ route($link.'.update', $item->id) }}" enctype="multipart/form-data">
                            @csrf
                            @method('PUT')

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="site_id">Site</label>
                                    <select name="site_id" class="form-control select2" id="site_id">
                                        <?php
                                            foreach ($foreigns as $foreign) {
                                                ?>
                                                <option <?= ($item->site_id == $foreign->id) ? "selected" : "" ?> value="{{ $foreign->id }}">{{ $foreign->designation }}</option>
                                                <?php
                                                }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="designation">Désignation</label>
                                    <input id="designation" type="text" class="form-control" required name="designation" value="{{ $item->designation }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="dltsuperviseur">DLT MANAGER</label>
                                    <input id="dltsuperviseur" type="email" class="form-control" required name="dltsuperviseur" value="{{ $item->dltsuperviseur }}">
                                </div>
                            </div>
                            <div class="col-md-12">
                                <div class="form-group">
                                    <label for="description">Description</label>
                                    <textarea id="description" class="form-control" cols="30" rows="10" name="description">{{ $item->description }}</textarea>
                                </div>
                            </div>

                            <div class="col-sm-12 text-center">
                                <a href="{{ route("projet.index") }}" class="btn btn-danger"><i class="fa fa-close"></i> Annuler</a>
                                <button class="btn btn-primary"><i class="fa fa-save"></i> Modifier</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@stop
