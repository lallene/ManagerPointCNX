<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use App\Mail\ResetPasswordMail;

class ForgotPasswordController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.passwords.email');
    }

    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'work_email' => 'required|email'
        ]);

        // Récupère l'user via work_email
        $user = User::where('work_email', $request->work_email)->first();

        if (!$user) {
            return back()->withErrors([
                'work_email' => 'Cet email n\'existe pas dans notre base.'
            ]);
        }

        // Créer un token manuellement
        $token = Str::random(64);

        DB::table('password_resets')->updateOrInsert(
            ['work_email' => $request->work_email],
            [
                'token' => Hash::make($token),
                'created_at' => now()
            ]
        );

        // Envoyer l'email avec le token
        Mail::to($request->work_email)->send(new ResetPasswordMail($token));

        return back()->with('status', 'Lien de réinitialisation envoyé sur votre email.');
    }
}