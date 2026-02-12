<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    {{-- Une seule balise viewport suffit --}}
    <meta name="viewport" content="width=device-width, initial-scale=1">
    
    {{-- CSRF Token indispensable pour vos formulaires et scripts --}}
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ config('app.name', 'ManagerPointCnx') }}</title>

    {{-- Fonts --}}
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">
    
    {{-- Font Awesome (Essentiel pour vos icônes de planning et d'action) --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">

    {{-- Styles --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/meyer-reset/2.0/reset.min.css">
    <link href="{{ asset('css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('css/nav.css') }}">
    
    {{-- Emplacement pour les styles spécifiques (DataTables, Planning, etc.) --}}
    @stack('styles')
</head>

<body>
    <div id="app">
        {{-- Inclusion de votre barre de navigation --}}
        @include('layouts.nav') 

        <main class="py-4">
            @yield('content')
        </main>
    </div>

    {{-- Scripts de base --}}
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.0/jquery.min.js"></script>
    <script src="{{ asset('js/app.js') }}" defer></script>
    <script src="{{ asset('assets/js/nav.js') }}"></script>

    {{-- Emplacement pour les scripts spécifiques aux pages (DataTables, Planning) --}}
    @stack('scripts')
</body>
</html>