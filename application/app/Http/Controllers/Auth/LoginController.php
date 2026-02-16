<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use Illuminate\Validation\ValidationException;


class LoginController extends Controller
{
    use \Illuminate\Foundation\Auth\AuthenticatesUsers;

    /**
     * Affiche le formulaire de connexion.
     *
     * @return \Illuminate\View\View
     */
    public function showLoginForm()
    {
        return view('auth.login');
    }

    /**
     * Gère la demande de connexion de l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     */
    public function login(Request $request)
    {
        $this->validateLogin($request);

        // Essayer de se connecter
        if ($this->attemptLogin($request)) {
            return $this->sendLoginResponse($request);
        }

        // Si l'échec de la connexion
        $this->incrementLoginAttempts($request);
        return $this->sendFailedLoginResponse($request);
    }

    /**
     * L'utilisateur a été authentifié avec succès.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  mixed  $user
     * @return \Illuminate\Http\RedirectResponse
     */
     protected function authenticated(Request $request, $user)
    {
        // Si le flag est à true (1), redirection forcée
        if ($user->password_first_connection) {
            return redirect()->route('changePassword');
        }

        return redirect()->intended($this->redirectPath());
    }

    /**
     * Affiche le formulaire de changement de mot de passe.
     *
     * @return \Illuminate\View\View
     */
    public function showChangePasswordForm()
    {
        return view('auth.change-password');
    }

    /**
     * Met à jour le mot de passe de l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse
     */

    public function updatePassword(Request $request)
    {
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ], [
            'password.confirmed' => 'La confirmation du mot de passe ne correspond pas.',
            'password.min' => 'Le mot de passe doit contenir au moins 8 caractères.'
        ]);

        // Récupération directe de l'objet utilisateur authentifié
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Mise à jour du mot de passe ET remise à zéro du flag de première connexion
        $user->update([
            'password' => Hash::make($request->password),
            'password_first_connection' => false, // Important !
        ]);

        return redirect()->route('home')->with('success', 'Votre mot de passe a été modifié avec succès !');
    }

    /**
     * Déconnexion de l'utilisateur.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function logout(Request $request)
    {
        $this->guard()->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }

    /**
     * Le nom d'utilisateur pour la connexion.
     *
     * @return string
     */
    public function username()
    {
        return 'work_email';
    }
}
