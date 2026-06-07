<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Http;

abstract class Controller
{
    protected function getFirestoreAccessToken()
    {
        return cache()->remember('firestore_access_token', 3000, function () {
            $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT_JSON') ?: env('FIREBASE_CREDENTIALS');
            if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
                // Attempt to resolve relative to base_path if it's not absolute
                $serviceAccountPath = base_path($serviceAccountPath);
                if (!file_exists($serviceAccountPath)) {
                    throw new \Exception('Service account JSON file not found at: ' . $serviceAccountPath);
                }
            }

            $credentials = json_decode(file_get_contents($serviceAccountPath), true);
            
            $header = json_encode(['alg' => 'RS256', 'typ' => 'JWT']);
            $now = time();
            $payload = json_encode([
                'iss' => $credentials['client_email'],
                'scope' => 'https://www.googleapis.com/auth/datastore',
                'aud' => 'https://oauth2.googleapis.com/token',
                'exp' => $now + 3600,
                'iat' => $now
            ]);

            $base64UrlHeader = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($header));
            $base64UrlPayload = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($payload));

            $signature = '';
            if (!openssl_sign($base64UrlHeader . "." . $base64UrlPayload, $signature, $credentials['private_key'], 'sha256WithRSAEncryption')) {
                throw new \Exception('Failed to sign JWT token.');
            }

            $base64UrlSignature = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($signature));
            $jwt = $base64UrlHeader . "." . $base64UrlPayload . "." . $base64UrlSignature;

            $tokenResponse = Http::timeout(15)->asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt
            ]);

            if (!$tokenResponse->successful()) {
                throw new \Exception('Failed to exchange JWT for access token: ' . $tokenResponse->body());
            }

            return $tokenResponse->json('access_token');
        });
    }
}
