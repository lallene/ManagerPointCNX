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


        // Vérifier si c'est la première connexion
        if ($user->password_first_connection) {
            // Rediriger vers la page de changement de mot de passe
            return redirect()->route('changePassword');
        }

        // Sinon, rediriger vers la page d'accueil
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
    ]);

    $user = User::find(Auth::user()->id); // Récupère l'utilisateur authentifié

    // Si l'utilisateur existe, vous pouvez utiliser la méthode 'save'
    $user->password = Hash::make($request->password);  // Mise à jour du mot de passe
    $user->save();  // Sauvegarde dans la base de données

    return redirect()->route('home')->with('success', 'Votre mot de passe a été modifié avec succès!');
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
        return 'email';
    }
}
