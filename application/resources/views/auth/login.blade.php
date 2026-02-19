<!DOCTYPE html>
<html lang="fr">

<head>
    <meta charset="UTF-8">
    <title>Connexion - ManagerPointCNX</title>
    <link rel="icon" href="{{ asset('images/logo/favicon.ico') }}" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <style>
        :root {
            --cnx-teal: #1d4750;
            --cnx-teal-dark: #163840;
            --cnx-accent: #00d1ff;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            background: url('{{ asset('images/logo/bg.png') }}') center/80px 80px repeat;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
        }

        .overlay {
            position: absolute;
            inset: 0;
            backdrop-filter: blur(4px);
            background: radial-gradient(circle, rgba(255, 255, 255, 0.7) 0%, rgba(245, 247, 250, 0.9) 100%);
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 440px;
            padding: 2.5rem;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 1.5rem;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.3);
            transform: translateY(20px);
            opacity: 0;
            animation: slideUp 0.6s cubic-bezier(0.23, 1, 0.32, 1) forwards;
        }

        @keyframes slideUp {
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }

        .logo-wrapper {
            margin-bottom: 1.5rem;
            transition: transform 0.3s ease;
        }

        .logo-wrapper:hover {
            transform: scale(1.05);
        }

        .logo {
            height: 75px;
            object-fit: contain;
        }

        .form-label {
            font-size: 0.85rem;
            font-weight: 600;
            color: var(--cnx-teal);
            margin-bottom: 0.5rem;
        }

        .input-group-text {
            background-color: transparent;
            border-right: none;
            color: #adb5bd;
        }

        .form-control {
            border-left: none;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: #dee2e6;
            box-shadow: none;
            background-color: #f8f9fa;
        }

        .input-group:focus-within .input-group-text,
        .input-group:focus-within .form-control {
            border-color: var(--cnx-teal);
            color: var(--cnx-teal);
        }

        .btn-primary {
            background: var(--cnx-teal);
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-primary:hover {
            background: var(--cnx-teal-dark);
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(29, 71, 80, 0.3);
        }

        .btn-primary:active {
            transform: translateY(0);
        }

        /* Style pour l'erreur de domaine */
        #domain-error {
            display: none;
            font-size: 0.75rem;
            margin-top: 0.25rem;
            color: #dc3545;
        }

        body {
            font-family: 'Inter', 'Segoe UI', sans-serif;
            /* Remplacer l'URL par une image haute définition type Unsplash ou une ressource interne */
            background: url('https://images.unsplash.com/photo-1497366216548-37526070297c?auto=format&fit=crop&q=80&w=2070') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            position: absolute;
            inset: 0;
            /* On augmente légèrement le flou pour que l'image ne fatigue pas l'œil */
            backdrop-filter: blur(8px);
            background: radial-gradient(circle, rgba(29, 71, 80, 0.4) 0%, rgba(255, 255, 255, 0.2) 100%);
            z-index: 0;
        }
    </style>
</head>

<body>
    <div class="overlay"></div>

    <div class="login-container">
        <div class="text-center logo-wrapper">
            <img src="{{ asset('images/logo/logoMP.png') }}" alt="ManagerPointCNX" class="logo">
        </div>

        <h3 class="text-center mb-1 fw-bold" style="color: var(--cnx-teal);">Bon retour !</h3>
        <p class="text-center text-muted small mb-4">Accédez à votre tableau de bord Concentrix</p>

        @if (session('status'))
            <div class="alert alert-success py-2 small border-0 shadow-sm mb-4">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" id="loginForm">
            @csrf

            <div class="mb-3">
                <label for="work_email" class="form-label">Email professionnel</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-regular fa-envelope"></i></span>
                    <input id="work_email" type="email" class="form-control @error('work_email') is-invalid @enderror"
                        name="work_email" value="{{ old('work_email') }}" placeholder="nom@concentrix.com"
                        pattern=".+@concentrix\.com" title="Veuillez utiliser votre adresse @concentrix.com" required
                        autofocus>
                </div>
                <div id="domain-error"><i class="fa-solid fa-circle-exclamation me-1"></i>Seuls les emails
                    @concentrix.com sont autorisés.</div>
                @error('work_email')
                    <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <div class="input-group">
                    <span class="input-group-text"><i class="fa-solid fa-lock"></i></span>
                    <input id="password" type="password" class="form-control @error('password') is-invalid @enderror"
                        name="password" placeholder="••••••••" required>
                    <button class="btn btn-outline-secondary border-start-0" type="button" id="togglePassword"
                        style="border-color: #dee2e6;">
                        <i class="fa-regular fa-eye" id="eyeIcon"></i>
                    </button>
                </div>
                @error('password')
                    <span class="text-danger small mt-1 d-block">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-4 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember">
                    <label class="form-check-label small text-muted" for="remember">Rester connecté</label>
                </div>
                <a class="small text-link fw-semibold" href="#"
                    style="color: var(--cnx-teal); text-decoration: none;">Oublié ?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100 mb-3" id="submitBtn">
                <span id="btnText">Se connecter</span>
                <span id="btnLoader" class="spinner-border spinner-border-sm d-none" role="status"></span>
            </button>
        </form>

        <p class="text-center text-muted" style="font-size: 0.7rem;">&copy; {{ date('Y') }} Concentrix Plus. Tous
            droits réservés.</p>
    </div>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            const $emailInput = $('#work_email');
            const $errorMsg = $('#domain-error');
            const $form = $('#loginForm');

            // 1. Validation du domaine en temps réel
            $emailInput.on('input', function() {
                const email = $(this).val();
                if (email && !email.toLowerCase().endsWith('@concentrix.com')) {
                    $errorMsg.fadeIn();
                    $(this).addClass('is-invalid');
                } else {
                    $errorMsg.fadeOut();
                    $(this).removeClass('is-invalid');
                }
            });

            // 2. Toggle visibilité mot de passe
            $('#togglePassword').on('click', function() {
                const passInput = $('#password');
                const type = passInput.attr('type') === 'password' ? 'text' : 'password';
                passInput.attr('type', type);
                $('#eyeIcon').toggleClass('fa-eye fa-eye-slash');
            });

            // 3. Animation du bouton au submit
            $form.on('submit', function(e) {
                const email = $emailInput.val().toLowerCase();
                if (!email.endsWith('@concentrix.com')) {
                    e.preventDefault();
                    $errorMsg.shake(); // Optionnel : petit effet de vibration
                    return false;
                }

                $('#btnText').addClass('d-none');
                $('#btnLoader').removeClass('d-none');
                $('#submitBtn').attr('disabled', true);
            });
        });
    </script>
</body>

</html>
