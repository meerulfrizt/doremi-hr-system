<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Kreait\Firebase\Contract\Auth as FirebaseAuth;

class AuthenticatedSessionController extends Controller
{
    protected $firebaseAuth;

    // Kita panggil Firebase Auth masuk ke dalam Controller ni
    public function __construct(FirebaseAuth $auth)
    {
        $this->firebaseAuth = $auth;
    }

    /**
     * Paparkan muka surat Login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Proses pengesahan (Login) menggunakan Firebase.
     */
    public function store(Request $request): RedirectResponse
    {
        // 1. Pastikan user masukkan email dan password
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        try {
            // 2. Hantar email & password ke Firebase untuk disahkan
            $signInResult = $this->firebaseAuth->signInWithEmailAndPassword($request->email, $request->password);

            // 3. Kalau berjaya, ambil maklumat user (UID, email) dari Firebase
            $firebaseUser = $this->firebaseAuth->getUser($signInResult->firebaseUserId());

            // 4. Simpan maklumat user ni dalam sistem 'Session' Laravel 
            // supaya sistem ingat siapa yang tengah login
            $request->session()->put('firebase_user', [
                'uid' => $firebaseUser->uid,
                'email' => $firebaseUser->email,
                'displayName' => $firebaseUser->displayName ?? 'Admin DOREMi',
            ]);

            $request->session()->regenerate();

            // 5. Bawa user masuk ke Dashboard
            return redirect()->intended(route('dashboard', absolute: false));

        } catch (\Exception $e) {
            // Kalau password salah atau email tak wujud di Firebase
            return back()->withErrors([
                'email' => 'Invalid email address or password. Please check your credentials and try again.',
            ])->onlyInput('email');
        }
    }

    /**
     * Proses Log Keluar (Logout).
     */
    public function destroy(Request $request): RedirectResponse
    {
        // Padam rekod Firebase dari Session komputer
        $request->session()->forget('firebase_user');
        
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}