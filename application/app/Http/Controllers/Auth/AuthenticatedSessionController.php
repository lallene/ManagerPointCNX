<?php

namespace App\Http\Controllers\Auth;



use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;


class AuthenticatedSessionController extends Controller
{
    public function store(Request $request)
{
    // 1. Validation : Utilise 'email' pour la syntaxe
    $request->validate([
        'work_email' => 'required|email', 
        'password' => 'required',
    ]);

    // 2. Tentative de connexion
    // On utilise les credentials basés sur work_email
    $credentials = $request->only('work_email', 'password');

    if (Auth::attempt($credentials)) {
        $user = Auth::user();

        // 3. Logique de première connexion
        if ($user->password_first_connection) {
            // S'assurer que la route 'changePassword' est bien celle définie dans web.php
            return redirect()->route('changePassword');
        }

        // 4. Redirection vers HOME (Laravel 11 utilise souvent une chaîne directe ou une constante)
        return redirect()->intended('/home'); 
    }

    // 5. Échec : Retour avec erreur et input pour ne pas retaper l'email
    return back()->withErrors([
        'work_email' => 'Les informations d\'identification fournies sont incorrectes.',
    ])->withInput($request->only('work_email'));
}
}
