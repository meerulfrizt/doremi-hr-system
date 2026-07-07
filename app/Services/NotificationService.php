<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    private $projectId;

    public function __construct()
    {
        $this->projectId = env('FIRESTORE_PROJECT_ID', 'doremi-admin2');
    }

    /**
     * Send a notification by writing to Firestore 'notifications' collection.
     *
     * @param string $uid Target staff UID
     * @param string $type "ot_approved" | "ot_rejected" | "leave_approved" | "leave_rejected" | "flexi_approved" | "flexi_rejected"
     * @param array $data Placeholders values + request_id
     * @return void
     */
    public function send(string $uid, string $type, array $data): void
    {
        $templates = [
            'ot_approved' => [
                'title' => 'OT Request Approved',
                'message' => 'Your OT request for {date} ({hours}h) has been approved.',
            ],
            'ot_rejected' => [
                'title' => 'OT Request Rejected',
                'message' => 'Your OT request for {date} has been rejected.',
            ],
            'leave_approved' => [
                'title' => 'Leave Request Approved',
                'message' => 'Your {leave_type} leave for {start_date} has been approved.',
            ],
            'leave_rejected' => [
                'title' => 'Leave Request Rejected',
                'message' => 'Your {leave_type} leave for {start_date} has been rejected.',
            ],
            'flexi_approved' => [
                'title' => 'Flexi Request Approved',
                'message' => 'Your Flexi-Hours request for {date} has been approved.',
            ],
            'flexi_rejected' => [
                'title' => 'Flexi Request Rejected',
                'message' => 'Your Flexi-Hours request for {date} has been rejected.',
            ],
        ];

        if (!isset($templates[$type])) {
            Log::warning("Notification type '{$type}' is not recognized.");
            return;
        }

        $template = $templates[$type];
        $title = $template['title'];
        $message = $template['message'];

        // Replace placeholders in the template message
        foreach ($data as $key => $value) {
            $message = str_replace('{' . $key . '}', (string)$value, $message);
        }

        try {
            $accessToken = $this->getFirestoreAccessToken();
            $url = "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents/notifications";

            $payload = [
                'fields' => [
                    'uid' => ['stringValue' => $uid],
                    'title' => ['stringValue' => $title],
                    'message' => ['stringValue' => $message],
                    'type' => ['stringValue' => $type],
                    'status' => ['stringValue' => 'unread'],
                    'created_at' => ['timestampValue' => now()->toIso8601ZuluString()],
                    'request_id' => ['stringValue' => $data['request_id'] ?? ''],
                ]
            ];

            $response = Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . $accessToken])
                ->post($url, $payload);

            if (!$response->successful()) {
                Log::error("Failed to write notification to Firestore: " . $response->body());
                throw new \Exception("Firestore write error: " . $response->body());
            }

        } catch (\Exception $e) {
            Log::error("NotificationService Error: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retrieve the cached/fresh access token for Firestore REST API.
     */
    private function getFirestoreAccessToken(): string
    {
        return cache()->remember('firestore_access_token', 3000, function () {
            $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT_JSON') ?: env('FIREBASE_CREDENTIALS');
            if (!$serviceAccountPath || !file_exists($serviceAccountPath)) {
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

            $tokenResponse = Http::withoutVerifying()->timeout(15)->asForm()->post('https://oauth2.googleapis.com/token', [
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


