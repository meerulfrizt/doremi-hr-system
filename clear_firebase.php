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
$collections = ['users', 'attendances', 'overtime', 'leaves', 'flexi_requests', 'assigned_tasks', 'notifications'];

$keepUids = [
    'uid-ahmad-faris', 'uid-nur-hafizah', 'uid-sara-irdina', 'uid-khairul-anwar', 
    'uid-muhammad-haziq', 'uid-nurul-ain', 'uid-amirul-hakim', 'uid-fatin-nabilah'
];

$deletedCount = 0;
$keptCount = 0;

foreach ($collections as $collection) {
    echo "Scanning collection: $collection...\n";
    $nextPageToken = null;
    
    do {
        $url = "{$baseUrl}/{$collection}?pageSize=300";
        if ($nextPageToken) {
            $url .= "&pageToken={$nextPageToken}";
        }
        
        $res = Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->get($url);
        
        if ($res->successful()) {
            $data = $res->json();
            $documents = $data['documents'] ?? [];
            $nextPageToken = $data['nextPageToken'] ?? null;
            
            foreach ($documents as $doc) {
                $docName = $doc['name'];
                $docId = basename($docName);
                
                $isKeep = false;
                
                if ($collection === 'users') {
                    if (in_array($docId, $keepUids)) $isKeep = true;
                } else {
                    $docUid = $doc['fields']['uid']['stringValue'] ?? null;
                    if ($docUid && in_array($docUid, $keepUids)) {
                        $isKeep = true;
                    }
                }
                
                if ($isKeep) {
                    $keptCount++;
                } else {
                    Http::withoutVerifying()->withHeaders(['Authorization' => "Bearer {$token}"])->delete("https://firestore.googleapis.com/v1/" . $docName);
                    $deletedCount++;
                    if ($deletedCount % 100 == 0) {
                        echo "  -> Deleted {$deletedCount} records so far...\n";
                    }
                }
            }
        } else {
            echo "Failed to fetch $collection: " . $res->body() . "\n";
            break;
        }
    } while ($nextPageToken);
}

echo "\nDone! Total old records deleted: {$deletedCount}. Total new records kept: {$keptCount}.\n";
