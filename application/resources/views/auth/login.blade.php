<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Connexion - ManagerPointCNX</title>
    <link rel="icon" href="{{ asset('images/logo/favicon.ico') }}" type="image/x-icon">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-image: url('{{ asset('images/logo/bg.png') }}');
            background-repeat: repeat;
            background-size: 80px 80px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .overlay {
            position: absolute;
            width: 100%;
            height: 100%;
            backdrop-filter: blur(2px);
            background-color: rgba(245, 247, 250, 0.63); /* couche blanche transparente */
            z-index: 0;
        }

        .login-container {
            position: relative;
            z-index: 1;
            width: 100%;
            max-width: 420px;
            padding: 2rem;
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
        }

        .login-container h2 {
            color: #1d4750;
            font-weight: 600;
        }

        .btn-primary {
            background-color: #1d4750;
            border-color: #1d4750;
        }

        .btn-primary:hover {
            background-color: #163840;
            border-color: #163840;
        }

        .form-control {
            border-radius: 0.5rem;
        }

        .form-control:focus {
            border-color: #1d4750;
            box-shadow: 0 0 0 0.2rem rgba(29, 71, 80, 0.2);
        }

        .text-link {
            color: #1d4750;
            text-decoration: none;
        }

        .text-link:hover {
            text-decoration: underline;
        }

        .logo {
            height: 80px;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <div class="overlay"></div>

    <div class="login-container text-center">
        <img src="{{ asset('images/logo/logoMP.png') }}" alt="ManagerPointCNX" class="logo">
        <h2 class="mb-4">Se connecter</h2>

        @if (session('status'))
            <div class="alert alert-success">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="text-start">
            @csrf

            <div class="mb-3">
                <label for="work_email" class="form-label">Adresse e-mail</label>
                <input id="work_email" type="email" 
                       class="form-control @error('work_email') is-invalid @enderror" 
                       name="work_email" value="{{ old('work_email') }}" required autofocus>
                @error('work_email')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3">
                <label for="password" class="form-label">Mot de passe</label>
                <input id="password" type="password" 
                       class="form-control @error('password') is-invalid @enderror" 
                       name="password" required>
                @error('password')
                    <span class="text-danger small">{{ $message }}</span>
                @enderror
            </div>

            <div class="mb-3 d-flex justify-content-between align-items-center">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <a class="small text-link" href="{{ route('password.request') }}">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn btn-primary w-100">Connexion</button>
        </form>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
