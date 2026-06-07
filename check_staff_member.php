<?php

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\Http;

$projectId = env('FIREBASE_PROJECT_ID', 'doremi-admin');
$baseUrl = "https://firestore.googleapis.com/v1/projects/{$projectId}/databases/(default)/documents";

function getToken() {
    $serviceAccountPath = env('FIREBASE_SERVICE_ACCOUNT_JSON', storage_path('firebase-auth.json'));
    $serviceAccount = json_decode(file_get_contents($serviceAccountPath), true);    

    $jwtHeader = base64_encode(json_encode(['alg' => 'RS256', 'typ' => 'JWT']));
    $now = time();
    $jwtClaim = base64_encode(json_encode([
        'iss' => $serviceAccount['client_email'],
        'scope' => 'https://www.googleapis.com/auth/datastore',
        'aud' => 'https://oauth2.googleapis.com/token',
        'exp' => $now + 3600,
        'iat' => $now,
    ]));

    $signatureInput = $jwtHeader . '.' . $jwtClaim;
    openssl_sign($signatureInput, $signature, $serviceAccount['private_key'], 'sha256WithRSAEncryption');
    $jwt = $signatureInput . '.' . base64_encode($signature);

    $response = Http::withoutVerifying()->asForm()->post('https://oauth2.googleapis.com/token', [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
        'assertion' => $jwt,
    ]);

    return $response->json('access_token');
}

$token = getToken();

$url = "{$baseUrl}/attendances?pageSize=300";
$res = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get($url);
$docs = $res->json()['documents'] ?? [];

echo "Attendances:\n";
foreach ($docs as $doc) {
    $uid = $doc['fields']['uid']['stringValue'] ?? 'NO_UID';
    $name = $doc['fields']['name']['stringValue'] ?? 'NO_NAME';
    $dept = $doc['fields']['department']['stringValue'] ?? 'NO_DEPT';
    
    if ($name === 'Staff Member') {
        echo "Found: UID=$uid | NAME=$name\n";
    }
}
