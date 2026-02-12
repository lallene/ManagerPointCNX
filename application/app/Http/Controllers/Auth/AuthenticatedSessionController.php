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
        // Validation des informations de connexion
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        // Tenter la connexion
        if (Auth::attempt($request->only('email', 'password'))) {
            $user = Auth::user();

            // Si c'est la première connexion de l'utilisateur
            if ($user->password_first_connection) {
                // Rediriger vers la page de modification du mot de passe
                return redirect()->route('changePassword');
            }

            // Sinon, rediriger vers la page d'accueil ou tableau de bord
            return redirect()->intended(RouteServiceProvider::HOME);
        }

        // Si la connexion échoue, retourner avec un message d'erreur
        return back()->withErrors([
            'email' => 'Les informations d\'identification fournies sont incorrectes.',
        ]);
    }
}
