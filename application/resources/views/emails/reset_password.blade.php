<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Réinitialisation mot de passe</title>
</head>
<body>
    <p>Bonjour,</p>
    <p>Cliquez sur le lien ci-dessous pour réinitialiser votre mot de passe :</p>
    <p>
        <a href="{{ url('reset-password?token=' . $token) }}">
            Réinitialiser mon mot de passe
        </a>
    </p>
    <p>Si vous n’avez pas demandé ce changement, ignorez cet email.</p>
</body>
</html>