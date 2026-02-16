<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $titre ?? 'ManagerPoint' }}</title>

    <link rel="icon" href="{{ asset('images/logo/favicon.ico') }}" type="image/x-icon">

    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&display=swap" rel="stylesheet">

    <link href="{{ asset('assets/bootstrap/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap5.min.css">
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />

    <link rel="stylesheet" href="{{ asset('assets/style.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/responsive.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/nav.css') }}">
    <link rel="stylesheet" href="{{ asset('assets/css/custom.css') }}">

    @yield('link')

    <style>
        :root {
            --nav-bg: #1a2a2d;
            --accent: #198754;
            --accent-hover: #157347;
            --text-muted: #a0b0b1;
            --text-dark: #1d4750;
            --bg-light: #f4f7f6;
            --white: #ffffff;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }

        body {
            background-color: var(--bg-light);
            font-family: 'Inter', sans-serif;
            padding-top: 80px;
            /* Évite que le contenu soit caché sous la navbar fixe */
        }

        .navbar {
            background: #003d5b !important;
            padding: 0.5rem 1.5rem;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.007);
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1050;
        }

        .navbar-brand {
            font-size: 1.25rem;
            letter-spacing: 1px;
        }

        .navbar-nav .nav-link {
            color: var(--text-muted) !important;
            font-weight: 600;
            font-size: 1.65rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 1.2rem 1rem !important;
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
            background: linear-gradient(to bottom, transparent, rgba(25, 135, 84, 0.15));
        }

        .dropdown-menu {
            border: none;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            border-radius: 10px;
            padding: 0.5rem;
        }

        .dropdown-item {
            font-size: 0.9rem;
            padding: 0.6rem 1rem;
            border-radius: 6px;
            transition: var(--transition);
        }

        .dropdown-item:hover {
            background: var(--accent) !important;
            color: white !important;
            padding-left: 1.5rem;
        }

        .user-info-box {
            border-left: 1px solid rgba(255, 255, 255, 0.1);
            padding-left: 1.5rem;
        }

        .user-avatar {
            border: 2px solid var(--accent);
            transition: var(--transition);
            object-fit: cover;
        }

        .role-badge {
            font-size: 0.65rem;
            text-transform: uppercase;
            font-weight: 700;
            color: var(--accent);
        }

        .column_title {
            background: var(--white);
            padding: 1.5rem 2rem;
            /* Aligné sur les bords du container */
            border-bottom: 1px solid #e9ecef;
            display: flex;
            align-items: center;
            justify-content: space-between;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.02);
        }

        .column_title h1,
        .column_title h2,
        .column_title h3 {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--nav-bg);
            /* Ton bleu foncé #003d5b */
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
        }

        /* Petit indicateur de couleur à gauche du titre */
        .column_title h2::before {
            content: "";
            width: 4px;
            height: 20px;
            background: var(--accent);
            /* Ton vert #198754 */
            display: inline-block;
            margin-right: 12px;
            border-radius: 2px;
        }

        /* Style pour les boutons d'action souvent présents dans les titres (ex: Ajouter) */
        .column_title .btn {
            padding: 0.5rem 1.2rem;
            font-weight: 600;
            font-size: 0.85rem;
            border-radius: 6px;
            box-shadow: 0 2px 5px rgba(25, 135, 84, 0.2);
        }
    </style>
</head>

<body class="dashboard dashboard_1">
    <div class="full_container">
        <div class="inner_container">

            <nav class="navbar navbar-expand-lg navbar-dark">
                <div class="container-fluid">
                    <a class="navbar-brand fw-bold text-uppercase" href="{{ route('home') }}">
                        <span class="text-white">Manager</span><span class="text-success">Point</span>
                    </a>

                    <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse"
                        data-bs-target="#navbarNav">
                        <span class="navbar-toggler-icon"></span>
                    </button>

                    <div class="collapse navbar-collapse" id="navbarNav">
                        <ul class="navbar-nav mx-auto">
                            @hasanyrole('IT|Ressources Humaines')
                                <li class="nav-item"><a class="nav-link @routeActive('projet.*')"
                                        href="{{ route('projet.index') }}">Projets</a></li>
                            @endhasanyrole

                            @hasanyrole('IT|Ressources Humaines|Responsables d’équipe')
                                <li class="nav-item"><a class="nav-link @routeActive('effectifs')"
                                        href="{{ route('effectifs') }}">Effectifs</a></li>
                            @endhasanyrole

                            @role('Responsables d’équipe')
                                <li class="nav-item"><a class="nav-link @routeActive('planification')"
                                        href="{{ route('planification') }}">Planification</a></li>
                            @endrole

                            <li class="nav-item"><a class="nav-link @routeActive('planning.group')"
                                    href="{{ route('planning.group') }}">Plannings</a></li>

                            @role('Manager')
                                @unlessrole('IT')
                                    <li class="nav-item"><a class="nav-link @routeActive('pointages.create')"
                                            href="{{ route('pointages.create') }}">Suivi</a></li>
                                @endunlessrole
                            @endrole

                            <li class="nav-item"><a class="nav-link @routeActive('pointages.index')"
                                    href="{{ route('pointages.index') }}">Pointages</a></li>

                            @role('IT')
                                <li class="nav-item dropdown">
                                    <a class="nav-link dropdown-toggle @routeActive('profil.*|permission.*|users.*')" href="#"
                                        data-bs-toggle="dropdown">Administration</a>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="{{ route('profil.index') }}"><i
                                                    class="fas fa-user-shield me-2"></i> Profils</a></li>
                                        <li><a class="dropdown-item" href="{{ route('permission.index') }}"><i
                                                    class="fas fa-key me-2"></i> Permissions</a></li>
                                        <li><a class="dropdown-item" href="{{ route('users.index') }}"><i
                                                    class="fas fa-users-cog me-2"></i> Utilisateurs</a></li>
                                    </ul>
                                </li>
                            @endrole
                        </ul>

                        <ul class="navbar-nav ms-auto user-info-box">
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle d-flex align-items-center p-0" href="#"
                                    data-bs-toggle="dropdown">
                                    <div class="text-end me-3 d-none d-sm-block">
                                        <div class="text-white fw-bold" style="font-size: 0.85rem; line-height: 1.2;">
                                            {{ Auth::user()->name }}</div>
                                        <div class="role-badge">{{ Auth::user()->roles->pluck('name')->first() }}</div>
                                    </div>
                                    <img src="{{ asset('images/layout_img/user_img.jpg') }}"
                                        class="user-avatar rounded-circle" width="40" height="40">
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end shadow">
                                    <li>
                                        <a class="dropdown-item text-danger fw-bold" href="{{ route('logout') }}"
                                            onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                            <i class="fas fa-power-off me-2"></i> Déconnexion
                                        </a>
                                    </li>
                                </ul>
                            </li>
                        </ul>
                    </div>
                </div>
            </nav>

            <div id="content">
                <div class="midde_cont">
                    @include('message')
                    <div class="p-3">
                        @yield('content')
                    </div>

                    <div class="container-fluid">
                        <div class="footer text-center mt-5">
                            <p class="small text-muted">Copyright © 2025 Designed by <span
                                    class="text-dark fw-bold">Lallène Cedric ACHI</span>. Tous droits réservés.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <form action="{{ route('logout') }}" method="POST" id="logout-form" class="d-none">
        @csrf
    </form>

    <script src="https://code.jquery.com/jquery-3.6.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap5.min.js"></script>

    <script src="{{ asset('assets/js/custom.js') }}"></script>

    @stack('scripts')
</body>

</html>
