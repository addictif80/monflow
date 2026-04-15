<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\{User, Wallet};
use App\Services\{NavidromeService, EmailService};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth, Hash, Password, Log};
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) return redirect(Auth::user()->is_admin ? '/admin' : '/portal');
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $creds = $request->validate(['username' => 'required', 'password' => 'required']);
        $user = User::where('username', $creds['username'])->orWhere('email', $creds['username'])->first();

        if (!$user || !Hash::check($creds['password'], $user->password)) {
            return back()->withErrors(['username' => 'Identifiants incorrects.'])->withInput();
        }
        if ($user->status === 'suspended') return back()->withErrors(['username' => 'Compte suspendu. Régularisez votre paiement.']);
        if ($user->status === 'deleted') return back()->withErrors(['username' => 'Ce compte a été supprimé.']);

        Auth::login($user, $request->boolean('remember'));
        return redirect($user->is_admin ? '/admin' : '/portal');
    }

    public function showRegister() { return view('auth.register'); }

    public function register(Request $request, NavidromeService $nd, EmailService $mail)
    {
        $data = $request->validate([
            'username' => 'required|unique:users|min:3|max:50',
            'email' => 'required|email|unique:users',
            'first_name' => 'nullable|max:100',
            'last_name' => 'nullable|max:100',
            'password' => 'required|min:6|confirmed',
        ]);

        $user = User::create([
            'username' => $data['username'],
            'email' => $data['email'],
            'first_name' => $data['first_name'] ?? '',
            'last_name' => $data['last_name'] ?? '',
            'password' => Hash::make($data['password']),
        ]);
        $user->storeEncryptedPassword($data['password']);
        Wallet::create(['user_id' => $user->id]);

        try {
            $nd_user = $nd->createUser($user->username, $data['password'], $user->full_name, $user->email);
            $user->update(['navidrome_id' => $nd_user['id'] ?? null]);
            // Suspendre immédiatement Navidrome : l'accès musique nécessite un abonnement actif
            if ($user->navidrome_id) {
                $nd->suspendUser($user->navidrome_id);
            }
        } catch (\Exception $e) { Log::error("Navidrome create failed: {$e->getMessage()}"); }

        try { $mail->sendWelcome($user); } catch (\Exception $e) {}

        Auth::login($user);
        return redirect('/portal')->with('success', 'Bienvenue ! Compte créé avec succès.');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/login');
    }

    public function showForgotPassword() { return view('auth.forgot-password'); }

    public function forgotPassword(Request $request, EmailService $mail)
    {
        $request->validate(['email' => 'required|email']);
        $user = User::where('email', $request->email)->first();
        if ($user) {
            $token = Str::random(64);
            \DB::table('password_reset_tokens')->updateOrInsert(
                ['email' => $user->email],
                ['token' => Hash::make($token), 'created_at' => now()]
            );
            $url = url("/reset-password/{$token}?email={$user->email}");
            $mail->sendPasswordReset($user, $url);
        }
        return back()->with('success', 'Si un compte existe, un lien a été envoyé.');
    }

    public function showResetPassword(string $token) { return view('auth.reset-password', ['token' => $token, 'email' => request('email')]); }

    public function resetPassword(Request $request, NavidromeService $nd)
    {
        $data = $request->validate([
            'token' => 'required', 'email' => 'required|email',
            'password' => 'required|min:6|confirmed',
        ]);
        $record = \DB::table('password_reset_tokens')->where('email', $data['email'])->first();
        if (!$record || !Hash::check($data['token'], $record->token)) {
            return back()->withErrors(['token' => 'Lien invalide ou expiré.']);
        }
        $user = User::where('email', $data['email'])->firstOrFail();
        $user->update(['password' => Hash::make($data['password'])]);
        $user->storeEncryptedPassword($data['password']);
        if ($user->navidrome_id) {
            try { $nd->changePassword($user->navidrome_id, $data['password']); } catch (\Exception $e) {}
        }
        \DB::table('password_reset_tokens')->where('email', $data['email'])->delete();
        return redirect('/login')->with('success', 'Mot de passe réinitialisé.');
    }
}
