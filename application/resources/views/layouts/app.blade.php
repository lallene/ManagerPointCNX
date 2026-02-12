<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titre ?? 'ManagerPoint' }}</title>

    <link rel="icon" href="{{ asset('images/logo/favicon.ico') }}" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/nav.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

    @yield('link')
     <style>
                /* 1. Variables Globales & Reset */
        :root {
            --nav-bg: #1a2a2d;        /* Bleu pétrole pro */
            --accent: #198754;        /* Vert émeraude */
            --accent-hover: #157347;
            --text-muted: #a0b0b1;
            --text-dark: #1d4750;
            --bg-light: #f4f7f6;      /* Fond de page gris très clair */
            --white: #ffffff;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', 'Segoe UI', system-ui, sans-serif;
            color: #333;
        }

        /* 2. Barre de Navigation (Topbar) */
        .topbar .navbar {
            background: var(--nav-bg) !important;
            padding: 0.6rem 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .navbar-nav .nav-link {
            color: var(--text-muted) !important;
            font-weight: 600;
            font-size: 1.5rem;
            letter-spacing: 0.5px;
            padding: 0.8rem 1.2rem !important;
            transition: var(--transition);
            border-bottom: 3px solid transparent;
        }

        .navbar-nav .nav-link:hover {
            color: var(--white) !important;
            background: rgba(255, 255, 255, 0.05);
        }

        .navbar-nav .nav-link.active {
            color: var(--white) !important;
            border-bottom: 3px solid var(--accent);
            background: linear-gradient(to bottom, transparent, rgba(25, 135, 84, 0.1));
        }

        /* 3. Menus Déroulants (Dropdowns) */
        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
            background: var(--white);
            border-radius: 10px;
            padding: 0.5rem;
            margin-top: 10px !important;
            animation: fadeIn 0.2s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .dropdown-item {
            color: #444 !important;
            font-weight: 500;
            border-radius: 6px;
            padding: 0.7rem 1.2rem;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: var(--accent) !important;
            color: var(--white) !important;
            transform: translateX(5px);
        }

        /* 4. Titre de Page (Column Title) */
        .column_title {
            background: var(--white);
            padding: 22px 35px;
            margin-top: 110px; /* Espace pour la navbar fixe */
            margin-bottom: 35px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.04);
            border-left: 6px solid var(--accent);
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        .column_title h2 {
            font-size: 1.55rem;
            font-weight: 800;
            color: var(--text-dark);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .breadcrumb-custom {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* 5. Profil Utilisateur & Avatar */
        .user-info-box {
            padding-left: 20px;
            border-left: 1px solid rgba(255, 255, 255, 0.1);
        }

        .user-avatar {
            border: 2px solid var(--accent);
            padding: 2px;
            background: var(--white);
            transition: var(--transition);
            object-fit: cover;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .user-avatar:hover {
            transform: scale(1.1) rotate(3deg);
            border-color: var(--white);
        }

        /* 6. Boutons d'Action Table (Optionnel mais recommandé) */
        .btn-action {
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 0.85rem;
            font-weight: 600;
            transition: var(--transition);
        }
     </style>
</head>
<body class="dashboard dashboard_1">
    <div class="full_container">
        <div class="inner_container">
            <!-- Sidebar  -->


               - <!-- partial:index.partial.html -->
               <header>


               </header>
               <!-- partial -->

            <!-- end sidebar -->
            <!-- right content -->
            <div id="content">
                <!-- topbar -->
               <div class="topbar">
    <nav class="navbar navbar-expand-lg navbar-dark shadow-lg">
        <div class="container-fluid">
            <a class="navbar-brand fw-bold text-uppercase" href="{{ route('home') }}" style="letter-spacing: 1px;">
                <span class="text-success">Manager</span>Point
            </a>

            <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    
                    @hasanyrole('IT|Ressources Humaines')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->routeIs('projet.*') ? 'active' : '' }}" href="{{ route('projet.index') }}">
                            Projets
                        </a>
                    </li>
                    @endhasanyrole

                    @hasanyrole('IT|Ressources Humaines|Responsables d’équipe')
                     <li class="nav-item">
                        <a class="nav-link {{ request()->is('effectifs*') ? 'active' : '' }}" href="{{ route('effectifs') }}">
                            Effectifs
                        </a>
                    </li>
                    @endhasanyrole
                    @role('Responsables d’équipe')
                    <li class="nav-item">
                        <a class="nav-link {{ request()->is('planification*') ? 'active' : '' }}" href="{{ route('planification') }}">
                            Planification
                        </a>
                    </li>
                    @endrole

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('planning*') ? 'active' : '' }}" href="#" id="dropPlan" data-bs-toggle="dropdown">
                            Plannings
                        </a>
                        <ul class="dropdown-menu">
                            @role('Manager')
                            <li><a class="dropdown-item" href="{{ route('planning.group.journee') }}">Journalier Équipe</a></li>
                            @endrole
                            <li><a class="dropdown-item" href="{{ route('planning.group') }}">Hebdomadaire Équipe</a></li>
                        </ul>
                    </li>

                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('pointages*') ? 'active' : '' }}" href="#" id="dropSuivi" data-bs-toggle="dropdown">
                            Présence
                        </a>
                        <ul class="dropdown-menu">
                            @role('Manager')
                            <li><a class="dropdown-item" href="{{ route('pointages.create') }}">Suivi Journalier</a></li>
                            @endrole
                            <li><a class="dropdown-item" href="{{ route('pointages.global') }}">Vue Globale</a></li>
                        </ul>
                    </li>

                    @role('IT')
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle {{ request()->is('profil*') || request()->is('permission*') ? 'active' : '' }}" href="#" data-bs-toggle="dropdown">
                            Administration
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="{{ route('profil.index') }}">Profils & Rôles</a></li>
                            <li><a class="dropdown-item" href="{{ route('permission.index') }}">Permissions</a></li>
                            <li><a class="dropdown-item" href="{{ route('users.index') }}">Utilisateurs</a></li>
                        </ul>
                    </li>
                    @endrole
                </ul>

                <ul class="navbar-nav ms-auto user-info-box">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#" data-bs-toggle="dropdown">
                            <div class="text-end me-3 d-none d-sm-block">
                                <div class=" fw-bold small" style="font-size: 0.85rem;">{{ Auth::user()->name }}</div>
                                <div class="text-success small" style="font-size: 0.7rem; font-weight: 600;">{{ Auth::user()->roles->pluck('name')->first() }}</div>
                            </div>
                            <img src="{{ asset('images/layout_img/user_img.jpg') }}" class="user-avatar rounded-circle" width="40" height="40">
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item text-danger" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                <i class="fas fa-power-off me-2"></i> Déconnexion
                            </a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</div>
                <!-- end topbar -->
                <!-- dashboard inner -->
                <div class="midde_cont">
                    @include('message')
                    @yield('content')
                    <!-- footer -->
                    <div class="container-fluid">
                        <div class="footer">
                            <p>Copyright © 2025 Designed by <a target="_blank" href="#">Lallène Cedric ACHI</a></a>. Tous droits réservés.<br><br>
                            </p>
                        </div>
                    </div>
                </div>
                <!-- end dashboard inner -->
            </div>
        </div>
    </div>

<form action="{{ route('logout') }}" method="POST" id="logout-form">
    @csrf
</form>
<script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.1.3/jszip.min.js"></script>
<script src="https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/axios/0.27.2/axios.min.js"></script>
<script src="{{ asset('assets/js/custom.js') }}"></script>

@stack('scripts')



</body>


</html>
