<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class FirebaseAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Kalau takde 'firebase_user' dalam session, tendang balik ke login
        if (!$request->session()->has('firebase_user')) {
            return redirect()->route('login')->withErrors([
                'email' => 'Please log in to access this page.'
            ]);
        }

        // Kalau ada, benarkan masuk
        return $next($request);
    }
}